<?php
namespace Core\InternalAPI;
#use Core\BaseFiles\BaseUpdater;
use Core\Loader;
use pocketmine\plugin\PluginBase;

abstract class CoreInstance extends PluginBase{
    public function onDisable(){
        //$this->getServer()->getScheduler()->scheduleAsyncTask(new BaseUpdater($this, $this->updaterName()));
    }

    /**
     * @return Loader
     */
    public abstract function getCore();

    /**
     * @return string
     */
    public abstract function updaterName();
}