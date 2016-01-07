<?php
namespace Core\InternalAPI\Events\Disguise;

use Core\InternalAPI\SuperPlayer;

class DisguiseStopEvent extends DisguiseEvent{
    /**
     * @param SuperPlayer $player
     */
    public function __construct(SuperPlayer $player){
        parent::__construct($player);
    }
}