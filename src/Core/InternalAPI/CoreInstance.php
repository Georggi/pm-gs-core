<?php
namespace Core\InternalAPI;
#use Core\BaseFiles\BaseUpdater;
use Core\Loader;
use pocketmine\plugin\PluginBase;

abstract class CoreInstance extends PluginBase{
    /** @var Loader */
    private $core;

    public function onDisable(){
        //$this->getServer()->getScheduler()->scheduleAsyncTask(new BaseUpdater($this, $this->updaterName()));
    }

    /**
     * @return Loader
     */
    public function getCore(){
        if(!$this->core){
            $this->core = $this->getServer()->getPluginManager()->getPlugin("Core");
        }
        return $this->core;
    }

    /**
     * @return string
     */
    public abstract function updaterName();
}