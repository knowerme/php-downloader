<?php

namespace App\Traits;

/**
 *
 */
trait Fantastic
{
    public $testTrait = "TEST";

    public function testTrait()
    {
        r($this->testTrait);
    }

    /**
     * Установка значений класса
     * Например: $obj->a = 1;
     * https://www.php.net/manual/ru/language.oop5.overloading.php#object.get
     * https://habr.com/ru/post/228951/
     *
     * @author KnowerMe [local]
     * @date   2019-04-26
     * @param  string $property Название переменной
     * @param  mixed $value Значение переменной
     */
    // public function __set($property, $value)
    // {
    //     if (property_exists($this, $property)) {
    //         $this->$property = $value;
    //     }
    // }

    /**
     * Получение значений класса
     * Пример: echo $obj->a
     * https://www.php.net/manual/ru/language.oop5.overloading.php#object.get
     * https://habr.com/ru/post/228951/
     *
     * @author KnowerMe [local]
     * @date   2019-04-26
     * @param  string $property Название переменной
     * @return mixed Значение переменной
     */
    // public function __get($property)
    // {
    //     if (property_exists($this, $property)) {
    //         return $this->$property;
    //     }
    // }
}









//
