<?php

namespace App\Sites;

use PHPHtmlParser\Dom;

use App\Files\TorrentFile;

/**
 * клас функций рутрекера
 *
 * @author KnowerMe [local]
 * @date   2019-04-26
 */
class Rutracker extends Site
{
    /**
     * Название сайта
     *
     * @var string
     */
    protected $siteName = 'RuTracker.org';

    /**
     * Имя файла где хранятся куки между сессиями
     * Если пустое, то куки не хранить.
     *
     * @var string
     */
    protected $cookiesFile = 'rutracker_org.txt';

    /**
     * Допустимые названия опция для свойст миссии
     *
     * @var array
     */
    protected $definedOptionsAndAllowedTypes =
    [
        'test'   => ['array','string','int','float','object','bool'],
        'login'  => 'string',
        'pass'  => 'string',
        'host' => 'string',
        'loginform' => 'string',
        'viewtopic' => 'string',
        'download' => 'string',
        'lastCheckSite' => 'DateTime',
        'code' => 'array',
        'codeStatus' => 'string',
        'codeInput' => 'string',
        'error' => 'int',
        'formToken' => 'string',
    ];

    /**
     * Содержимое страницы
     *
     * @var string
     */
    protected $body = '';

    /**
     * Торрент файл
     *
     * @var TorrentFile
     */
    protected $file;



    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Проверяем, Возможно уже случилось много ошибок
     * Тогда TRUE
     *
     * @return bool True, если много ошибок
     */
    protected function checkError()
    {
        if ((int)$this->getError() > 5) return true;
        return false;
    }

    /**
     * Возвращяет URL куда нужно конектится
     *
     * @return string URL
     */
    protected function _getUrl() : string
    {
        if (!empty($this->url)){
            $topicid = $this->url;
        }else{
            $topicid = $this->mission->getUrl();
        }
        # формируем ссылку для скачивания
        $url = $this->siteOptions['host'].$this->siteOptions['viewtopic'].$topicid;
        return $url;
    }

    /**
     * Ищет код для авторизации
     * И возврящяет чего
     * Или возврящяет false
     *
     * @return array|bool Коды авторизации или false
     */
    private function _getCode()
    {
        $dom = new Dom;
        $dom->load($this->body);
        $code = [];
        $code['reg-input'] = $dom->find('.reg-input')[0]->name ?? '';
        $code['cap_sid'] = $dom->find('input[name="cap_sid"]')[0]->value ?? '';
        $code['img'] = $dom->find('img[alt="pic"]')[0]->src ?? '';
        // $code['test'] = $dom->find('td:contains(Код:)')[0]->innerHtml;
        // r($dom->find('input[name="cap_sid"]')[0]->next()->innerHtml);
        // r($code);
        unset($dom);
        if (!empty($code)) return $code;
        return false;
    }

    /**
     * Ищем на странице токен форм
     *
     */
    private function _findToken()
    {
        # ищем токен на странице
        preg_match("/form_token: '(.*)',/i", $this->body, $matches);
        if (isset($matches[1])){
            $this->setFormToken($matches[1]);
        }else {
            $this->setFormToken("");
        }
    }

    /**
     * Скачиваем страницу и проверяем на ошибки
     *
     * @param  string $url URL страницы куда отправляем данные
     */
    private function _getPage(string $url=null)
    {
        if ($this->checkError()) return false; // Превышен лимит ошибок
        if (empty($url)) throw new \Exception("Не передан URL для загрузки.", 1);

        if ($this->getCodeStatus() == 'required'){ // если ждём распознания, ничего не запрашиваем
            return false;
        }

        // r($url);

        $this->response = $this->GET($url);
        if ($this->response->getStatusCode() != 200){
            $this->setError((int)$this->getError() + 1); // увеличиваем ошибку на 1
            throw new \Exception("Error getStatusCode: ".$this->response->getStatusCode(), 1);
        }
        # содердимое страницы
        $this->body = mb_convert_encoding($this->response->getBody(), "UTF-8", "Windows-1251");

        # ищем токен формы
        $this->_findToken();
    }

    /**
     * Посылаем форму на сайт
     *
     * @param  string $url URL страницы куда отправляем данные
     * @param  array $data Массив анных которые нужно отправить на страницу
     */
    private function _postPage(string $url=null, array $data=null)
    {
        if ($this->checkError()) return false; // Превышен лимит ошибок
        if (empty($url)) throw new \Exception("Не передан URL для загрузки.", 1);
        if (empty($data)) throw new \Exception("Не передан Массив данных для загрузки.", 1);

        if ($this->getCodeStatus() == 'required'){ // если ждём распознания, ничего не запрашиваем
            return false;
        }

        // r($url, $data);

        $this->response = $this->POST($url, $data);
        if ($this->response->getStatusCode() != 200){
            $this->setError((int)$this->getError() + 1); // увеличиваем ошибку на 1
            throw new \Exception("Error getStatusCode: ".$this->response->getStatusCode(), 1);
        }
        # содердимое страницы
        $this->body = mb_convert_encoding($this->response->getBody(), "UTF-8", "Windows-1251");

        # ищем токен формы
        $this->_findToken();
    }

    /**
     * Проверяем, обновились ли данные
     * И нужно ли скачивать снова
     *
     * @return bool Возвращяем true если страница обновлена
     */
    public function checkUpdate()
    {
        # если просто нужно скачать
        if ($this->onlyDownload){
            return true;
        }

        $dom = new Dom;
        $dom->load($this->body);
        $title = $dom->find('title')[0]->text ?? '';
        $md5 = md5($title);
        // $md5 = '123';
        unset($dom);
        # изменилась ли страница
        if ($md5 != $this->mission->getHash()){
            $this->mission->setStatus('updated');
            $this->mission->setTitle($title);
            return true;
        }else{
            return false;
        }
    }

    /**
     * Проверяем авторизацию
     *
     * @return bool Возвращяем true если авторизован
     */
    public function checkAuth()
    {
        // Проверяем наличие дива с именем авторизованного пользователя
        $dom = new Dom;
        $dom->load($this->body);
        $login = $dom->find('#logged-in-username')[0]->text ?? '';
        // if (!empty($login)) r($login);
        unset($dom);
        # проверяем наличие
        if (!empty($login)){ // существует, значит авторизован
            if ($login == $this->getLogin()){ // совпадает с логином авторизации
                return true;
            }else{ // авторизован под другим пользователем,
                // Нужно ВЫЙТИ и авторизоваться снова
                $this->logoutAuth();
                throw new \Exception("Error auth site. Авторизован под другим пользователем", 1);
                return false;
            }
        }
        return false;
    }

    /**
     * Авторизуемся на сайте
     *
     * @param  bool $withCode True если авторизация уже с распознанным кодом
     * @return bool Возвращяем true если авторизовался удачно
     */
    private function loginAuth($withCode=false)
    {
        if ($this->checkAuth()) return true; // уже авторизован
        if ($this->getCodeStatus() == 'required'){
            // echo "Before authorization must recognize the code!";
            return false;
        }

        // Готовим форму авторизации
        $form = ['form_params' => [
            'login_username'    => $this->siteOptions['login'],
            'login_password'    => $this->siteOptions['pass'],
            'login-ssl'         => 1,
            'login'             => 'Вход',
            'redirect'          => $this->_getUrl(),
            'form_token'        => $this->getFormToken()??'',
        ]];

        # если есть код, добавляем его к форме
        if ($this->getCodeStatus() == 'recognized'){ // КОД распознан и нужно ввести код авторизации
            $codeInput = $this->getCodeInput();
            $codeInput = mb_convert_encoding($codeInput, "Windows-1251", "UTF-8");
            if (!empty($codeInput)){
                $codeArr = $this->getCode();
                $form['form_params'] = array_merge($form['form_params'], [
                    'cap_sid' => $codeArr['cap_sid'],
                    $codeArr['reg-input'] => $codeInput,
                ]);
            }
            // r($form);
        }

        // адрес отправки
        $url = $this->siteOptions['host'] . $this->siteOptions['loginform'];

        // $this->body = \file_get_contents('body.txt');

        // отправляем форму авторизации
        $this->_postPage($url, $form);

        // \file_put_contents('body.txt', $this->body);
        // echo $this->body;

        # Если авторизовались удачно
        if ($this->checkAuth()){
            $this->setCodeStatus('');
            // $this->mission->setCodeStatus('');
            $this->setCode([]);
            $this->setCodeInput('');
            return true;
        }

        # если есть код, получаем данные кода
        # и сохраняем в базу их
        $code = $this->_getCode();
        if (empty($code) || $code == false){ // кода нет
            // r("Нет кода");
        }else{ // есть код
            // r("Есть код");
            // r($code);
            if ($this->getCode()['reg-input'] != $code['reg-input']){ // проверяем что код изменился
                $this->setCode($code);
                $this->setCodeStatus('required');
                // $this->mission->setCodeStatus('required');
                $this->setCodeInput(''); // очищяем старый код
            }
        }

        // # Повторно (рекурсивно) авторизуемся, если это уже не повтораня
        // # чтобы не получить бесконетную рекурсию
        // if (($withCode == false) && ($this->getCodeStatus() == 'recognized')){
        //     return $this->loginAuth(true);
        // }

        return false;
    }

    /**
     * Выйход из авторизации
     *
     */
    public function logoutAuth()
    {
        if (!$this->checkAuth()) return true; // уже НЕ авторизован
        if (!empty($this->getCodeStatus())) {
            # ждем каких то действий с кодами и ничего не делаем
            return false;
        }

        # формируем форму
        $form = ['form_params' => [
            'logout'            => '1',
            'redirect'          => $this->_getUrl(),
            'form_token'        => $this->getFormToken()??'',
        ]];

        // адрес отправки
        $url = $this->siteOptions['host'] . $this->siteOptions['loginform'];
        // отправляем форму авторизации
        $this->_postPage($url, $form);

        // echo $this->body;
    }

    /**
     * Проверяем авторизацию и если нужно авторизуемся
     *
     */
    private function Auth()
    {
        if (!$this->checkAuth()){ // не авторизован
            // авторизуемся
            if ($this->loginAuth()){ // удалось авторизоватся
                return true;
            }else{ // не удалось авторизоваться
                return false;
            }
        }
        return true;
    }

    /**
     * Скачиваем торрент файл
     *
     */
    private function downloadFile()
    {
        if (!$this->checkUpdate()){
            return false;
        }
        if (!$this->checkAuth()){
            return false;
        }

        if (!$this->onlyDownload){
            $this->mission->setStatus('Download');
        }

        if ($this->onlyDownload){
            $fileName = 'tmp_000.torrent';
        }else{
            $fileName = 'tmp_' . $this->mission->getId() . '.torrent';
        }

        $this->file = new TorrentFile($fileName);

        # готовим данные для скачивания
        // указываем куда сохранить файл при скачивании
        $form = ['sink' => $this->file->getResource()];

        # URL для скачивания
        if (!empty($this->url)){
            $topicid = $this->url;
        }else{
            $topicid = $this->mission->getUrl();
        }
        $url = $this->siteOptions['host'].$this->siteOptions['download'].$topicid;

        # GET
        $response = $this->GET($url, $form);
        if ($response->getStatusCode() != 200){
            $this->setError((int)$this->getError() + 1); // увеличиваем ошибку на 1
            throw new \Exception("Error getStatusCode: ".$this->response->getStatusCode(), 1);
        }
        // r($response->getBody()); // ->getContents()
        // r($response->getHeader('Content-Disposition')[0]);

        # новое имя файла
        preg_match('/filename="(.*)";/i', $response->getHeader('Content-Disposition')[0], $matches);
        if (!empty($matches[1])) {
            $this->file->rename($matches[1]);
        }

        # после скачивания
        if (!$this->onlyDownload){
            $this->mission->setStatus('done');
            $this->mission->setHash(md5($this->mission->getTitle())); // обновляем хэш названия
            $this->mission->setFileName($this->file->getFileName());
        }

        return true;
    }

    /**
     * Вводим распознынный код в базу
     *
     * @param  string $code Распознанный код
     */
    public function putCode(string $code=null)
    {
        if (!isset($code)) throw new \Exception("Не указан распознанный код.", 1);
        $this->setCodeInput($code);
        $this->setCodeStatus('recognized');
        // $this->mission->setCodeStatus('recognized');
    }

    /**
     * Возвращяет URL картинки для распознания
     *
     * @return string|bool URL картинки или FALSE
     */
    public function showImg()
    {
        $img = $this->getCode()['img'];
        if (empty($img)) return false;
        return $img;
    }

    /**
     * Действия пере основным скаччиванием,
     * например авторизация.
     *
     */
    public function preRun()
    {
        # если код распознан, вначале авторизуемся
        if ($this->getCodeStatus() == 'recognized'){
            $this->Auth();
        }

        # получаем страницу
        $url = $this->_getUrl();
        $this->_getPage($url);

        # Время последнего посещения сайта
        if (!$this->onlyDownload){ // если не только скачать но и по полной
            $dt = new \DateTime();
            $this->mission->setLastCheckUrl($dt);
            $this->setLastCheckSite($dt);
        }

        // echo $this->body;
        // r($this->response->getStatusCode());
        // r($this->response->getBody()->getContents());
    }

    /**
     * Основное скачивание
     *
     */
    public function Run()
    {
        if ($this->checkUpdate()){ // если страница обновлена
            if ($this->Auth()){ // проверить авторизацию или авторизуемся
                // скачать
                // r("СКАЧИВАЮ");
                // echo "СКАЧИВАЮ \r\n";
                $this->downloadFile(); // скачиваю файл
                return "UPDATE";
                // return true;
            }else {
                // r("НЕ АВТОРИЗОВАЛСЯ");
                // echo "НЕ АВТОРИЗОВАЛСЯ \r\n";
                $this->setError((int)$this->getError() + 1); // увеличиваем ошибку на 1
                return "ERROR AUTH";
                // return false;
            }
        }else {
            // r("НИЧЕГО НЕ ОБНОВИЛОСЬ");
            // echo "НИЧЕГО НЕ ОБНОВИЛОСЬ \r\n";
            return "NO UPDATE";
            // return null;
        }
        // echo $this->body;
    }

    /**
     * Действия после скачивания.
     *
     */
    public function postRun()
    {
        //
    }

    /**
     * Возвращяет обект torrnetFile
     *
     * @return TorrentFile
     */
    public function finish()
    {
        if (empty($this->file)) return false;
        return $this->file;
        // return false;
    }
}
