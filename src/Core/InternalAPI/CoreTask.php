<?php
namespace Core\InternalAPI;

use Core\BaseFiles\BaseTask;
use Core\Loader;

abstract class CoreTask extends BaseTask{
    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        parent::__construct($plugin);
    }

    /**
     * @return Loader
     */
    public function getPlugin(){
        return parent::getPlugin();
    }
}