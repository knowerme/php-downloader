<?php

namespace App;

use App\MissionAll;
// use App\Mission;
use App\Download;

/**
 * Основная программа.
 * Выполняется при старте и выполняет все действия
 *
 * @author KnowerMe [local]
 * @date   2019-05-16
 */
class Start
{
    /**
     * Вводить информациюб о текущем действии
     * Или только ошибки
     *
     * @var bool
     */
    public $message = false;
    private $err = false;


    public function __construct()
    {
        // code...
    }

    /**
     * При запуске из браузера
     */
    public function runBrowser()
    {
        $this->run();
    }

    /**
     * При запуске из консоли
     */
    public function runConsole()
    {
        $this->run();
    }

    /**
     * Вывести сообщение
     *
     * @param  string $msg текст сообщения
     */
    private function msg(string $msg=null)
    {
        if ($this->err || $this->message){
            if (isset($msg))
                echo $msg;
        }
    }

    /**
     * Основная программа
     */
    private function run()
    {
        $missionAll = new MissionAll;
        $missions = $missionAll->fetchAll();
        // r($missions);
        foreach ($missions as $row) {
            // r($row['id']);
            // echo $row['id'].'<br>'."\r\n";
            if (!empty($row['id'])){ // id существует
                try {
                    # загрузаем mission
                    $mission = new Mission;
                    $mission->load( (int)$row['id'] );

                    # инициализируем класс сайта
                    $site = $mission->getSiteClass();

                    # запускаем основные действия
                    $download = new Download;
                    $download->loadMission($mission);
                    $download->loadSite($site);
                    $download->run();

                    # Проверяем ответ статус
                    switch ($download->status) {
                        case 'UPDATE':
                            $this->err = true;
                            $this->msg("[UPDATE] ");
                            break;
                        case 'ERROR AUTH':
                            $this->err = true;
                            $this->msg("[ERROR AUTH]  ");
                            break;
                        case 'NO UPDATE':
                            $this->msg("[-] ");
                            break;
                        default:
                            $this->err = true;
                            $this->msg("[???] ");
                            break;
                    }
                    $this->msg('"'.$mission->getName().'"' . "\r\n");

                } catch (\Exception $e) {
                    echo "ERROR: Проблемы при обработке mission id ("
                        .$row['id']
                        .") с ошибкой: "
                        .$e->getMessage()
                        ."\r\n";
                }
            }
        }
    }
}






//
