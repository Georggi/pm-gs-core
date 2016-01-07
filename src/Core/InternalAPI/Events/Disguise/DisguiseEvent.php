<?php
namespace Core\InternalAPI\Events\Disguise;

use Core\InternalAPI\SuperPlayer;
use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;

class DisguiseEvent extends PluginEvent implements Cancellable{
    public static $handlerList = null;

    /** @var SuperPlayer */
    private $player;

    /**
     * @param SuperPlayer $player
     */
    public function __construct(SuperPlayer $player){
        parent::__construct($player->getCore());
        $this->player = $player;
    }

    /**
     * @return SuperPlayer
     */
    public function getPlayer(){
        return $this->player;
    }
}