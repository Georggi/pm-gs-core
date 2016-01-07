<?php
namespace Core\InternalAPI\Events;

use Core\BaseFiles\BaseSession;
use Core\InternalAPI\CoreInstance;
use pocketmine\event\plugin\PluginEvent;

class SuperPlayerCreateEvent extends PluginEvent{
    public static $handlerList = null;

    /** @var CoreInstance */
    private $core;
    /** @var BaseSession::class|null */
    private $sessionClass;

    /**
     * @param CoreInstance $plugin
     */
    public function __construct(CoreInstance $plugin){
        parent::__construct($plugin);
        $this->core = $plugin;
        $this->sessionClass = null;
    }

    /**
     * @return CoreInstance
     */
    public function getCore(){
        return $this->core;
    }

    /**
     * @param CoreInstance $core
     */
    public function setCore(CoreInstance $core){
        $this->core = $core;
    }

    /**
     * @return BaseSession::class
     */
    public function getSessionClass(){
        return $this->sessionClass;
    }

    /**
     * @param BaseSession::class $class
     */
    public function setSessionClass($class){
        if(!is_a($class, BaseSession::class, true)){
            throw new \RuntimeException("Class " . $class . " must extend " . BaseSession::class);
        }
        $this->sessionClass = $class;
    }
}