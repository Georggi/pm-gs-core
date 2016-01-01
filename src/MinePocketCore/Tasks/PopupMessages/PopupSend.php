<?php
namespace MinePocketCore\Tasks\PopupMessages;

use MinePocketCore\BaseFiles\BaseTask;

class PopupSend extends BaseTask{
    public function onRun($currentTick){
        foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $p){
            $message = $this->getPlugin()->getPopupMessage();
            if(!$this->getPlugin()->isPlayerAuthenticated($p)){
                $message = "auth." . ($this->getPlugin()->isPlayerRegistered($p) ? "login" : "register") . ".popup";
            }
            $p->sendPopup((substr($message, 0, 1) === "%" ? "" : "%") . $message);
        }
    }
}