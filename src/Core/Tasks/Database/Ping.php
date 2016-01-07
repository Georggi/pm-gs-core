<?php
namespace Core\Tasks\Database;

use Core\InternalAPI\CoreTask;

class Ping extends CoreTask{
    public function onRun($currentTick){
        if($this->getPlugin()->database === null || !$this->getPlugin()->database->ping()){
            $this->getPlugin()->getServer()->getLogger()->info("Database ping failed! Reconnecting...");
            $this->getPlugin()->database = new \mysqli("192.99.152.145", "developm_develop", "L3ZqZm+JMNKx", "developm_test", 3306);;
        }
    }
}