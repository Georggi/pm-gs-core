<?php
namespace MinePocketCore\Tasks;

use MinePocketCore\BaseFiles\BaseTask;
use MinePocketCore\Loader;

class DatabasePing extends BaseTask{
    public function __construct(Loader $plugin){
        parent::__construct($plugin);
    }

    public function onRun($currentTick){
        if(!$this->getPlugin()->database->ping()){
            $this->getPlugin()->getServer()->getLogger()->info("Database ping failed! Reconnecting...");
            $this->getPlugin()->database->close();
            $this->getPlugin()->database = new \mysqli("192.99.152.145", "developm_develop", "L3ZqZm+JMNKx", "developm_test", 3306);
        }
    }
}