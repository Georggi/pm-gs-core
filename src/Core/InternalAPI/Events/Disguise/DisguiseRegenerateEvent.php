<?php
namespace Core\InternalAPI\Events\Disguise;

use Core\InternalAPI\SuperPlayer;

class DisguiseRegenerateEvent extends DisguiseStartEvent{
    /** @var int */
    private $currentID;
    /** @var int */
    private $newID;

    /**
     * @param SuperPlayer $player
     * @param int $currentEntityID
     * @param int $newEntityID
     */
    public function __construct(SuperPlayer $player, $currentEntityID, $newEntityID){
        parent::__construct($player, $currentEntityID);
        $this->currentID = $currentEntityID;
        $this->newID = $newEntityID;
    }

    /**
     * @return int
     */
    public function getNewEntityID(){
        return $this->newID;
    }

    /**
     * @param int $id
     */
    public function setEntityID($id){
        if(is_int($id)){
            $this->newID = $id;
        }
    }
}