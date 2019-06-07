<?php

namespace App\Files;

use Transmission\Transmission;
use Transmission\Client;

/**
 * Абстрактный клас для классов хранения разных типов файлов
 *
 * @author KnowerMe [local]
 * @date   2019-05-23
 */
class TorrentFile extends File
{
    protected $fileName = 'tmp.torrent';
    protected $patch = __DIR__ . '/../../db/files/torrent/';
    protected $resource;
    private $transmission = [];
    public $torrent;

    public function __construct($fileName)
    {
        parent::__construct($fileName);
    }

    public function getResource()
    {
        $file = $this->patch . $this->fileName;
        $this->resource = fopen($file, 'w');
        return $this->resource;
    }

    public function loadConfig()
    {
        $config = new \App\Config();
        if ($config->isTransmission()){
            $this->transmission = $config->getTransmission();
        }
    }

    public function save()
    {
        return $this->toTransmission();
    }

    protected function toTransmission()
    {
        if (empty($this->transmission)) return false;
        // r("ПЕРЕДАЁМ в transmission");

        $client = new Client(
            $this->transmission['transmission_url'],
            $this->transmission['transmission_port'],
            $this->transmission['transmission_rpc_bind_address']
        );
        $client->authenticate(
            $this->transmission['username'],
            $this->transmission['password']
        );
        $transmission = new Transmission();
        $transmission->setClient($client);

        // $file = $this->patch . $this->fileName;

        # base64_encode
        $content64 = base64_encode($this->getContentFile());

        try {
            // Adding a torrent to the download queue
            $this->torrent = $transmission->add($content64, true);
        } catch (\RuntimeException $e) {
            echo "ERROR: TorrentFile '{$this->fileName}' скачен, но не передан в transmission. Причина: ". $e->getMessage() . "\r\n";
            return false;
        }

        return $this->torrent;
    }

    // public function convertToUtf8()
    // {
    //     $content = $this->getContentFile();
    //     $content8 = mb_convert_encoding($content, "UTF-8", "Windows-1251");
    //     $newFile = $this->patch . 'new_' . $this->fileName;
    //     \file_put_contents($newFile, $content8);
    // }

    public function __sleep()
    {
        // return array('site');
        return ['fileName','torrent'];
    }
}





//
