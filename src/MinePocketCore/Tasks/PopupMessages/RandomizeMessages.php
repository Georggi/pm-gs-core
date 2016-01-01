<?php
namespace Core\Tasks\PopupMessages;

use Core\BaseFiles\BaseTask;

class RandomizeMessages extends BaseTask{
    /** @var int|null */
    private $maxCount = null;

    /** @var int */
    private $counter = 0;

    public function onRun($currentTick){
        if($this->maxCount === null){
            $this->maxCount = count($this->getPlugin()->getLanguagesAPI()->getArray("popups", "english"));
        }
        if($this->counter >= $this->maxCount){
            $this->counter = 0;
        }
        $this->getPlugin()->setPopupMessage("%popups." . $this->counter);
        $this->counter++;
    }
}