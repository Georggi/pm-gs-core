<?php
namespace Core\InternalAPI;

use Core\BaseFiles\BaseSession;
use Core\InternalAPI\Events\Disguise\DisguiseEvent;
use Core\InternalAPI\Events\Disguise\DisguisePauseEvent;
use Core\InternalAPI\Events\Disguise\DisguiseRegenerateEvent;
use Core\InternalAPI\Events\Disguise\DisguiseStartEvent;
use Core\InternalAPI\Events\Disguise\DisguiseStopEvent;
use Core\InternalAPI\Events\SuperPlayerCreateEvent;
use Core\Loader;
use Core\Tasks\LoginTasks;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\event\TextContainer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\Short;
use pocketmine\nbt\tag\String;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;

class SuperPlayer extends Player{
    /** @var Loader */
    private $core;
    /** @var BaseSession|null */
    private $session = null;

    /**
     * @param SourceInterface $interface
     * @param string $clientID
     * @param string $ip
     * @param int $port
     */
    public function __construct(SourceInterface $interface, $clientID, $ip, $port){
        parent::__construct($interface, $clientID, $ip, $port);
        /** @var CoreInstance $core */
        $core = $this->getServer()->getPluginManager()->getPlugin("Core");
        $this->getServer()->getPluginManager()->callEvent($ev = new SuperPlayerCreateEvent($core, self::class, self::class, BaseSession::class, BaseSession::class));
        $this->core = $ev->getCore();
        if(($session = $ev->getSessionClass()) !== null && !(new \ReflectionClass($session))->isAbstract()){
            $this->session = new $session($this);
        }
        $this->getCore()->getLanguagesAPI()->getPlayerLanguage($this);
        $this->getCore()->getRanksAPI()->getPlayerRank($this);

        $this->setPermissionAttachment($this->addAttachment($this->getCore()));

        $class = new \ReflectionClass($this);
        foreach($class->getConstants() as $k => $v){
            if(strpos($k, "DISGUISE_ENTITY_") !== false){
                $this->disguiseIDs[$v] = substr($k, 16);
            }
        }
    }

    /**
     * @return Loader
     */
    public function getCore(){
        return $this->core->getCore();
    }

    /**
     * @return BaseSession|null
     */
    public function getSession(){
        return $this->session;
    }

    /**
     * Jump between servers!
     *
     * @param string $ip
     * @param int $port
     * @return bool
     */
    public function jumpToServer($ip, $port = 19132){
        if(!is_int($port)){
            return false;
        }
        $pattern = "/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/";
        if(preg_match($pattern, $ip) !== 1){
            if(($ip = $this->getCore()->getServerByName($ip)) === null || preg_match($pattern, $ip) !== 1){
                return false;
            }
            $ip = $this->getCore()->getServerByName($ip);
        }
        $pk = new ServerJumpPacket($ip, $port);
        $pk->ip = $ip;
        $pk->port = $port;
        $this->dataPacket($pk);
        return true;
    }

    /**
     * @param string $string
     * @param array $args
     * @return string
     */
    private function chatMagic($string, array $args){
        return $this->getCore()->chatMagic($this->getCore()->getLanguagesAPI()->getTranslation($string, $args, $this->getLanguage()));
    }

    /**
     * @param TextContainer|string $message
     * @param array $args
     */
    public function sendMessage($message, array $args = []){
        parent::sendMessage($this->chatMagic($message, $args));
    }

    /**
     * @param string $message
     * @param string $subtitle
     * @param array $args
     */
    public function sendPopup($message, $subtitle = "", array $args = []){
        parent::sendPopup($this->chatMagic($message, $args), $subtitle);
    }

    /**
     * @param string $message
     * @param array $args
     */
    public function sendTip($message, array $args = []){
        parent::sendTip($this->chatMagic($message, $args));
    }

    /**
     * @param string $message
     * @param bool $isAdmin
     * @return bool
     */
    public function kick($message = '', $isAdmin = false){
        return parent::kick($this->chatMagic($message . str_repeat("\n", 7) . "%kick.sub", []), $isAdmin);
    }

    /**
     * @return SuperPlayer[]
     */
    public function getHiddenPlayers(){
        return $this->hiddenPlayers;
    }

    /**
     * @return SuperPlayer[]
     */
    public function getViewers(){
        return array_merge(parent::getViewers(), $this->getHiddenPlayers());
    }

    /**
     * @param bool $justNick
     * @return string
     */
    public function getDisplayName($justNick = false){
        if($justNick){
            return parent::getDisplayName();
        }
        return $this->getCore()->getRanksAPI()->getRankPrefix($this->getRank()) . parent::getDisplayName() . ">";
    }

    public function recalculatePermissions(){
        $this->getPermissionAttachment()->setPermissions($this->getCore()->getRanksAPI()->getRankPermissions($this->getCore()->getRanksAPI()->getPlayerRank($this)));
        parent::recalculatePermissions();
    }

    /*                 _   _
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
        //$this->recalculatePermissions();
        $this->getCore()->registerPlayerToDatabase($this);
        // Show the player... To itself...
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_INVISIBLE, false);
        $this->setDataProperty(self::DATA_SHOW_NAMETAG, self::DATA_TYPE_BYTE, 1);
        foreach($this->getCore()->getServer()->getOnlinePlayers() as $p){
            $p->showPlayer($this); // Show to other players...
        }
        // TODO Set player inventory to custom one when logging
    }

    public function deauthenticate(){
        $this->authenticated = false;
        //$this->recalculatePermissions();
        $task = $this->getCore()->getServer()->getScheduler()->scheduleRepeatingTask(new LoginTasks($this->getCore(), $this, $this->getCore()->isPlayerRegistered($this)), 600);
        $this->setTaskID($task->getTaskId());
        $this->setLastTyping(time());

        // Hide the player... From itself...
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_INVISIBLE, true);
        $this->setDataProperty(self::DATA_SHOW_NAMETAG, self::DATA_TYPE_BYTE, 0);
        foreach($this->getCore()->getServer()->getOnlinePlayers() as $p){
            $p->hidePlayer($this); // Hide to other players...
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
    ////////////////

    /**
     * @return bool
     */
    public function isAuthenticated(){
        return $this->authenticated;
    }

    /** @var PermissionAttachment */
    private $permissionAttachment;

    /**
     * @return PermissionAttachment
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
        $this->setIntoRegistrationProcess(); // Just to not waste resources xD
    }

    /*   _____  _                 _
     *  |  __ \(_)               (_)
     *  | |  | |_ ___  __ _ _   _ _ ___  ___
     *  | |  | | / __|/ _` | | | | / __|/ _ \
     *  | |__| | \__ \ (_| | |_| | \__ \  __/
     *  |_____/|_|___/\__, |\__,_|_|___/\___|
     *                 __/ |
     *                |___/
     */

    // Actions...
    const STOP_DISGUISE = 0;
    const START_DISGUISE = 1;
    const PAUSE_DISGUISE = 2;
    const REGENERATE_DISGUISE = 3;

    /////////// PASSIVE MOBS ///////////
    const DISGUISE_ENTITY_CHICKEN = 10;
    const DISGUISE_ENTITY_COW = 11;
    const DISGUISE_ENTITY_PIG = 12;
    const DISGUISE_ENTITY_SHEEP = 13;
    const DISGUISE_ENTITY_WOLF = 14;
    const DISGUISE_ENTITY_VILLAGER = 15;
    const DISGUISE_ENTITY_NPC = self::DISGUISE_ENTITY_VILLAGER;
    const DISGUISE_ENTITY_MOOSHROOM_COW = 16;
    const DISGUISE_ENTITY_MOOSHROOM = self::DISGUISE_ENTITY_MOOSHROOM_COW;
    const DISGUISE_ENTITY_MUSHROOM_COW = self::DISGUISE_ENTITY_MOOSHROOM_COW;
    const DISGUISE_ENTITY_MUSHROOM = self::DISGUISE_ENTITY_MOOSHROOM_COW;
    const DISGUISE_ENTITY_SQUID = 17;

    const DISGUISE_ENTITY_BAT = 19;
    const DISGUISE_ENTITY_IRON_GOLEM = 20;
    const DISGUISE_ENTITY_SNOW_GOLEM = 21;
    const DISGUISE_ENTITY_OCELOT = 22;
    const DISGUISE_ENTITY_CAT = self::DISGUISE_ENTITY_OCELOT;
    /////////// HOSTILE MOBS ///////////
    const DISGUISE_ENTITY_ZOMBIE = 32;
    const DISGUISE_ENTITY_CREEPER = 33;
    const DISGUISE_ENTITY_SKELETON = 34;
    const DISGUISE_ENTITY_SPIDER = 35;
    const DISGUISE_ENTITY_PIGMAN = 36;
    const DISGUISE_ENTITY_ZOMBIE_PIGMAN = self::DISGUISE_ENTITY_PIGMAN;
    const DISGUISE_ENTITY_SLIME = 37;
    const DISGUISE_ENTITY_ENDERMAN = 38;
    const DISGUISE_ENTITY_SILVERFISH = 39;
    const DISGUISE_ENTITY_CAVE_SPIDER = 40;
    const DISGUISE_ENTITY_GHAST = 41;
    const DISGUISE_ENTITY_MAGMA_CUBE = 42;
    const DISGUISE_ENTITY_MAGMA = self::DISGUISE_ENTITY_MAGMA_CUBE;
    const DISGUISE_ENTITY_BLAZE = 43;
    ////////// OTHER ENTITIES //////////
    const DISGUISE_ENTITY_PLAYER = 63;
    const DISGUISE_ENTITY_DROPPED_ITEM = 64;
    const DISGUISE_ENTITY_ITEM = self::DISGUISE_ENTITY_DROPPED_ITEM;
    const DISGUISE_ENTITY_PRIMED_TNT = 65;
    const DISGUISE_ENTITY_TNT = self::DISGUISE_ENTITY_PRIMED_TNT;
    const DISGUISE_ENTITY_FALLING_BLOCK = 66;

    const DISGUISE_ENTITY_FISHING_ROD_HOOK = 77;
    const DISGUISE_ENTITY_FISHING_HOOK = self::DISGUISE_ENTITY_FISHING_ROD_HOOK;

    const DISGUISE_ENTITY_SHOOT_ARROW = 80;
    const DISGUISE_ENTITY_ARROW = self::DISGUISE_ENTITY_SHOOT_ARROW;
    const DISGUISE_ENTITY_THROWN_SNOWBALL = 81;
    const DISGUISE_ENTITY_SNOWBALL = self::DISGUISE_ENTITY_THROWN_SNOWBALL;
    const DISGUISE_ENTITY_THROWN_EGG = 82;
    const DISGUISE_ENTITY_EGG = self::DISGUISE_ENTITY_THROWN_EGG;
    const DISGUISE_ENTITY_PAINTING = 83;
    const DISGUISE_ENTITY_MINECART = 84;
    const DISGUISE_ENTITY_GHAST_FIREBALL = 85;
    const DISGUISE_ENTITY_FIREBALL = self::DISGUISE_ENTITY_GHAST_FIREBALL;

    const DISGUISE_ENTITY_BOAT = 90;

    // The following constants require the usage of the "setDisguiseDataProperty" method
    const DISGUISE_DATA_AGEABLE_AGE = 12;
    const DISGUISE_DATA_ZOMBIE_IS_VILLAGER = 13;
    const DISGUISE_DATA_ZOMBIE_IS_CONVERTING = 14;

    const DISGUISE_DATA_BAT_HANGING = 16;

    const DISGUISE_DATA_BLAZE_ON_FIRE = 16;

    const DISGUISE_DATA_CREEPER_STATE = 16;
    const DISGUISE_DATA_CREEPER_STATE_IDLE = -1;
    const DISGUISE_DATA_CREEPER_STATE_FUSE = 1;
    const DISGUISE_DATA_CREEPER_IS_POWERED = 17;

    const DISGUISE_DATA_ENDERMAN_CARRIED_BLOCK_ID = 16;
    const DISGUISE_DATA_ENDERMAN_CARRIED_BLOCK_DATA = 17;
    const DISGUISE_DATA_ENDERMAN_SCREAMING = 18;

    const DISGUISE_DATA_FALLING_BLOCK_ID = 20;

    const DISGUISE_DATA_GHAST_ATTACKING = 16;

    const DISGUISE_DATA_IRONGOLEM_PLAYER_CREATED = 16;

    const DISGUISE_DATA_LIVINGENTITY_NAME = 2;
    const DISGUISE_DATA_LIVINGENTITY_SHOW_NAMETAG = 3;
    const DISGUISE_DATA_LIVINGENTITY_AI = 15;

    const DISGUISE_DATA_PIG_SADDLE = 16;

    const DISGUISE_DATA_SKELETON_TYPE = 13;
    const DISGUISE_DATA_SKELETON_TYPE_NORMAL = 0;
    const DISGUISE_DATA_SKELETON_TYPE_WITHER = 1;

    const DISGUISE_DATA_SPIDER_CLIMBING = 16;
    const DISGUISE_DATA_CAVE_SPIDER_CLIMBING = self::DISGUISE_DATA_SPIDER_CLIMBING;

    const DISGUISE_DATA_SLIME_SIZE = 16;
    const DISGUISE_DATA_MAGMACUBE_SIZE = self::DISGUISE_DATA_SLIME_SIZE;

    const DISGUISE_DATA_TAMEABLE_OWNER = 17;
    const DISGUISE_DATA_OCELOT_TYPE = 18;
    const DISGUISE_DATA_WOLF_HEALTH = 18;
    const DISGUISE_DATA_WOLF_BEGGING = 19;
    const DISGUISE_DATA_WOLF_COLLAR = 20;

    const DISGUISE_DATA_VILLAGER = 16;
    const DISGUISE_DATA_VILLAGER_FARMER = 0;
    const DISGUISE_DATA_VILLAGER_LIBRARIAN = 1;
    const DISGUISE_DATA_VILLAGER_PRIEST = 2;
    const DISGUISE_DATA_VILLAGER_BLACKSMITH = 3;
    const DISGUISE_DATA_VILLAGER_BUTCHER = 4;

    // The following constants require the usage of the "setDisguiseDataFlag" method
    const DISGUISE_DATA_ENTITY_FLAG = 0;
    const DISGUISE_DATA_ENTITY_FLAG_INVISIBLE = 32; // 0x20

    const DISGUISE_DATA_TAMEABLE_FLAG = 16;
    const DISGUISE_DATA_TAMEABLE_FLAG_SITTING = 1; // 0x01
    const DISGUISE_DATA_WOLF_FLAG_ANGRY = 2; // 0x02
    const DISGUISE_DATA_TAMEABLE_FLAG_TAMED = 4; // 0x04

    const DISGUISE_DATA_SHEEP_FLAG = 16;
    const DISGUISE_DATA_SHEEP_FLAG_COLOR = 15; // 0x0F
    const DISGUISE_DATA_ENTITY_DYE_COLOR_BLACK = 0;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_RED = 1;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_GREEN = 2;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_BROWN = 3;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_BLUE = 4;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_PURPLE = 5;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_CYAN = 6;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_SILVER = 7;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_GRAY = 8;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_PINK = 9;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_LIME = 10;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_YELLOW = 11;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_LIGHT_BLUE = 12;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_MAGENTA = 13;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_ORANGE = 14;
    const DISGUISE_DATA_ENTITY_DYE_COLOR_WHITE = 15;
    const DISGUISE_DATA_SHEEP_FLAG_SHEARED = 16; // 0x10


    /** @var bool|int */
    private $disguise = false;
    /** @var bool|int */
    private $pauseDisguise = false;
    /** @var Effect */
    private $disguiseEffect;
    /** @var array */
    private $disguiseDataProperties = [];
    /** @var array */
    private $disguiseIDs = [];

    /**
     * @return Effect
     */
    public function getDisguiseEffect(){
        if(!$this->disguiseEffect instanceof Effect){
            $effect = Effect::getEffect(Effect::INVISIBILITY);
            $this->disguiseEffect = (new Effect(Effect::INVISIBILITY, "Disguised", $effect->getColor()[0], $effect->getColor()[1], $effect->getColor()[2], false))->setDuration(PHP_INT_MAX);
        }
        return $this->disguiseEffect;
    }

    /**
     * @param string $entity
     * @return int
     */
    public function getDisguiseFromString($entity){
        if(is_string($entity) && defined($entity = "self::DISGUISE_ENTITY_" . strtoupper($entity))){
            return constant($entity);
        }
        return 0;
    }

    /**
     * @param int $entity
     * @return string
     */
    public function getDisguiseFromInt($entity){
        if($this->isValidID($entity)){
            return $this->disguiseIDs[$entity];
        }
        return "player";
    }

    /**
     * @param int $id
     * @return bool
     */
    public function isValidID($id){
        if(!is_int((int) $id)){
            return false;
        }
        return isset($this->disguiseIDs[(int) $id]);
    }

    /**
     * @return bool
     */
    public function isDisguised(){
        return $this->getDisguiseID();
    }

    /**
     * @return bool|int
     */
    public function getDisguiseID(){
        return $this->disguise;
    }

    /**
     * @return bool
     */
    public function isDisguisePaused(){
        return is_int($this->pauseDisguise);
    }

    /**
     * @return bool
     */
    public function isSpawnedWithDisguisePaused(){
        return $this->pauseDisguise === 1;
    }

    /**
     * @param int $action
     * @param array $data
     * @return DisguiseEvent|DisguisePauseEvent|DisguiseRegenerateEvent|DisguiseStartEvent|DisguiseStopEvent
     */
    private function fireEvent($action, array $data){
        switch($action){
            case self::START_DISGUISE:
                $ev = new DisguiseStartEvent($this, $data["entityID"]);
                break;
            case self::REGENERATE_DISGUISE:
                $ev = new DisguiseRegenerateEvent($this, $data["entityID"], $data["newEntityID"]);
                break;
            case self::PAUSE_DISGUISE:
                $ev = new DisguisePauseEvent($this, $data["spawnPlayer"]);
                break;
            case self::STOP_DISGUISE:
                $ev = new DisguiseStopEvent($this);
                break;
            default:
                $ev = new DisguiseEvent($this);
                break;
        }
        $this->getServer()->getPluginManager()->callEvent($ev);
        return $ev;
    }

    /**
     * @param int $propertyId
     * @param int $id
     * @return int
     */
    public function getDisguiseDataFlag($propertyId, $id){
        return (((int) $this->getDisguiseDataProperty($propertyId)) & (1 << $id));
    }

    /**
     * @param int $propertyId
     * @param int $id
     * @param int $type
     * @param mixed $value
     * @param bool $send
     */
    public function setDisguiseDataFlag($propertyId, $id, $type, $value, $send = true){
        if($this->getDisguiseDataFlag($propertyId, $id) !== $value){
            $flags = (int) $this->getDisguiseDataProperty($id);
            $flags ^= 1<< $id;
            $this->setDisguiseDataProperty($propertyId, $type, $flags, $send);
        }
    }

    /**
     * @param $id
     * @return null|int
     */
    public function getDisguiseDataProperty($id){
        return isset($this->disguiseDataProperties[$id]) ? $this->disguiseDataProperties[$id][1] : null;
    }

    public function getDisguiseDataProperties(){
        return isset($this->disguiseDataProperties) ? $this->disguiseDataProperties : null;
    }

    /**
     * @param int $id
     * @param int $type
     * @param mixed $value
     * @param bool $send
     * @param SuperPlayer[] $targets
     */
    public function setDisguiseDataProperty($id, $type, $value, $send = true, $targets = []){
        if($this->getDisguiseDataProperty($id) !== $value){
            $this->disguiseDataProperties[$id] = [$type, $value];

            if($send){
                $this->sendData(count($targets) < 1 ? $this->getViewers() : $targets, [$id => $this->disguiseDataProperties[$id]]);
            }
        }
    }

    /**
     * @param $entity
     * @param Compound|string $nbt
     * @param bool|true $send
     */
    private function parseDisguiseNBT($entity, $nbt, $send = true){
        if(is_string($nbt)){
            $nbt = NBT::parseJSON($nbt);
        }
        switch($entity){
            case self::DISGUISE_ENTITY_FALLING_BLOCK:
                if(isset($nbt->Tile) && !isset($nbt->TileID)){
                    $this->setDisguiseDataProperty(self::DISGUISE_DATA_FALLING_BLOCK_ID, self::DATA_TYPE_INT, $nbt["Tile"], $send);
                    $nbt["TileID"] = new Int("TileID", $nbt->Tile);
                }
                if($nbt["TileID"] < 1){
                    $nbt["TileID"] = new Int("TileID", Block::SAND);
                }
                if(!isset($nbt->Data)){
                    $nbt["Data"] = new Int("Data", 0);
                }
                if(!isset($nbt->NoFall)){
                    $nbt["NoFall"] = new Int("NoFall", 1);
                }
                //$this->setDataProperty(self::DATA_NO_AI, self::DATA_TYPE_INT, 0);
                $this->setDisguiseDataProperty(self::DISGUISE_DATA_FALLING_BLOCK_ID, self::DATA_TYPE_INT, $nbt["TileID"]->getValue() | ($nbt["Data"]->getValue() << 8), $send);
                //$ActualEntity = $this->getLevel()->getEntity($this->getDisguiseID());
                //$this->setDisguiseDataProperty(self::DATA_NO_AI, self::DATA_TYPE_BYTE, 1);
                break;
            default: // Living entities
                if(isset($nbt->Name) && !isset($nbt->CustomName)){
                    $nbt["CustomName"] = new String("CustomName", $nbt["Name"]->getValue());
                }elseif(!isset($nbt->Name) && !isset($nbt->CustomName)){
                    $nbt["CustomName"] = new String("CustomName", "Disguise");
                }
                $this->setDisguiseDataProperty(self::DISGUISE_DATA_LIVINGENTITY_NAME, self::DATA_TYPE_STRING, (string) $nbt["CustomName"]->getValue(), $send);
                if(!isset($nbt->CustomNameVisible)){
                    $nbt["CustomNameVisible"] = new Byte("CustomNameVisible", 0);
                }
                $this->setDisguiseDataProperty(self::DISGUISE_DATA_LIVINGENTITY_SHOW_NAMETAG, self::DATA_TYPE_BYTE, (int) $nbt["CustomNameVisible"]->getValue(), $send);
                switch($entity){
                    case self::DISGUISE_ENTITY_COW:
                    case self::DISGUISE_ENTITY_CHICKEN;
                    case self::DISGUISE_ENTITY_DROPPED_ITEM:
                    case self::DISGUISE_ENTITY_FISHING_ROD_HOOK:
                    case self::DISGUISE_ENTITY_GHAST_FIREBALL:
                    case self::DISGUISE_ENTITY_MINECART:
                    case self::DISGUISE_ENTITY_MOOSHROOM:
                    case self::DISGUISE_ENTITY_PAINTING:
                    case self::DISGUISE_ENTITY_PLAYER:
                    case self::DISGUISE_ENTITY_PRIMED_TNT:
                    case self::DISGUISE_ENTITY_SILVERFISH:
                    case self::DISGUISE_ENTITY_SQUID:
                    case self::DISGUISE_ENTITY_THROWN_EGG:
                    case self::DISGUISE_ENTITY_THROWN_SNOWBALL:
                        // None of them have special NBT....
                        break;
                    case self::DISGUISE_ENTITY_BAT:
                        if(!isset($nbt->Hanging)){
                            $nbt["Hanging"] = new Byte("Hanging", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_BAT_HANGING, self::DATA_TYPE_BYTE, (int) $nbt["Hanging"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_BLAZE:
                        if(!isset($nbt->OnFire)){
                            $nbt["OnFire"] = new Byte("OnFire", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_BLAZE_ON_FIRE, self::DATA_TYPE_BYTE, (int) $nbt["OnFire"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_CREEPER:
                        if(!isset($nbt->State)){
                            $nbt["State"] = new Byte("State", self::DISGUISE_DATA_CREEPER_STATE_IDLE);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_CREEPER_STATE, self::DATA_TYPE_BYTE, (int) $nbt["State"]->getValue(), $send);
                        if(!isset($nbt->Powered)){
                            $nbt["Powered"] = new Byte("Powered", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_CREEPER_IS_POWERED, self::DATA_TYPE_BYTE, (int) $nbt["Powered"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_ENDERMAN:
                        if(!isset($nbt->CarriedBlock)){
                            $nbt["CarriedBlock"] = new Short("CarriedBlock", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_ENDERMAN_CARRIED_BLOCK_ID, self::DATA_TYPE_SHORT, (int) $nbt["CarriedBlock"]->getValue(), $send);

                        // TODO: CarriedBlockData

                        if(!isset($nbt->Screaming)){
                            $nbt["Screaming"] = new Byte("Screaming", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_ENDERMAN_SCREAMING, self::DATA_TYPE_BYTE, (int) $nbt["Screaming"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_IRON_GOLEM:
                        if(!isset($nbt->PlayerCreated)){
                            $nbt["PlayerCreated"] = new Byte("PlayerCreated", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_IRONGOLEM_PLAYER_CREATED, self::DATA_TYPE_BYTE, (int) $nbt["PlayerCreated"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_SKELETON:
                        if(!isset($nbt->Type)){
                            $nbt["Type"] = new Byte("Type", self::DISGUISE_DATA_SKELETON_TYPE_NORMAL);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_SKELETON_TYPE, self::DATA_TYPE_BYTE, (int) $nbt["Type"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_SLIME:
                    case self::DISGUISE_ENTITY_MAGMA:
                        if(!isset($nbt->Size)){
                            $nbt["Size"] = new Byte("Size", 16);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_SLIME_SIZE, self::DATA_TYPE_BYTE, (int) $nbt["Size"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_SPIDER:
                    case self::DISGUISE_ENTITY_CAVE_SPIDER:
                        if(!isset($nbt->Climbing)){
                            $nbt["Climbing"] = new Byte("Climbing", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_SPIDER_CLIMBING, self::DATA_TYPE_BYTE, (int) $nbt["Climbing"]->getValue(), $send);
                        break;
                    case self::DISGUISE_ENTITY_GHAST:
                        if(!isset($nbt->Attacking)){
                            $nbt["Attacking"] = new Byte("Attacking", 0);
                        }
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_GHAST_ATTACKING, self::DATA_TYPE_BYTE, (int) $nbt["Attacking"]->getValue(), $send);
                        break;
                    default: // Ageable entities
                        if(!isset($nbt->Child)){
                            $nbt["Child"] = new Byte("Child", 0);
                        }
                        /* Negative switch "is Child", positive is "not Child"
                         * Please just use booleans to define this on NBT, the proper value setting will be specified here...
                         * Example:
                         *  '$nbt["Child"] = false' === isn't Child
                         *  '$nbt["Child"] = true'  === is Child
                         * Just, don't try to fit the below line of code....
                         */
                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_AGEABLE_AGE, self::DATA_TYPE_BYTE, ((bool) $nbt["Child"]->getValue() ? -1 : 1), $send);
                        switch($entity){
                            case self::DISGUISE_ENTITY_PIG:
                                if(!isset($nbt->Saddled)){
                                    $nbt["Saddled"] = new Byte("Saddled", 0);
                                }
                                $this->setDisguiseDataProperty(self::DISGUISE_DATA_PIG_SADDLE, self::DATA_TYPE_BYTE, (int) $nbt["Saddled"]->getValue(), $send);
                                break;
                            case self::DISGUISE_ENTITY_SHEEP:
                                if(!isset($nbt->Color)){
                                    $nbt["Color"] = new Byte("Color", self::DISGUISE_DATA_ENTITY_DYE_COLOR_WHITE);
                                }
                                $this->setDisguiseDataFlag(self::DISGUISE_DATA_SHEEP_FLAG, self::DISGUISE_DATA_SHEEP_FLAG_COLOR, self::DATA_TYPE_BYTE, (int) $nbt["Color"]->getValue(), $send);
                                if(!isset($nbt->Sheared)){
                                    $nbt["Sheared"] = new Byte("Sheared", 0);
                                }
                                $this->setDisguiseDataFlag(self::DISGUISE_DATA_SHEEP_FLAG, self::DISGUISE_DATA_SHEEP_FLAG_SHEARED, self::DATA_TYPE_BYTE, (int) $nbt["Sheared"]->getValue(), $send);
                                break;
                            case self::DISGUISE_ENTITY_VILLAGER:
                                if(!isset($nbt->Profession)){
                                    $nbt["Profession"] = new Int("Profession", self::DISGUISE_DATA_VILLAGER_FARMER);
                                }
                                $this->setDisguiseDataProperty(self::DISGUISE_DATA_VILLAGER, self::DATA_TYPE_INT, (int) $nbt["Profession"]->getValue(), $send);
                                break;
                            case self::DISGUISE_ENTITY_ZOMBIE:
                            case self::DISGUISE_ENTITY_PIGMAN:
                                if(!isset($nbt->Villager)){
                                    $nbt["Villager"] = new Byte("Villager", 0);
                                }
                                $this->setDisguiseDataProperty(self::DISGUISE_DATA_ZOMBIE_IS_VILLAGER, self::DATA_TYPE_BYTE, (int) $nbt["Villager"]->getValue(), $send);
                                if(!isset($nbt->Converting)){
                                    $nbt["Converting"] = new Byte("Converting", 0);
                                }
                                $this->setDisguiseDataProperty(self::DISGUISE_DATA_ZOMBIE_IS_CONVERTING, self::DATA_TYPE_BYTE, (int) $nbt["Converting"]->getValue(), $send);
                                break;
                            default: // Tameable entities
                                if(!isset($nbt->Sitting)){
                                    $nbt["Sitting"] = new Byte("Sitting", 0);
                                }
                                $this->setDisguiseDataFlag(self::DISGUISE_DATA_TAMEABLE_FLAG, self::DISGUISE_DATA_TAMEABLE_FLAG_SITTING, self::DATA_TYPE_BYTE, (int) $nbt["Sitting"]->getValue(), $send);
                                if(!isset($nbt->Tamed)){
                                    $nbt["Tamed"] = new Byte("Tamed", (int) isset($nbt->Owner));
                                }
                                $this->setDisguiseDataFlag(self::DISGUISE_DATA_TAMEABLE_FLAG, self::DISGUISE_DATA_TAMEABLE_FLAG_TAMED, self::DATA_TYPE_BYTE, (int) $nbt["Tamed"]->getValue(), $send);
                                if(!isset($nbt->Owner)){
                                    $nbt["Owner"] = new String("Owner", "");
                                }
                                $this->setDisguiseDataProperty(self::DISGUISE_DATA_TAMEABLE_OWNER, self::DATA_TYPE_STRING, (string) $nbt["Owner"]->getValue(), $send);
                                switch($entity){
                                    case self::DISGUISE_ENTITY_OCELOT:
                                        if(!isset($nbt->Type)){
                                            $nbt["Type"] = new Byte("Type", 0);
                                        }
                                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_OCELOT_TYPE, self::DATA_TYPE_BYTE, (int) $nbt["Type"]->getValue(), $send);
                                        break;
                                    case self::DISGUISE_ENTITY_WOLF:
                                        if(!isset($nbt->Angry)){
                                            $nbt["Angry"] = new Byte("Angry", 0);
                                        }
                                        $this->setDisguiseDataFlag(self::DISGUISE_DATA_TAMEABLE_FLAG, self::DISGUISE_DATA_WOLF_FLAG_ANGRY, self::DATA_TYPE_BYTE, (int) $nbt["Angry"]->getValue(), $send);
                                        if(!isset($nbt->Collar)){
                                            $nbt["Collar"] = new Byte("Collar", self::DISGUISE_DATA_ENTITY_DYE_COLOR_RED);
                                        }
                                        $this->setDisguiseDataProperty(self::DISGUISE_DATA_WOLF_COLLAR, self::DATA_TYPE_BYTE, (int) $nbt["Collar"]->getValue(), $send);
                                        break;
                                }
                                break;
                        }
                        break;
                }
                break;
        }
    }

    /**
     * @param string|int $entity
     * @param Compound|string|null $nbt
     * @return bool
     */
    public function startDisguise($entity, $nbt = null){
        if($entity === $this->getDisguiseID()){
            return true;
        }
        elseif(!$this->isValidID($entity)){
            if(is_string($entity)){
                $entity = $this->getDisguiseFromString($entity);
                if($entity === 0){
                    return false;
                }
            }else{
                return false;
            }
        }

        if($this->isDisguised()){
            /** @var DisguiseRegenerateEvent $ev */
            $ev = $this->fireEvent(self::REGENERATE_DISGUISE, ["entityID" => $this->getDisguiseID(), "newEntityID" => $entity]);
            if(!$this->isDisguisePaused()){
                $entity = $ev->getNewEntityID();
                $pk = new RemoveEntityPacket();
                $pk->eid = $this->getId();
            }
        }else{
            /** @var DisguiseStartEvent $ev */
            $ev = $this->fireEvent(self::START_DISGUISE, ["entityID" => $entity]);
            $entity = $ev->getEntityID();
            $pk = new RemovePlayerPacket();
            $pk->eid = $this->getId();
            $pk->clientId = $this->getUniqueId();
        }
        if($ev->isCancelled()){
            return false;
        }

        $this->getDisguiseEffect()->add($this);
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_INVISIBLE, true, self::DATA_TYPE_BYTE);
        $this->setDataProperty(self::DATA_SHOW_NAMETAG, self::DATA_TYPE_BYTE, 0);

        $pk2 = new AddEntityPacket();
        $pk2->eid = $this->getId();
        $pk2->type = $entity;
        $pk2->x = $this->getX();
        if($entity == self::DISGUISE_ENTITY_FALLING_BLOCK){
            $pk2->y = $this->getY() + 0.5;//Fix bug with falling send going underground
        } else {
            $pk2->y = $this->getY();
        }
        $pk2->z = $this->getZ();
        $pk2->yaw = $this->getYaw();
        $pk2->pitch = $this->getPitch();
        $pk2->metadata = [];

        $pk3 = new SetEntityMotionPacket();
        $pk3->entities = [[$this->getId(), $this->motionX, $this->motionY, $this->motionZ]];

        $this->parseDisguiseNBT($entity, $nbt, false);

        $pk4 = new SetEntityDataPacket();
        $pk4->eid = $this->getId();
        $pk4->metadata = $this->disguiseDataProperties;

        // TODO: Display disguise for client
        /*if(isset($pk)){
            $pk->eid = 0;
            $this->dataPacket($pk);
            $pk->eid = $this->getId();
        }*/ //CAUSES CRASHING
        //$this->dataPacket($pk2);
        //$this->dataPacket($pk3);
        //$this->dataPacket($pk4);
        foreach($this->getViewers() as $p){
            if($p->isOnline() && $p->canSee($this)){
                if(isset($pk)){
                    $p->dataPacket($pk);
                }
                $p->dataPacket($pk2);
                $p->dataPacket($pk3);
                $p->dataPacket($pk4);
            }
        }

        $this->setDisguiseDataProperty(self::DISGUISE_DATA_LIVINGENTITY_AI, self::DATA_TYPE_BYTE, false, true, [$this]);

        $this->disguise = $entity;
        $this->getCore()->addDisguisedPlayer($this);
        return true;
    }

    /**
     * @param bool $pause
     * @param bool $spawnPlayer
     */
    public function stopDisguise($pause = false, $spawnPlayer = false){
        if($this->isDisguised()){
            $pk = new RemoveEntityPacket();
            $pk->eid = $this->getId();

            if($pause){
                if(!$this->isDisguisePaused()){
                    /** @var DisguisePauseEvent $ev */
                    $ev = $this->fireEvent(self::PAUSE_DISGUISE, ["spawnPlayer" => $spawnPlayer]);
                    $spawnPlayer = $ev->goingToSpawnPlayer();
                    $this->pauseDisguise = $spawnPlayer ? 1 : 0;
                }else{
                    return;
                }
            }else{
                $this->getCore()->removeDisguisedPlayer($this);
                $this->disguiseDataProperties = [];
                $this->pauseDisguise = false;
                $this->disguise = false;
                /** @var DisguiseStopEvent $ev */
                $ev = $this->fireEvent(self::STOP_DISGUISE, []);
                $spawnPlayer = true;
                if($this->isDisguisePaused()){
                    unset($pk);
                }
            }
            if($ev->isCancelled()){
                return;
            }

            if(isset($pk)){
                $this->dataPacket($pk);
            }
            foreach($this->getViewers() as $p){
                if($p->isOnline() && $p->canSee($this)){
                    if(isset($pk)){
                        $p->dataPacket($pk);
                    }
                    if($spawnPlayer){
                        $this->despawnFrom($p);
                        $this->spawnTo($p);
                    }
                }
            }

            if($spawnPlayer){
                $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_INVISIBLE, false);
                $this->setDataProperty(self::DATA_SHOW_NAMETAG, self::DATA_TYPE_BYTE, 1);
                if(!$pause){
                    $this->getDisguiseEffect()->remove($this);
                }
            }
        }
    }

    /*   __  __             _       _____ _            _
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
        if($this->getLevel() === $this->getCore()->getServer()->getDefaultLevel()){
            $this->magicClock = $state;
            foreach($this->getViewers() as $p){;
                /** @var SuperPlayer $p */
                if($p->isAuthenticated()){
                    if(!$state){
                        $this->hidePlayer($p);
                    }else{
                        $this->showPlayer($p);
                    }
                }
            }
        }
    }

    public function switchMagicClock(){
        $this->setMagicClock(!$this->isMagicClockEnabled());
    }

    /*   __  __       _ _   _        _
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
     * @param string $language
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
     * @param string $country
     */
    public function setCountry($country){
        $this->country = $country;
    }

    /*   _____             _
     *  |  __ \           | |
     *  | |__) |__ _ _ __ | | _____
     *  |  _  // _` | '_ \| |/ / __|
     *  | | \ \ (_| | | | |   <\__ \
     *  |_|  \_\__,_|_| |_|_|\_\___/
     */

    /** @var string */
    private $rank = "Guest";

    /**
     * @return string
     */
    public function getRank(){
        return $this->rank;
    }

    /**
     * @param string $rank
     */
    public function setRank($rank){
        $this->rank = $rank;
    }
}