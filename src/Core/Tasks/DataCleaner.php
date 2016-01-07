<?php
namespace Core\Tasks;

use Core\InternalAPI\CoreTask;
use Core\Loader;

class DataCleaner extends CoreTask{
    public function __construct(Loader $plugin){
        parent::__construct($plugin);
    }

    public function onRun($currentTick){
        foreach(scandir($dir = $this->getPlugin()->getServer()->getDataPath() . "players" . DIRECTORY_SEPARATOR) as $f){
            if(substr($f, -4) === ".dat"){
                $delete = true;
                foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $p){
                    if(substr($f, 0, (strlen($f) - 4)) === strtolower($p->getName())){
                        $delete = false;
                        break;
                    }
                }
                if($delete === true){
                    unlink($dir . $f);
                }
            }
        }
    }
}