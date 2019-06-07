<?php

namespace App\Sites;

use Symfony\Component\OptionsResolver\OptionsResolver;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use PHPHtmlParser\Dom;

use App\Config;
use App\OptionsDB;
use App\Mission;
// use App\Files\File;


/**
 * Класс с основными функциями конекта к сайту
 *
 * @author KnowerMe [local]
 * @date   2019-05-14
 */
abstract class Site
{

    /**
     * Допустимые названия опция для свойст миссии
     *
     * @var array
     */
    protected $definedOptionsAndAllowedTypes =
    [
        'test'   => ['array','string','int','float','object','bool'],
    ];

    /**
     * Опции самого сайта
     *
     * @var array
     */
    protected $siteOptions = [];

    /**
     * Опции клиента Curl
     *
     * @var array
     */
    protected $clientOptions = [];

    /**
     * Прокси сервер, если указан.
     *
     * Подгружается из файла настроек (если оставить пустым)
     * или если указать конкретно в этой переменной
     *      (socks5h://[user:password@]proxyhost[:port])
     * или FALSE если не нужно использовать прокси для этого сайта
     *
     * @var string|bool
     */
    protected $clientProxy = '';

    /**
     * Имя таблицы где храним данные
     *
     * @var string
     */
    private $tablePrefix = 'sites';

    /**
     * Имя файла где хранятся куки между сессиями
     * Если пустое, то куки не хранить.
     *
     * @var string
     */
    protected $cookiesFile = '';

    /**
     * Путь до места хранения файлов куки
     *
     * @var string
     */
    private $cookiesFilePatch = __DIR__ . '/../../db/Cookies/';

    /**
     * Обетк с опциями
     *
     * @var OptionsDB
     */
    private $optionsDB;

    /**
     * Соединение для скачивания
     *
     * @var Client
     */
    protected $client;

    /**
     * Миссии
     *
     * @var Mission
     */
    protected $mission;

    /**
     * Ответ от сервера
     * со всеми параметрами и заголовками
     *
     * @var Response
     */
    protected $response;

    /**
     * Название сайта
     *
     * @var string
     */
    protected $siteName = 'Дефолтный сайт';

    /**
     * Если нужно скачать и только
     *
     * @var bool
     */
    public $onlyDownload = false;

    /**
     * URL, когда нужно просто скачать
     *
     * @var string
     */
    public $url = '';



    public function __construct(array $clientOptions = [], $cookiesFile = null)
    {
        # Новый файл куков если задан
        if (isset($cookiesFile)) $this->cookiesFile = $cookiesFile;

        # загружаем настройки
        $this->loadConfig();

        # создаём новый класс опций
        $this->optionsDB = new OptionsDB($this->tablePrefix);
        # задаём допустимые значения свойств
        $this->optionsDB->addDefinedOptionsAndAllowedTypes($this->definedOptionsAndAllowedTypes);
        # Загружаем свойства сайта из БД, если ещё нет, то создаём новые
        $this->optionsDB->loadByName((string)$this->siteName, true);
        # загружаем для лучшей визуализации при отладке
        $this->siteOptions = $this->optionsDB->toArray();

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->clientOptions = $resolver->resolve($clientOptions);

        # Создаём соединение
        $this->loadClient();
    }

    /**
     * Обработка значений по умолчанию
     *
     * @param  OptionsResolver $resolver Класс работы с дефолтными значениями
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $default = [
            'debug' => false,
            'cookies' => true,
        ];
        # если прокуси указан
        if (!empty($this->clientProxy)) $default['proxy'] = $this->clientProxy;
        # если нужны куки в файл
        if (!empty($this->cookiesFile)){
            $file = $this->cookiesFilePatch . $this->cookiesFile;
            $default['cookies'] = new \GuzzleHttp\Cookie\FileCookieJar($file);
        }

        $resolver->setDefaults($default);
    }

    private function loadConfig()
    {
        $config = new \App\Config();
        if ($config->isProxy()){
            if ($this->clientProxy !== false && empty($this->clientProxy)){
                $this->clientProxy = $config->getProxy();
            }
        }
    }

    public function loadClient()
    {
        # создаём клиента для подключения к сайту
        $this->client = new Client( $this->clientOptions );
    }

    public function loadMission(Mission $mission)
    {
        $this->mission = $mission;
    }

    /**
     * Запрос GET к сайту
     *
     * @param  string $url URL для запроса GET
     * @param  array $data Массив анных которые нужно отправить на страницу
     * @return Response Все параметры возвращённые от сайта
     */
    public function GET(string $url, array $data=[])
    {
        if (empty($url)) throw new \Exception("Error: Не передан URL для загрузки.", 1);

        # запрашиваем страницу
        $response = $this->client->request('GET', $url, $data);

        # сохраняем куки
        # Сохраняются автоматом
        // if (is_object($this->clientOptions['cookies'])) $this->clientOptions['cookies']->save($this->cookiesFilePatch . $this->cookiesFile);

        return $response;
    }

    /**
     * Запрос POST к сайту
     *
     * @param  string $url URL для запроса POST
     * @param  array $data Массив анных которые нужно отправить на страницу
     * @return Response Все параметры возвращённые от сайта
     */
    public function POST(string $url, array $data=[])
    {
        if (empty($url)) throw new \Exception("Error: Не передан URL для загрузки.", 1);
        // if (empty($data)) throw new \Exception("Error: Не передан Массив данных для загрузки.", 1);

        # запрашиваем страницу
        $response = $this->client->request('POST', $url, $data);

        # сохраняем куки
        # Сохраняются автоматом
        // if (is_object($this->clientOptions['cookies'])) $this->clientOptions['cookies']->save($this->cookiesFilePatch . $this->cookiesFile);

        return $response;
    }

    /**
     * Возвращяет URL куда нужно конектится
     *
     * @return string URL
     */
    abstract protected function _getUrl() : string;

    /**
     * Действия пере основным скаччиванием,
     * например авторизация.
     * Если уже была получена страница, то можно и не скачивать
     * снова
     *
     */
    abstract public function preRun();

    /**
     * Основное скачивание
     *
     */
    abstract public function Run();

    /**
     * Действия после скачивания.
     * Например выход с сайта.
     *
     */
    abstract public function postRun();

    /**
     * Возвращяет обект torrnetFile
     *
     * @return object
     */
    abstract public function finish();



    public function toArray() : array
    {
        return $this->optionsDB->toArray();
    }

    public function fromArray(array $options = null, bool $megreOptions = false)
    {
        return $this->optionsDB->fromArray($options, $megreOptions);
    }

    public function test0()
    {
        return $this->cookiesFile;
    }

    /**
     * Обработка методов setName И getName и подобных
     *
     * @param  string $name Имя метода
     * @param  mixed $args Значение переменной
     * @return any Содержимое переменной
     */
    public function __call($name, $args)
    {
        return $this->optionsDB->__call($name, $args);
    }

    public function __sleep()
    {
        // return array('siteName');
        return [];
    }

    public function __wakeup()
    {
        $this->__construct();
    }

    public function __toString() : string
    {
        return $this->siteName;
    }

}
