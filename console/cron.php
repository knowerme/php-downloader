#!/usr/local/bin/php
<?php
require __DIR__ . '/../vendor/autoload.php';

# включаем регистрацию ошибок
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PlainTextHandler);
$whoops->register();

use App\Start;

$start = new Start;

$start->runConsole();



?>
