<?php

namespace App\Files;

/**
 * Абстрактный клас для классов хранения разных типов файлов
 *
 * @author KnowerMe [local]
 * @date   2019-05-23
 */
abstract class File
{
    protected $fileName = 'file.txt';
    protected $patch = __DIR__ . '/../../db/files/';

    public function __construct($fileName=null)
    {
        if (!empty($fileName)) $this->fileName = $fileName;

        $this->loadConfig();
    }

    public function rename($newName=null)
    {
        if (empty($newName)) return false;

        $file = $this->patch . $this->fileName;
        $newFile = $this->patch . $newName;
        if (rename($file, $newFile)) {
            $this->fileName = $newName;
        }
    }

    public function delete()
    {
        $file = $this->patch . $this->fileName;
        return unlink($file);
    }

    public function getFileName()
    {
        // $file = $this->patch . $this->fileName;
        $file = $this->fileName;
        return $file;
    }

    public function getFullFileName()
    {
        $file = $this->patch . $this->fileName;
        // $file = $this->fileName;
        return $file;
    }

    public function getContentFile()
    {
        $file = $this->patch . $this->fileName;
        $contentFile = \file_get_contents($file);

        return $contentFile;
    }

    abstract public function save();

    abstract public function loadConfig();

    public function __sleep()
    {
        return ['fileName'];
    }

    public function __wakeup()
    {
        $this->__construct($this->fileName);
    }

    public function __destroy()
    {
        //
    }
}














//
