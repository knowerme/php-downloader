<?php
namespace App;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Schema\Table;
// use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

use App\Config;

/**
 * Хранение и работа с опциями хранящимися в БД
 *
 * @author KnowerMe [local]
 * @date   2019-05-13
 *
 */
class OptionsDB
{
    use \App\Traits\Fantastic;

    /**
     * Версия данного класса
     *
     * @var string
     */
    public $version = '1.0.1';

    /**
     * Ссылка на соединение с БД
     *
     * @var DriverManager
     */
    private $conn;

    /**
     * @var OptionsResolver
     */
    private $resolver;

    /**
     * Параметры подключения к базе данных
     *
     * @var array
     */
    private $connectionDbParams = [];

    /**
     * Сами опции
     *
     * @var array
     */
    private $options = [];

    /**
     * Дефолтное название префикса таблиц
     *
     * @var string
     */
    private $tablePrefix = 'Options';

    /**
     * Имена таблиц
     *
     * @var array
     */
    private $tableName = ['Options_name', 'Options_options'];

    /**
     * Столбцы из таблицы _name
     *
     * @var array
     */
    private $columName = ['id','name','active','run_now'];

    /**
     * Допустимые названия опций и их тип
     *
     * @var array
     */
    private $definedAndTypeOptions = ['test'   => ['array','string','int','float','object','bool']];

    /**
     * Автоматом добавлять в setDefined неизвестные свойства из БД
     *
     * @var bool
     */
    private $autoSetDefined = true;

    /**
     * Сохранять в БД автоматом при изменении свойств и опций
     *
     * @var bool
     */
    private $autoSave = true;


    /**
     * Создание класса
     *
     * @param  string $tablePrefix Имя префикса для таблиц
     */
    public function __construct(string $tablePrefix = null)
    {
        # проверяем наличие названия таблиц
        if (!isset($tablePrefix)){
            throw new \Exception("No setup class OprionsDB", 1);
        }else{
            $this->tablePrefix = $tablePrefix;
        }

        # название таблиц
        $this->tableName = [$this->tablePrefix.'_name', $this->tablePrefix.'_options'];

        # загружаем настройки базы данных
        $this->loadConfig();

        # подключаемся к БД
        $this->connectDB();

        # Если нет таблиц, создаём их в БД
        $this->createTable();

        # дефолтные опции
        $this->resolver = new OptionsResolver();
        $this->configureOptions($this->resolver);
    }

    /**
     * Загружаем настройки из файла настроек
     *
     */
    private function loadConfig()
    {
        $config = new Config();
        $this->connectionDbParams = $config->getDb();
    }

    /**
     * Подключаемся к БД
     */
    private function connectDB()
    {
        try {
            # подключаемся к базе данных
            $config = new Configuration();
            $this->conn = DriverManager::getConnection($this->connectionDbParams, $config);
            // TODO: Вынести подключение к БД в отдельный класс DB
        }catch (\Exception $e) {
            // die('ERROR: '.$e);
            throw new \Exception("Не удалось подключится к БД: ".$e->getMessage(), 0, $e);
        }
    }

    /**
     * Обработка значений по умолчанию
     *
     * @param  OptionsResolver $resolver Класс работы с дефолтными значениями
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        # Опции значений по умолчанию
        $resolver->setDefaults([
            'name' => 'Новая запись',
            'active' => true,
            'run_now' => false,
        ]);

        $resolver->setDefined('id');
        foreach ($this->definedAndTypeOptions as $key => $value){
            # Допустимые Опции без значений по умолчанию
            $resolver->setDefined($key);
            # Проверка типа
            $resolver->setAllowedTypes($key, $value);
        }

        // r($resolver->getDefinedOptions());

        # Обезательный параметр
        // $resolver->setRequired(['name']);

        # Проверка типа
        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('id', 'int');
        $resolver->setAllowedTypes('active', 'bool');
        $resolver->setAllowedTypes('run_now', 'bool');
    }

    /**
     * проверяем существует ли такие таблицы
     *
     * @return boolean
     */
    private function isTableInDB(string $tableName = null) : bool
    {
        $sm = $this->conn->getSchemaManager();
        if ($sm->tablesExist($tableName)){
            unset($sm);
            return true;
        }
        unset($sm);
        return false;
    }

    /**
     * Существует ли ID записи
     *
     * @return bool
     */
    public function isId() : bool
    {
        if (!empty($this->options['id'])){
            return true;
        }else{
            return false;
        }
    }

    /**
     * Проверяет наличие опции в базе данных
     *
     * @param  string $property Название опции
     * @return bool
     */
    private function isOptionSql(string $property) : bool
    {
        if (empty($property)) return false;
        if (!$this->isId()) return false;
        $qb = $this->conn->createQueryBuilder();
        $qb ->select('id') //'id', 'name'
            ->from($this->tableName[1])
            ->where('name_id = ?')
            ->andWhere('property = ?')
            ->setParameter(0, (int)$this->options['id'], 'integer')
            ->setParameter(1, $property, 'string')
        ;
        if ($qb->execute()->rowCount() > 0){
            unset($qb);
            return true;
        }
        unset($qb);
        return false;
    }

    /**
     * Проверяет наличие опции в БД с таким Именем
     *
     * @param  string $property Имя для поиска в БД
     * @return bool Существует или нет
     */
    public function isNameSql(string $name = null) : bool
    {
        if (!isset($name)) throw new \Exception("не указан NAME записи для проверки", 1);
        $qb = $this->conn->createQueryBuilder();
        $qb ->select('id')
            ->from($this->tableName[0])
            ->where('name = ?')
            ->setParameter(0, $name, 'string')
        ;
        if ($qb->execute()->rowCount() > 0){
            unset($qb);
            return true;
        }
        unset($qb);
        return false;
    }

    /**
     * Проверяем наличие записи в БД по ID
     *
     * @param  int $id ID записи в БД
     * @return bool
     */
    public function isIDSql(int $id = null) : bool
    {
        if (!isset($id)) throw new \Exception("не указан ID записи для проверки", 1);
        $qb = $this->conn->createQueryBuilder();
        $qb ->select('id')
            ->from($this->tableName[0])
            ->where('id = ?')
            ->setParameter(0, $id, 'integer')
        ;
        if ($qb->execute()->rowCount() > 0){
            unset($qb);
            return true;
        }
        unset($qb);
        return false;
    }

    /**
     * Создание новых таблиц с опциями
     */
    private function createTable()
    {
        $sm = $this->conn->getSchemaManager();

        if (!$this->isTableInDB($this->tableName[0])){
            $myTable = new Table($this->tableName[0]);
            $myTable->addColumn("id", "integer", ["unsigned" => true, 'autoincrement' => true]);
            $myTable->addColumn("name", "string", ['default'=> '-', 'comment'=>'Имя задачи']);
            $myTable->addColumn("active", "boolean", ['default'=> '1', 'comment'=>'Задача разрешена']);
            $myTable->addColumn("run_now", "boolean", ['default'=> '0', 'comment'=>'Задача запущена сейчас']);
            $myTable->setPrimaryKey(["id"]);
            $myTable->addUniqueIndex(['name'], 'name');
            $sm->createTable($myTable);
            unset($myTable);
        }

        if (!$this->isTableInDB($this->tableName[1])){
            $myTable = new Table($this->tableName[1]);
            $myTable->addColumn("id", "integer", ["unsigned" => true, 'autoincrement' => true]);
            $myTable->addColumn("name_id", "integer", ['notnull'=>true]);
            $myTable->addColumn("property", "string", ['comment'=>'Свойство']);
            $myTable->addColumn("value", "string", ["length" => 20000, 'comment'=>'Значение']);
            $myTable->addColumn("type", "string", ['default'=>'string', 'comment'=>'Тип переменной']);
            // $myTable->addColumn("comment", "string", ['notnull'=>false,'comment'=>'Коментарий к свойству']);
            $myTable->setPrimaryKey(["id"]);
            $myTable->addUniqueIndex(['name_id','property'], 'proper');
            $sm->createTable($myTable);
            unset($myTable);
        }

        // r($sm->listTables());
        unset($sm);
    }

    /**
     * Добавление допустимых опций и их типа
     *
     * ['name property' => 'Allowed type property or NULL']
     *
     * @param  array $options Массив допустимых опций и их допустимого типа типа
     * @return boolean
     */
    public function addDefinedOptionsAndAllowedTypes(array $options = null)
    {
        if (!isset($options)) return false; // если неу
        $this->definedAndTypeOptions = $options;
        $this->resolver = new OptionsResolver();
        $this->configureOptions($this->resolver);
        // r($this->resolver->getDefinedOptions());
        return true;
    }

    /**
     * Создаёт новую запись со стандартными параметрами
     *
     * ['name'=>'моя новая запись', 'test'=>true, 'test2'=>123]
     *
     * @param  array $options Массив опций
     * @return bool Удалось ли создать или нет
     */
    public function new(array $options = null) : bool
    {
        if (!isset($options)) throw new \Exception("Нельзя создать новое из ничего.", 1);
        if ($this->isId()) throw new \Exception("Вы пытаетесь создать новые опции на базе существующих.", 1);
        $this->options = $this->resolver->resolve($options);
        if (!$this->isName()) throw new \Exception("Не указан name для загрузки.", 1);
        if ($this->isNameSql($this->getName())){
            throw new \Exception("Запись с таким названием уже существует.", 1);
        }
        $qb = $this->conn->createQueryBuilder();
        $qb
            ->insert($this->tableName[0])
            ->setValue('name', '?')
            ->setValue('active', '?')
            ->setValue('run_now', '?')
            ->setParameter(0, $this->getName(), 'string')
            ->setParameter(1, $this->getActive(), 'boolean')
            ->setParameter(2, $this->getRun_now(), 'boolean')
        ;
        // r($qb->getSql());
        $qb->execute();
        $lid = (int) $this->conn->lastInsertId();
        unset($qb);
        if ($lid > 0){
            $this->options['id'] = $lid;
            $this->saveOptions();
            return true;
        }
        return false;
    }

    /**
     * Сохраняем текущее в базу
     *
     */
    public function save()
    {
        // TODO: добавить когданить возможность использования: setOptions()->save()
        $this->saveName();
        $this->saveOptions();
    }

    /**
     * Сохраняем в базу текушую опцию
     *
     */
    private function saveName()
    {
        if (!$this->isId()) throw new \Exception("Не указан id для загрузки.", 1);
        if (!$this->isName()) throw new \Exception("Не указан Name для сохранения.", 1);
        if ($this->isIDSql($this->getId())){
            $qb = $this->conn->createQueryBuilder();
            $qb
                ->update($this->tableName[0])
                ->set('name', '?')
                ->set('active', '?')
                ->set('run_now', '?')
                ->setParameter(0, (string)$this->options['name'], 'string')
                ->setParameter(1, (bool)$this->options['active'], 'boolean')
                ->setParameter(2, (bool)$this->options['run_now'], 'boolean')
                ->where('id = ?')
                ->setParameter(3, (int)$this->options['id'], 'integer')
            ;
            $qb->execute();
            unset($qb);
        }else{
            throw new \Exception("Вы пытаетесь сохранить не существующую запись.", 1);
        }
    }

    /**
     * Сохраняем все опции в базе
     *
     * @return bool
     */
    private function saveOptions()
    {
        if (!$this->isId()) return false;

        foreach ($this->options as $key => $value) {
            if (!in_array($key, $this->columName)){
                // если запись из базы _Options
                $this->saveOption($key);
            }
        }
        return true;
    }

    /**
     * Сохраняем конкретную опцию вбазе данных
     *
     * @param  string $property Опция для сохранения
     * @return bool
     */
    private function saveOption(string $property)
    {
        if (empty($property)) return false;
        if (!$this->isId()) return false;
        # если такая опция существует
        if (isset($this->options[$property])){
            $value = $this->options[$property];
            $typePhp = gettype($value);
            if ($typePhp == 'double') $typePhp='float'; // dbal не поддерживает "double"
            $typeDb = \Doctrine\DBAL\Types\Type::getType($typePhp);
            // TODO: Доразобраться с записью типов в базу
            // r($typePhp, $typeDb);
            $qb = $this->conn->createQueryBuilder();
            // TODO: Добавить удаление пустых записей
            if ($this->isOptionSql($property)){
                $qb
                    ->update($this->tableName[1])
                    ->set('value', '?')
                    ->set('type', '?')
                    ->setParameter(0, $value, $typeDb)
                    ->setParameter(1, $typePhp, 'string')
                    ->where('name_id = ?')
                    ->andWhere('property = ?')
                    ->setParameter(2, (int)$this->options['id'], 'integer')
                    ->setParameter(3, $property, 'string')
                ;
            }else{
                $qb
                    ->insert($this->tableName[1])
                    ->setValue('property', '?')
                    ->setValue('value', '?')
                    ->setValue('type', '?')
                    ->setParameter(0, $property, 'string')
                    ->setParameter(1, $value, $typeDb)
                    ->setParameter(2, $typePhp, 'string')
                    ->setValue('name_id', '?')
                    ->setParameter(3, (int)$this->options['id'], 'integer')
                ;
            }
            // r($qb->getSql());
            $qb->execute();
            unset($qb);
        }else{
            return false;
        }
        return true;
    }

    /**
     * Загружаем опции по ID
     *
     * @param  int $id ID опций
     */
    private function load(int $id)
    {
        if (isset($id)){
            $this->options = []; // очищем все опции
            $this->options['id'] = $id;
        }else{
            throw new \Exception("Не указан id для загрузки.", 1);
        }
        # Проверяем существует ли запись в базе данных и загружаем
        if ($this->loadName()){
            # получаем и обединяем массивы с настройками
            $options = array_merge($this->options, $this->loadOptions());
            $this->options = $this->resolver->resolve($options);
            unset($options);
        }else{
            throw new \Exception("Такой записи нет в БД.", 1);
        }
    }

    /**
     * Alias load()
     *
     * @param  int $id ID опций
     */
    public function loadByID(int $id)
    {
        $this->load($id);
    }

    /**
     * Загрузка опции по её имени
     *
     * @param  string $name Имя опции
     * @param  bool $createIfNot Создать новую запись, если её ещё нет
     */
    public function loadByName(string $name, $createIfNot = false)
    {
        if (!isset($name)) throw new \Exception("не указан NAME записи для проверки", 1);
        $qb = $this->conn->createQueryBuilder();
        $qb ->select('id')
            ->from($this->tableName[0])
            ->where('name = ?')
            ->setParameter(0, $name, 'string')
        ;
        $rezult = $qb->execute();
        if ($rezult->rowCount() > 0){
            $id = (int) $rezult->fetch()['id'];
            $this->loadByID($id);
        }else{
            if ($createIfNot){
                $this->new(['name'=>(string)$name]);
            }else {
                throw new \Exception("Нет такого свойства.", 1);
            }
        }
        unset($qb, $rezult);
    }

    /**
     * Загружаем из БД опцию а если нет то возвращяем ошибку
     *
     * @return bool
     */
    private function loadName()
    {
        if (!$this->isId()) throw new \Exception("Нет ID для загрузки из БД.", 1);
        $qb = $this->conn->createQueryBuilder();
        $qb ->select('*') //'id', 'name'
            ->from($this->tableName[0])
            ->where('id = ?')
            ->setParameter(0, (int)$this->options['id'], 'integer')
        ;
        $result = $qb->execute(); // выполняем sql
        unset($qb);
        if ($result->rowCount() > 0){
            $options = $result->fetch();
            $this->options['id'] = (int) $options['id'];
            $this->options['name'] = (string) $options['name'];
            $this->options['active'] = (bool) $options['active'];
            $this->options['run_now'] = (bool) $options['run_now'];
            unset($options, $result);
            return true;
        }else{
            unset($result);
            return false;
        }
    }

    /**
     * Загружаем переменные опции Из БД
     *
     * @return array Сами переменные
     */
    private function loadOptions() : array
    {
        if (!$this->isId()) throw new \Exception("Нет ID для загрузки из БД.", 1);
        $qb = $this->conn->createQueryBuilder();
        $qb ->select('property', 'value', 'type')
            ->from($this->tableName[1])
            ->where('name_id = ?')
            ->setParameter(0, (int)$this->options['id'], 'integer')
        ;
        // r($qb->getSql());
        $result = $qb->execute();
        $options = [];
        while ($option = $result->fetch()) {
            # получаем тип переменной и преобразуем её
            $platform = $this->conn->getDatabasePlatform();
            $typeDb = \Doctrine\DBAL\Types\Type::getType($option['type']);
            $option['value'] = $typeDb->convertToPHPValue($option['value'], $platform);
            // r($option['value']);
            // exit;
            // settype($option['value'], $option['type']); // замененено на \Doctrine\DBAL\Types\Type
            $options[$option['property']] = $option['value'];
            if ($this->autoSetDefined){
                # Если в setDefined нет такого свойства, добавляем его
                if (!$this->resolver->isDefined($option['property'])){
                    # Допустимые Опции без значений по умолчанию
                    $this->resolver->setDefined($option['property']);
                    # Проверка типа
                    $this->resolver->setAllowedTypes($option['property'], $option['type']);
                    # пишем в лог
                    //syslog(LOG_WARNING, "Add resolver->setDefined('{$option['property']}') from DB options!");
                    printf("WARNING: Add resolver->setDefined('%s') from DB options!", $option['property']);
                }
            }
        }
        // r($this->options);
        unset($qb, $result);
        return $options;
    }

    /**
     * Удаляем опцию со всеми свойствами
     *
     */
    public function delete()
    {
        if (!$this->isId()) throw new \Exception("Не указан id для удаления.", 1);
        # очищаем таблицу с опциями
        $this->conn->delete($this->tableName[1], array('name_id' => $this->getId()));
        # Удаляем текушую миссию из базы
        $this->conn->delete($this->tableName[0], array('id' => $this->getId()));
        # уничтожаем ID
        unset($this->options['id']);
        $this->options = [];
    }

    /**
     * Удаление из БД отдельного свойства опции
     *
     * @param  string $property Имя свойства
     * @return bool Удалось ли
     */
    public function deleteOption(string $property = null)
    {
        // TODO: Добавить удаление из БД отдельного свойства опции
    }

    public function testSave($ob)
    {
        // TODO: Тут надо попробобвать реализовать setOptions->save()
    }

    /**
     * Установить новое значение свойства
     *
     * @param  string $property Свойство
     * @param  any $value Свойство
     */
    private function _setOption(string $property = null, $value = null)
    {
        if ($property == 'id') return false; // нельзя ставить этот параметр
        if ($property == 'name') {
            // if ($this->isNameSql($this->getName())) throw new \Exception("Error: Такое имя свойства уже существует.", 1);
            // TODO: Добавить проверку что такое имя уже существует в БД
        }
        if (!empty($property)){
            $tmpOptions = $this->options;
            $tmpOptions[$property] = $value;
            $this->options = $this->resolver->resolve($tmpOptions);
            // $this->options[$property] = $value;
            # Если АВТО-СОХРАНЕНИЕ
            if ($this->autoSave){
                $this->save();
            }
        }
    }

    private function _getOption(string $property)
    {
        if (empty($property)) return false;
        return $this->options[$property];
    }

    private function _isOption(string $property) : bool
    {
        if (empty($property)) return false;
        return isset($this->options[$property]);
    }

    public function toArray() : array
    {
        return $this->options;
    }

    /**
     * Получение всех всех свойств опции из массива
     *
     * @param  array $options Массив со свойствами
     * @param  bool  $megreOptions Обединять свойства или заменить новыми
     * @return bool Удалось ли создать или нет
     */
    public function fromArray(array $options = null, bool $megreOptions = false)
    {
        if (!isset($options)) return false;
        if ($megreOptions){
            # обединяем Новые свойства со старыми, чрез замену старых
            $options = array_merge($this->options, $options);
        }
        if (array_key_exists('id', $options)){ // если с массивом передан ID
            unset($options['id']); // удаляем ID из массива
        }
        if (!$this->isId()){ // если id ещё не задан, значить пытаются создать новую опцию
            return $this->new($options); // создаём новый
        }
        # добавляем новые свойства опции с сохранением ID
        $id = $this->options['id'];
        $this->options = $this->resolver->resolve($options);
        $this->options['id'] = $id;
        # Если АВТО-СОХРАНЕНИЕ
        if ($this->autoSave){
            $this->save();
        }
        return true;
    }

    public function __toString() : string
    {
        return $this->getName();
    }

    /**
     * Обработка методов setName И getName
     * https://www.php.net/manual/ru/language.oop5.overloading.php#object.call
     * https://habr.com/ru/post/228951/
     *
     * @param  string $name Имя метода
     * @param  mixed $args Значение переменной
     * @return mixed Содержимое переменной
     */
    public function __call($name, $args)
    {
        $property = lcfirst(substr($name, 3)); // только 3 знака
        if ('get' === substr($name, 0, 3)) {
            return $this->_isOption($property)
                ? $this->_getOption($property)
                : null;
        } elseif ('set' === substr($name, 0, 3)) {
            $value = (1 == count($args)) ? $args[0] : null;
            return $this->_setOption($property, $value);
        } elseif ('is' === substr($name, 0, 2)){
            $property = lcfirst(substr($name, 2)); // только 2 знака
            return $this->_isOption($property);
        }
    }

    public function test()
    {
        // return  $this->options;
    }
}

















//
