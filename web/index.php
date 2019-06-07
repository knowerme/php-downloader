<?php
require __DIR__ . '/../vendor/autoload.php';

# включаем регистрацию ошибок
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

use App\Start;

// $start = new Start;

echo "<pre>\r\n";
// $start->runBrowser();

echo "TEST";


//
