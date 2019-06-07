<?php

namespace App;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Загрузка настроеэк проэкта
 */
class Config
{
    /**
     * Все настройки из байла
     *
     * @author KnowerMe [local]
     *
     * @var array
     */
    private $config = [];

    /**
     * Файл настроек по умолчанию
     *
     * @var string
     */
    private $filename = 'config.yaml';

    /**
     * путь до файла с настройками
     *
     * @var string
     */
    private $patch = __DIR__ . '/../';

    /**
     * создаём клас и определяем переменные
     *
     * @param  string  $filename Имя файла с настройками
     */
    function __construct(string $filename = null)
    {
        # Если передано нестандартное имя файла с настройками
        if (isset($filename)) $this->filename = $filename;

        $this->load();
    }

    /**
     * Загрузаем настройки из файла в массив
     *
     * @return bool Удалось или нет
     */
    private function load()
    {
        $file = $this->patch . $this->filename;
        try {
            $this->config = Yaml::parseFile($file);
        } catch (ParseException $exception) {
            // printf('Unable to parse the YAML string: %s', $exception->getMessage());
            die('Unable to parse the YAML string: ' . $exception->getMessage());
        }
    }

    /**
     * Возврящяет все доступные настройки в виде массива
     *
     * @return array Настройки
     */
    public function getAll() : array
    {
        return $this->config;
    }

    public function __call($name, $args)
    {
        $property = lcfirst(substr($name, 3)); // только 3 знака
        if ('get' === substr($name, 0, 3)) {
            return isset($this->config[$property])
                ? $this->config[$property]
                : null;
        } elseif ('is' === substr($name, 0, 2)){
            $property = lcfirst(substr($name, 2)); // только 2 знака
            return isset($this->config[$property]);
        }
    }
}
