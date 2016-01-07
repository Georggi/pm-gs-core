<?php
namespace Core\InternalAPI\Events\Disguise;

use Core\InternalAPI\SuperPlayer;

class DisguiseStartEvent extends DisguiseEvent{
    /** @var int */
    private $entityID;

    /**
     * @param SuperPlayer $player
     * @param int $entityID
     */
    public function __construct(SuperPlayer $player, $entityID){
        parent::__construct($player);
        $this->entityID = $entityID;
    }

    /**
     * @return int
     */
    public function getEntityID(){
        return $this->entityID;
    }

    /**
     * @param int $id
     */
    public function setEntityID($id){
        if(is_int($id)){
            $this->entityID = $id;
        }
    }
}