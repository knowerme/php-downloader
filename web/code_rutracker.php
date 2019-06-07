<?php
require __DIR__ . '/../vendor/autoload.php';

# включаем регистрацию ошибок
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

use App\Sites\Rutracker;

// r($_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']);
// r($_SERVER);
// exit;

// $mission = new Mission;
// $mission->load(1);


$site = new Rutracker;
// $site->loadMission($mission);
// echo "<img src='{$site->showImg()}' class='img-fluid pull-xs-left'>";
// $site->putCode('bb6');

if (!empty($_POST['codeForm'])){
    $site->putCode( (string)$_POST['codeForm'] );
    header('Location: '.$_SERVER['SCRIPT_NAME']);
}


?>
<!doctype html>
<html lang="ru">
  <head>
    <title>Ввод распознанного кода</title>
    <meta charset="utf-8">
  </head>
  <body>
    <?php if ($site->getCodeStatus() == 'required'){ ?>
        <img src='<?php echo $site->showImg(); ?>' class='img-fluid pull-xs-left'>
        <br>
        <div class="">
            <form class="" action="" method="post">
                <input type="text" name="codeForm" value="">
                <input type="submit" name="sendForm" value="Отправить код ">
            </form>
        </div>
    <?php }else{ ?>
        <div class="">
            <h1>Нечего распозновать</h1>
        </div>
    <?php } ?>
  </body>
</html>
