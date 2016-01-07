<?php
namespace Core\BaseFiles;

use Core\InternalAPI\CoreInstance;
use pocketmine\scheduler\PluginTask;

abstract class BaseTask extends PluginTask{
    /** @var CoreInstance */
    private $plugin;

    public function __construct(CoreInstance $plugin){
        parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    /**
     * @return CoreInstance
     */
    public function getPlugin(){
        return $this->plugin;
    }
}