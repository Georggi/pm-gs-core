<?php
namespace Core\InternalAPI\Events\Disguise;

use Core\InternalAPI\SuperPlayer;

class DisguisePauseEvent extends DisguiseStopEvent{
    /** @var bool */
    private $spawnPlayer;

    /**
     * @param SuperPlayer $player
     * @param bool $spawnPlayer
     */
    public function __construct(SuperPlayer $player, $spawnPlayer){
        parent::__construct($player);
        $this->spawnPlayer = $spawnPlayer;
    }

    /**
     * @return bool
     */
    public function goingToSpawnPlayer(){
        return $this->spawnPlayer;
    }

    /**
     * @param bool $state
     */
    public function spawnPlayer($state){
        if(is_bool($state)){
            $this->spawnPlayer = $state;
        }
    }
}