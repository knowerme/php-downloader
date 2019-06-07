<?php

namespace App;

use Doctrine\DBAL\DriverManager;
// use Doctrine\DBAL\Schema\Schema;
// use Doctrine\DBAL\Schema\Table;
// use Doctrine\DBAL\Configuration;

/**
 * Получение записей mission для обработки
 *
 */
class MissionAll
{

    /**
     * @var array
     */
    private $connectionParams = [];

    /**
     * @var DriverManager
     */
    private $conn;

    /**
     * @var array
     */
    private $tableName = ['mission_name', 'mission_options'];


    public function __construct()
    {
        # загружаем настройки базы данных
        $this->loadConfig();

        // $config = new Configuration();
        $this->conn = DriverManager::getConnection($this->connectionParams);
    }

    /**
     * Загружаем настройки из файла настроек
     *
     */
    private function loadConfig()
    {
        $config = new \App\Config();
        $this->connectionParams = $config->getDb();
    }

    // /**
    //  * Получает одну MISSION из базы
    //  *
    //  * @return array Ассецеативный массив
    //  */
    // public function fetch(){
    //     return $this->conn->fetchAssoc('SELECT * FROM '.$this->tableName[0].' WHERE active=1');
    // }

    /**
     * Вывести все записи
     *
     * @return array Массив всех закачек
     */
    public function fetchAll(){
        return $this->conn->fetchAll('SELECT * FROM '.$this->tableName[0].' WHERE active=1');
    }

    /**
     * Вывести один элемент по ЙД
     *
     * @param  int $id id записи
     * @return array Ассецеативный массив
     */
    public function fetchId(int $id){
        return $this->conn->fetchAssoc('SELECT * FROM ' . $this->tableName[0] .' WHERE id = ?', [$id]);
    }

    public function test(){
        return $this->conn;
    }

}
