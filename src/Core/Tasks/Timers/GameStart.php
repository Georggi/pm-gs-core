<?php
namespace Core\Tasks\Timers;

use Core\BaseFiles\BaseMiniGame;
use Core\BaseFiles\BaseTask;
use Core\Loader;

class GameStart extends BaseTask{
    /** @var BaseMiniGame */
    private $game;
    /** @var int */
    private $counter = 300;

    public function __construct(Loader $core, BaseMiniGame $game){
        parent::__construct($core);
        $this->game = $game;
    }

    public function onRun($currentTick){
        if($this->counter < 1){
            $this->game->startGame();
        }
        if($this->counter % 60 === 0){
            $this->game->broadcastMessage("The game starts in " . ($this->counter / 60) . " minutes...");
        }elseif($this->counter === 30 || $this->counter <= 10){
            $this->game->broadcastMessage("The game starts in " . $this->counter . " seconds...");
        }
        $this->counter--;
    }
}