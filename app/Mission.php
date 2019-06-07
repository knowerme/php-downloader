<?php

namespace App;

use Doctrine\DBAL\DriverManager;
// use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use App\Config;
use App\OptionsDB;

/**
 * Создание и работа с миссиями по закачке чего либо
 *
 * @author KnowerMe [local]
 * @date   2019-05-14
 */
class Mission
{
    /**
     * Допустимые названия опция для свойст миссии
     *
     * @var array
     */
    private $definedOptionsAndAllowedTypes =
    [
        'test'   => 'string',
        'url'    => 'string',
        'type'   => 'string',
        'siteClass'  => 'object',
        'downloadClass' => 'object',
        'hash'   => 'string',
        'title'  => 'string',
        'status' => 'string',
        'lastCheckUrl' => 'DateTime',
        'lastDownload' => 'DateTime',
        'codeStatus' => 'string',
        'error' => 'int',
        'fileName' => 'string',
        'file'  => 'object',
    ];

    /**
     * Имя таблицы где храним данные
     *
     * @var string
     */
    private $tablePrefix = 'mission';

    /**
     * Свойстава mission
     *
     * @var array
     */
    private $options = [];

    /**
     * Обетк с опциями
     *
     * @var OptionsDB
     */
    private $optionsDB;

    /**
     * Текущий ID записи.
     * Нужен тока для сериализации обекта.
     * В системе не используется
     *
     * @var int
     */
    public $id;

    /**
     * При создании класса можно сразу отправить свойства для нового или
     * загрузить уже имеющийся (передав ID)
     *
     * @param  array $options Опции для миссии
     */
    public function __construct(array $options = null)
    {
        # создаём новый класс опций
        $this->optionsDB = new OptionsDB($this->tablePrefix);

        # задаём допустимые значения свойств
        $this->optionsDB->addDefinedOptionsAndAllowedTypes($this->definedOptionsAndAllowedTypes);

        # если есть опции, обрабатываем их
        if (isset($options)){
            if (isset($options['id'])){ // если есть ID, загружаем
                $this->load($options['id']);
            }else{ // если нет  ID, создаём новый
                $this->new($options);
            }
        }
    }


    /**
     * Подгрузить сохранённую миссию по её ID
     *
     * @param  int $id ID миссии
     */
    public function load(int $id = null)
    {
        $this->optionsDB->loadByID($id);
    }

    /**
     * Создать новую миссию из массива
     *
     * @param  array $options Массив с опциями
     * @return bool Удалось ли
     */
    public function new(array $options = null)
    {
        $this->optionsDB->new($options);
    }

    /**
     * Удалить уже загруженную запись
     *
     */
    public function delete()
    {
        $this->optionsDB->delete();
    }

    public function toArray() : array
    {
        return $this->optionsDB->toArray();
    }

    public function fromArray(array $options = null, bool $megreOptions = false)
    {
        return $this->optionsDB->fromArray($options, $megreOptions);
    }

    public function __toString() : string
    {
        return $this->optionsDB->__toString();
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
        if (!$this->optionsDB->isId()) throw new \Exception("Error: Not set ID.");
        $this->id = $this->optionsDB->getId();
        return array('id');
        // return [];
    }

    public function __wakeup()
    {
        if (empty($this->id)) throw new \Exception("Error: Not set ID.");
        $this->__construct(['id'=>$this->id]);
    }

    public function test(){
        // return $this->saveMission();
    }
}
