<?php
namespace Core\Tasks\PopupMessages;

use Core\InternalAPI\CoreTask;
use Core\InternalAPI\SuperPlayer;

class PopupSend extends CoreTask{
    public function onRun($currentTick){
        foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $p){
            /** @var SuperPlayer $p */
            $message = $this->getPlugin()->getPopupMessage();
            if(!$p->isAuthenticated()){
                $message = "auth." . ($this->getPlugin()->isPlayerRegistered($p) ? "login" : "register") . ".popup";
            }
            $p->sendPopup((substr($message, 0, 1) === "%" ? "" : "%") . $message);
        }
    }
}