<?php
namespace Core\BaseFiles;

use Core\InternalAPI\SuperPlayer;
use Core\Loader;

abstract class BaseSession{
    /** @var SuperPlayer */
    private $player;

    /**
     * @param SuperPlayer $player
     */
    public function __construct(SuperPlayer $player){
        $this->player = $player;
    }

    /**
     * @return Loader
     */
    public function getCore(){
        return $this->getPlayer()->getCore();
    }

    /**
     * @return SuperPlayer
     */
    public function getPlayer(){
        return $this->player;
    }
}