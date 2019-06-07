<?php
# https://packagist.org/packages/kslr/transmission-php

require __DIR__ . '/../vendor/autoload.php';

use Transmission\Client;
use Transmission\Transmission;

# подгружаем настройки
$config = new \App\Config;
$transmission_config = $config->getTransmission();

# устанавливаем соединение
$client = new Client(
    $transmission_config['transmission_url'],
    $transmission_config['transmission_port'],
    $transmission_config['transmission_rpc_bind_address']
);
$client->authenticate(
    $transmission_config['username'],
    $transmission_config['password']
);
$transmission = new Transmission();
$transmission->setClient($client);



// Getting all the torrents currently in the download queue
$torrents = $transmission->all();
// $torrent  = $transmission->get(1);

// Adding a torrent to the download queue
// $torrent = $transmission->add(/* path to torrent */, false, /* path to dir Downloading */);


r($torrents);



echo "Downloading to: {$transmission->getSession()->getDownloadDir()}<br>\r\n";
foreach ($torrents as $torrent) {
    echo "{$torrent->getName()}";

    if ($torrent->isFinished()) {
        echo ": done<br>\r\n";
    } else {
        if ($torrent->isDownloading()) {
            echo ": {$torrent->getPercentDone()}% ";
            echo "(eta: ". gmdate("H:i:s", $torrent->getEta()) .")<br>\r\n";
        } else {
            echo ": paused<br>\r\n";
        }
    }
}
