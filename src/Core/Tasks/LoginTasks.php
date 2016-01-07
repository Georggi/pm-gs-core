<?php
namespace Core\Tasks;

use Core\InternalAPI\CoreTask;
use Core\Loader;
use Core\InternalAPI\SuperPlayer;

class LoginTasks extends CoreTask{
    /**@var SuperPlayer */
    public $player;

    /** @var int */
    private $counter = 11;

    /** @var bool */
    private $isRegistered;

    public function __construct(Loader $plugin, SuperPlayer $player, $isRegistered){
        parent::__construct($plugin);
        $this->player = $player;
        $this->isRegistered = $isRegistered;
    }

    public function onRun($currentTick){
        if($this->getPlugin()->getServer()->getPlayerExact($this->player->getName()) === null){
            $this->getPlugin()->closePlayer($this->player);
        }
        $this->getPlugin()->getLogger()->debug("Run login task");
        if($this->counter < 1){
            $this->player->kick("%kick.notlogged");
            return;
        }
        if($this->counter < 11 && is_int($l = $this->getPlugin()->getLastTyping($this->player)) && $l > 600){
            if($this->isRegistered){
                $message = "%auth.login.join";
            }elseif(is_int($this->getPlugin()->getNextRegistrationStep($this->player))){
                $message = "%auth.register.steps.";
                if(($c = $this->getPlugin()->getNextRegistrationStep($this->player)) > 0 && $c < 4){
                    $message .= (string) $c;
                }else{
                    $message .= "0";
                }
            }else{
                $this->getPlugin()->getServer()->getScheduler()->cancelTask($this->getTaskId());
                return;
            }
            $this->player->sendMessage($message);
        }
        $this->counter--;
    }
}