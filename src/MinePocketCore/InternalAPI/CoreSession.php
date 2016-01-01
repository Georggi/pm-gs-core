<?php
namespace MinePocketCore\InternalAPI;


use MinePocketCore\Loader;
use MinePocketCore\Tasks\LoginTasks;
use pocketmine\permission\PermissionAttachment;

class CoreSession{
    /** @var Loader */
    private $core;
    /** @var SuperPlayer */
    private $player;

    public function __construct(Loader $core, SuperPlayer $player){
        $this->core = $core;
        $this->player = $player;
    }

    /**
     * @return Loader
     */
    public function getCore(){
        return $this->core;
    }

    /**
     * @return SuperPlayer
     */
    public function getPlayer(){
        return $this->player;
    }

    /**                _   _
     *      /\        | | | |
     *     /  \  _   _| |_| |__
     *    / /\ \| | | | __| '_ \
     *   / ____ | |_| | |_| | | |
     *  /_/    \_\__,_|\__|_| |_|
     */

    /** @var bool */
    private $authenticated = false;

    public function authenticate(){
        $this->authenticated = true;
        $this->getCore()->getServer()->getScheduler()->cancelTask($this->getTaskID());
        $this->getPlayer()->removeAttachment($this->getPermissionAttachment());
        $this->getCore()->registerPlayerToDatabase($this->getPlayer());
        // Show the player... To itself...
        $this->getPlayer()->setDataFlag(SuperPlayer::DATA_FLAGS, SuperPlayer::DATA_FLAG_INVISIBLE, false);
        $this->getPlayer()->setDataProperty(SuperPlayer::DATA_SHOW_NAMETAG, SuperPlayer::DATA_TYPE_BYTE, 1);
        foreach($this->getCore()->getServer()->getOnlinePlayers() as $p){
            $p->showPlayer($this->getPlayer()); // Show to other players...
        }
        // TODO Set player inventory to custom one when loggin
    }

    public function deauthenticate(){
        $this->authenticated = false;
        $permissions = [];
        foreach($this->getCore()->getServer()->getPluginManager()->getPermissions() as $perm){
            $permissions[$perm->getName()] = false;
        }
        $permissions["minepocket.login"] = true;
        $permissions["minepocket.register"] = true;
        uksort($permissions, [self::class, "orderPermissionsCallback"]);
        $attachment = $this->getPlayer()->addAttachment($this->getCore());
        $attachment->setPermissions($permissions);
        $this->setPermissionAttachment($attachment);
        $task = $this->getCore()->getServer()->getScheduler()->scheduleRepeatingTask(new LoginTasks($this->getCore(), $this->getPlayer(), $this->getCore()->isPlayerRegistered($this->getPlayer())), 600);
        $this->setTaskID($task->getTaskId());
        $this->setLastTyping(time());

        // Hide the player... To itself...
        $this->getPlayer()->setDataFlag(SuperPlayer::DATA_FLAGS, SuperPlayer::DATA_FLAG_INVISIBLE, true);
        $this->getPlayer()->setDataProperty(SuperPlayer::DATA_SHOW_NAMETAG, SuperPlayer::DATA_TYPE_BYTE, 0);
        foreach($this->getCore()->getServer()->getOnlinePlayers() as $p){
            $p->hidePlayer($this->getPlayer()); // Hide to other players...
        }
    }

    ////////////////
    public static function orderPermissionsCallback($perm1, $perm2){
        if(self::isChild($perm1, $perm2)){
            return -1;
        }elseif(self::isChild($perm2, $perm1)){
            return 1;
        }else{
            return 0;
        }
    }
    public static function isChild($perm, $name){
        $perm = explode(".", $perm);
        $name = explode(".", $name);
        foreach($perm as $k => $component){
            if(!isset($name[$k])){
                return false;
            }elseif($name[$k] !== $component){
                return false;
            }
        }
        return true;
    }
    ////////////////q

    /**
     * @return bool
     */
    public function isAuthenticated(){
        return $this->authenticated;
    }

    /** @var PermissionAttachment|null  */
    private $permissionAttachment = null;

    /**
     * @return null|PermissionAttachment
     */
    public function getPermissionAttachment(){
        return $this->permissionAttachment;
    }

    /**
     * @param PermissionAttachment $attachment
     */
    public function setPermissionAttachment(PermissionAttachment $attachment){
        $this->permissionAttachment = $attachment;
    }

    /** @var int|null */
    private $taskID = null;

    /**
     * @return int|null
     */
    public function getTaskID(){
        return $this->taskID;
    }

    /**
     * @param int|null $taskId
     */
    public function setTaskID($taskId){
        $this->taskID = $taskId;
    }

    /** @var int|null */
    private $lastTyping;

    /**
     * @return int|null
     */
    public function getLastTyping(){
        return $this->lastTyping;
    }

    /**
     * @param int|null $time
     */
    public function setLastTyping($time){
        $this->lastTyping = $time;
    }

    /** @var array|null */
    private $registrationProcess = null;

    public function setIntoRegistrationProcess(){
        $this->registrationProcess = [
            "password" => null,
            "confirm_password" => null,
            "email" => null,
            "confirm_email" => null
        ];
    }

    /**
     * @return bool
     */
    public function isInRegistrationProcess(){
        return is_array($this->registrationProcess);
    }

    /**
     * @return bool|int
     */
    public function getNextRegistrationStep(){
        if($this->registrationProcess["password"] === null){
            return 0;
        }elseif($this->registrationProcess["confirm_password"] === null){
            return 1;
        }elseif($this->registrationProcess["email"] === null){
            return 2;
        }elseif($this->registrationProcess["confirm_email"] === null){
            return 3;
        }else{
            var_dump("Next step true");
            return true;
        }
    }

    /**
     * @return array|bool|null
     */
    public function getRegistrationInformation(){
        if($this->getNextRegistrationStep() === true){
            return $this->registrationProcess;
        }
        return false;
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function receiveRegistrationProcessInformation($key, $value){
        if(($this->registrationProcess["password"] !== null && $key === "confirm_password") && $value !== $this->registrationProcess["password"]){
            $this->registrationProcess["password"] = null;
            $this->registrationProcess["confirm_password"] = null;
            return false;
        }elseif(($this->registrationProcess["email"] !== null && $key === "confirm_email") && $value !== $this->registrationProcess["email"]){
            $this->registrationProcess["email"] = null;
            $this->registrationProcess["confirm_email"] = null;
            return false;
        }else{
            $this->registrationProcess[$key] = $value;
            return true;
        }
    }

    public function resetRegisterInformation(){
        $this->setIntoRegistrationProcess(); // just to not waste resources xD
    }

    /**  __  __             _       _____ _            _
     *  |  \/  |           (_)     / ____| |          | |
     *  | \  / | __ _  __ _ _  ___| |    | | ___   ___| | __
     *  | |\/| |/ _` |/ _` | |/ __| |    | |/ _ \ / __| |/ /
     *  | |  | | (_| | (_| | | (__| |____| | (_) | (__|   <
     *  |_|  |_|\__,_|\__, |_|\___|\_____|_|\___/ \___|_|\_\
     *                 __/ |
     *                |___/
     */

    /** @var bool  */
    private $magicClock = false;

    /**
     * @return bool
     */
    public function isMagicClockEnabled(){
        return $this->magicClock;
    }

    /**
     * @param $state
     */
    public function setMagicClock($state){
        $this->magicClock = $state;
        if($this->getPlayer()->getLevel() === $this->getCore()->getServer()->getDefaultLevel()){
            foreach($this->getPlayer()->getLevel()->getPlayers() as $p){
                if($this->getCore()->isPlayerAuthenticated($p)){
                    if(!$state){
                        $this->getPlayer()->hidePlayer($p);
                    }else{
                        $this->getPlayer()->showPlayer($p);
                    }
                }
            }
        }
    }

    /**  __  __       _ _   _        _
     *  |  \/  |     | | | (_)      | |
     *  | \  / |_   _| | |_ _ ______| |     __ _ _ __   __ _ _   _  __ _  __ _  ___
     *  | |\/| | | | | | __| |______| |    / _` | '_ \ / _` | | | |/ _` |/ _` |/ _ \
     *  | |  | | |_| | | |_| |      | |___| (_| | | | | (_| | |_| | (_| | (_| |  __/
     *  |_|  |_|\__,_|_|\__|_|      |______\__,_|_| |_|\__, |\__,_|\__,_|\__, |\___|
     *                                                  __/ |             __/ |
     *                                                 |___/             |___/
     */

    /** @var string */
    private $language = null;

    /**
     * @return string
     */
    public function getLanguage(){
        return $this->language;
    }

    /**
     * @param $language
     */
    public function setLanguage($language){
        $this->language = $language;
    }

    /** @var string */
    private $country = null;

    /**
     * @return string
     */
    public function getCountry(){
        return $this->country;
    }

    /**
     * @param $country
     */
    public function setCountry($country){
        $this->country = $country;
    }
}