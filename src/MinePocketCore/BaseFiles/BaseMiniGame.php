<?php
namespace MinePocketCore\BaseFiles;

use MinePocketCore\Loader;
use MinePocketCore\InternalAPI\SuperPlayer;
use MinePocketCore\Tasks\Timers\GameEnd;
use MinePocketCore\Tasks\Timers\GameStart;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\tile\Sign;

abstract class BaseMiniGame{
    /** @var Loader */
    private $core;
    /** @var Level */
    private $level;
    /** @var null|Sign */
    private $arenaSign = null;
    /** @var int */
    private $maxPlayers;
    /** @var int */
    private $minPlayers;
    /** @var int */
    private $gameTime;
    /** @var int */
    private $currentRound = 1;
    /** @var int */
    private $rounds;
    /** @var bool */
    private $suddenDead;
    /** @var string */
    private $endMessage;
    /** @var bool */
    private $hasStarted = false;
    /** @var null|int */
    private $task = null;

    public function __construct(Loader $core, Level $level, Sign $arenaSign, $maxPlayers, $minPlayers, $gameTimeInMinutes, $rounds = 1, $suddenDead, $gameEndMessage = "to end this game"){
        $this->core = $core;
        $this->level = $level;
        $this->arenaSign = $arenaSign;
        $this->maxPlayers = $maxPlayers;
        $this->minPlayers = $minPlayers;
        $this->gameTime = $gameTimeInMinutes * 60;
        $this->rounds = $rounds;
        $this->suddenDead = $suddenDead;
        $this->endMessage = $gameEndMessage;
    }

    /**
     * @return Loader
     */
    public final function getCore(){
        return $this->core;
    }

    /**
     * @return null|Sign
     */
    public final function getSign(){
        return $this->arenaSign;
    }

    /**
     * @return int
     */
    public final function getMaxPlayers(){
        return $this->maxPlayers;
    }

    /**
     * @return int
     */
    public final function getMinPlayers(){
        return $this->minPlayers;
    }

    /**
     * @return int
     */
    public final function getGameTime(){
        return $this->gameTime;
    }

    /**
     * @return int
     */
    public final function getRoundsNumber(){
        return $this->rounds;
    }

    /**
     * @return int
     */
    public final function getCurrentRound(){
        return $this->currentRound;
    }

    /**
     * @return bool
     */
    public final function hasSuddenDead(){
        return $this->suddenDead;
    }

    /**
     * @return BaseMiniGame
     */
    public abstract function generateNewGame();

    public final function startGame(){
        $this->getCore()->getServer()->getScheduler()->cancelTask($this->task);
        $this->task = null;
        $this->getCore()->addNewGame($this->generateNewGame());
        $this->arenaSign = null;
        $this->onGameStart();
        $this->hasStarted = true;
        $this->getCore()->getServer()->getScheduler()->scheduleRepeatingTask(new GameEnd($this->getCore(), $this, $this->gameTime, $this->endMessage), 20); // Executed each second...
    }

    public abstract function onGameStart();

    //////////////////////////////////////////////////////////////////
    //  The following functions are called with the "GameEnd" task  //
    //////////////////////////////////////////////////////////////////

    /**
     * This function will allow games to make things while jumping players to a new round...
     * Well, if there are more than 1 round xD
     * If not, the "onSuddenDead" function will be called (See function's comment)
     */
    public function onNextRound(){}

    /**
     * How will this work?
     * Lets take an example of a game that needs a winner, like Survival Games...
     * When "onSuddenDead" is called, you can teleport all the players to an arena where they will fight until just 1 person is alive,
     * you can detect it with an event, and after it, call the "endGame" function to end normally :3
     */
    public function onSuddenDead(){}

    /**
     * @param bool $force
     */
    public final function endGame($force = false){
        if(!$force){
            $this->onGameEnd();
        }
        $this->getLevel()->unload(false);
        $this->getCore()->removeGame($this);
    }

    //////////////////////////////////////////////////////////////////

    public abstract function onGameEnd();

    /**
     * @return bool
     */
    public final function hasStarted(){
        return $this->hasStarted;
    }

    /**   _____              _
     *   / ____|            (_)
     *  | (___   ___ ___ ___ _  ___  _ __  ___
     *   \___ \ / _ / __/ __| |/ _ \| '_ \/ __|
     *   ____) |  __\__ \__ | | (_) | | | \__ \
     *  |_____/ \___|___|___|_|\___/|_| |_|___/
     */

    private $sessions = [];

    /**
     * @return BaseSession
     */
    public abstract function generateSession(SuperPlayer $player);

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function addPlayer(SuperPlayer $player){
        if($this->hasStarted() || count($this->getLevel()->getPlayers()) + 1 > $this->getMaxPlayers()){
            return false;
        }
        $session = $this->generateSession($player);
        $this->sessions[spl_object_hash($player)] = $session;
        if(count($this->getLevel()->getPlayers()) === $this->getMinPlayers()){
            $task = $this->getCore()->getServer()->getScheduler()->scheduleRepeatingTask(new GameStart($this->getCore(), $this), 20); // Executed each second...
            $this->task = $task->getTaskId();
        }
        if(count($this->getLevel()->getPlayers()) === $this->getMaxPlayers() && !$this->hasStarted()){ // just to be sure... double check if the game haven't started xD
            $this->startGame();
        }
        $this->onPlayerJoin($player);
        return true;
    }

    /**
     * This function should teleport the player to the arena, set custom things, etc...
     *
     * @param SuperPlayer $player
     */
    public abstract function onPlayerJoin(SuperPlayer $player);

    /**
     * @param SuperPlayer $player
     */
    public final function removePlayer(SuperPlayer $player){
        unset($this->sessions[spl_object_hash($player)]);
    }

    /**
     * @param SuperPlayer $player
     * @return bool|BaseSession
     */
    public function getSession(SuperPlayer $player){
        if(!isset($this->sessions[spl_object_hash($player)])){
            return false;
        }
        return $this->sessions[spl_object_hash($player)];
    }

    /**
     * @return BaseSession[]
     */
    public function getAllSessions(){
        return array_values($this->sessions);
    }

    /**  _____  _
     *  |  __ \| |
     *  | |__) | | __ _ _   _  ___ _ __ ___
     *  |  ___/| |/ _` | | | |/ _ | '__/ __|
     *  | |    | | (_| | |_| |  __| |  \__ \
     *  |_|    |_|\__,_|\__, |\___|_|  |___/
     *                   __/ |
     *                  |___/
     */

    /**
     * @return bool|SuperPlayer[]
     */
    public final function getPlayers(){
        /** @var SuperPlayer[] $players */
        $players = [];
        foreach($this->level->getPlayers() as $p){
            if(($s = $this->getSession($p)) !== false && $s->isActive()){
                $players[] = $p;
            }
        }
        return (count($players) < 1 ? false : $players);
    }

    /**
     * @param string $message
     */
    public final function broadcastMessage($message){
        foreach($this->getPlayers() as $p){
            $p->sendMessage($message);
        }
    }

    /**  _                    _
     *  | |                  | |
     *  | |     _____   _____| |
     *  | |    / _ \ \ / / _ | |
     *  | |___|  __/\ V |  __| |
     *  |______\___| \_/ \___|_|
     */

    /**
     * @return Level
     */
    public final function getLevel(){
        return $this->level;
    }

    /** @var Location[] */
    private $respawnpoints = [];

    /**
     * @return Location[]
     */
    public final function getRespawnPoints(){
        return $this->respawnpoints;
    }

    public final function getRandomSpawnPoint(){
        return array_rand($this->respawnpoints);
    }

    /**
     * @param Location[] $location
     */
    public final function addRespawnPoints(array $location){
        $this->respawnpoints = $location;
    }

    /**  ______               _
     *  |  ____|             | |
     *  | |____   _____ _ __ | |_ ___
     *  |  __\ \ / / _ | '_ \| __/ __|
     *  | |___\ V |  __| | | | |_\__ \
     *  |______\_/ \___|_| |_|\__|___/
     */

    /**
     * @param PlayerInteractEvent $event
     */
    public function  onPlayerInteract(PlayerInteractEvent $event){}

    /**
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event){}

    /**
     * @param EntityMotionEvent $event
     */
    public function onPlayerMotionChange(EntityMotionEvent $event){}

    /**
     * @param EntityDamageEvent $event
     */
    public function onEntityDamage(EntityDamageEvent $event){}

    /**
     * @param PlayerItemConsumeEvent $event
     */
    public function onItemConsume(PlayerItemConsumeEvent $event){}

    /**
     * @param PlayerDeathEvent $event
     */
    public function onPlayerDeath(PlayerDeathEvent $event){}

    /**
     * @param PlayerRespawnEvent $event
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event){}

    /**
     * @param PlayerQuitEvent $event
     */
    public function onPlayerQuit(PlayerQuitEvent $event){}
}