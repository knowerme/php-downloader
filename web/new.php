<?php
require __DIR__ . '/../vendor/autoload.php';

# включаем регистрацию ошибок
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

use App\Mission;
// use App\Download;
use App\Sites\Rutracker;

// r($_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
// r($_SERVER);
// exit;

$status = $_GET['status'];

if (!empty($_POST['sendFormHidden'])){
    $mission = new Mission;

    // $mission->load(2);
    // $mission->delete();
    // // https://rutracker.net/forum/viewtopic.php?t=5734623

    if (!empty($_POST['url'])
        && !empty($_POST['name'])
        && !empty($_POST['type'])
        && !empty($_POST['site'])
    ){
        $mission->new([
            'name' => $_POST['name'],
            'url'  => $_POST['url'],
            'type' => $_POST['type'],
        ]);
        if ($_POST['site'] == 'rutracker'){
            $site = new Rutracker;
            $mission->setSiteClass($site);
        }
        $status = $mission->getName() . " успешно создан";
    }

    // unset($mission);
    # перенапроавление на новую страницу
    $header_url = $_SERVER['SCRIPT_NAME'] . '?status='.$status;
    header('Location: '.$header_url);
}


?><!doctype html>
<html lang="ru">
  <head>
    <title>Добавить новую Mission</title>
    <meta charset="utf-8">
  </head>
  <body>
    <div class="">
        <span><?php echo $status; ?></span>
    </div>
    <br><br>
    <div class="">
        <span>Добавить новую запись: </span>
        <br><br>
        <form class="" action="" method="post">
            <input type="hidden" name="sendFormHidden" value="1">
            <input type="hidden" name="type" value="download">
            <input type="hidden" name="site" value="rutracker">

            <span>URL: </span>
            <input type="text" name="url" value="">
            <br>
            <span>Название: </span>
            <input type="text" name="name" value="">
            <br>
            <br>
            <input type="submit" name="sendForm" value="Добавить новый">
        </form>
    </div>
  </body>
</html>
