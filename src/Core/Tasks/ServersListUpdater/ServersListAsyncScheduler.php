<?php
namespace Core\Tasks\ServersListUpdater;

use Core\InternalAPI\CoreTask;

class ServersListAsyncScheduler extends CoreTask{
    public function onRun($currentTick){
        $this->getPlugin()->updateAllServers();
    }
}