<?php
namespace Core\BaseFiles;

use Core\InternalAPI\SuperPlayer;

class BaseSession{
    /** @var SuperPlayer */
    private $player;
    /** @var BaseMiniGame */
    private $game;

    public function __construct(SuperPlayer $player, BaseMiniGame $game){
        $this->player = $player;
        $this->game = $game;
    }

    /**
     * @return SuperPlayer
     */
    public final function getPlayer(){
        return $this->player;
    }

    /**
     * @return BaseMiniGame
     */
    public function getGame(){
        return $this->game;
    }

    /** @var bool */
    private $isActive;

    /**
     * @return bool
     */
    public final function isActive(){
        return $this->isActive;
    }

    /**
     * @param bool $bool
     */
    public final function setActive($bool){
        $this->isActive = $bool;
    }

    public function onClose(){}
}