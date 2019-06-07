<?php

namespace App;

use App\Sites\Site;
use App\Mission;
use App\Files\File;

/**
 * Основной класс для скачивания чего либо с сайтов
 *
 * @author KnowerMe [local]
 * @date   2019-05-16
 */
class Download
{
    protected $site;
    protected $mission;
    protected $file;
    public $status;

    public function __construct()
    {
        // code...
    }

    public function loadMission(Mission $mission=null)
    {
        if (empty($mission)) return false;
        $this->mission = $mission;
        return true;
    }

    public function loadSite(Site $site=null)
    {
        if (empty($site)) return false;
        $this->site = $site;
        return true;
    }

    public function run()
    {
        if ((int)$this->site->getError() > 5){ // если много ошибок
            throw new \Exception("Превышение количества ошибок на сайте", 0);
            // return false;
        }

        $this->site->loadMission($this->mission);
        $this->site->preRun();
        $this->status = $this->site->Run();
        $this->site->postRun();
        if ($this->mission->getType() == 'download'){
            $this->runDownload();
        }
    }

    public function runDownload()
    {
        $this->file = $this->site->finish();
        if ($this->file === false) return false;
        $this->file->save();
    }


    public function __sleep()
    {
        // return array('site');
        return [];
    }

    public function __wakeup()
    {
        $this->__construct();
    }

    public function __toString() : string
    {
        if (empty($this->site)){
            return "Empty class Downloader";
        }else {
            return 'Class Downloader from: ' . $this->site->__toString();
        }
    }
}










//
