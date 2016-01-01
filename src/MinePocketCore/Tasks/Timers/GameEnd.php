<?php
namespace MinePocketCore\Tasks\Timers;

use MinePocketCore\BaseFiles\BaseMiniGame;
use MinePocketCore\BaseFiles\BaseTask;
use MinePocketCore\Loader;

class GameEnd extends BaseTask{
    /** @var BaseMiniGame */
    private $game;
    /** @var int */
    private $time;
    /** @var string */
    private $endMessage;

    public function __construct(Loader $core, BaseMiniGame $game, $time, $endMessage){
        parent::__construct($core);
        $this->game = $game;
        $this->time = $time;
        $this->endMessage = $endMessage;
    }

    public function onRun($currentTick){
        if($this->time < 1){
            if($this->game->getRoundsNumber() > 1){
                $this->game->onNextRound();
            }elseif($this->game->hasSuddenDead()){
                $this->game->onSuddenDead();
            }else{
                $this->game->endGame();
            }
        }
        if($this->time % 60 === 0){
            $this->game->broadcastMessage(($this->time / 60) . " minutes " . $this->endMessage . "...");
        }elseif($this->time === 30 || $this->time <= 10){
            $this->game->broadcastMessage($this->time . " seconds " . $this->endMessage . "...");
        }
        $this->time--;
    }
}