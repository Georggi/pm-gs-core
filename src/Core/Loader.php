<?php
namespace Core;

use Core\InternalAPI\Charts;
use Core\InternalAPI\Commands\DisguiseCommand;
use Core\InternalAPI\CoreInstance;
use Core\InternalAPI\Languages;
use Core\InternalAPI\Ranks;
use Core\InternalAPI\SuperPlayer;
use Core\Tasks\PopupMessages\PopupSend;
use Core\Tasks\PopupMessages\RandomizeMessages;
use Core\Tasks\ServersListUpdater\ServersIPListUpdater;
use Core\Tasks\ServersListUpdater\ServersListAsyncScheduler;
use pocketmine\item\Item;
use pocketmine\permission\PermissionAttachment;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class Loader extends CoreInstance{
    /** @var Config */
    public $database;

    public function onEnable(){
        $this->database = new Config($this->getDataFolder() . "players.json", Config::JSON);

        $this->getServer()->getCommandMap()->registerAll("minepocket", [
            new DisguiseCommand($this)
        ]);

        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

        #$this->getServer()->getScheduler()->scheduleRepeatingTask(new ServersListAsyncScheduler($this), 400);
            // Popup Messages
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new RandomizeMessages($this), 200);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupSend($this), 5);

        // Import spawn...
        /*if($this->isHub()){
            $this->saveResource("world.zip", true);
            $this->unzip($this->getDataFolder() . "world.zip", $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . "world" . DIRECTORY_SEPARATOR);
        }*/

        // Server dynamic name...
        $this->updateServerName();
    }

    public function onDisable(){
        $this->database->save();
        foreach($this->getServer()->getPluginManager()->getPlugins() as $plugin){
            if($plugin instanceof CoreInstance && $plugin->isEnabled()){
                $plugin->setEnabled(false);
            }
        }
        foreach($this->getServer()->getLevels() as $level){
            $level->unload(false);
        }
        parent::onDisable();
        // Lets clean up...
        #$this->recursiveDirectoryCleaner($this->getServer()->getDataPath() . "players" . DIRECTORY_SEPARATOR); // Not needed with Katana?
        #$this->recursiveDirectoryCleaner($this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $dir
     */
    public function recursiveDirectoryCleaner($dir){
        if(is_dir($dir)){
            foreach(scandir($dir) as $object){
                if($object !== "." && $object !== ".."){
                    if(is_dir($dir . "/" . $object)){
                        $this->recursiveDirectoryCleaner($dir . $object . DIRECTORY_SEPARATOR);
                    }else{
                        unlink($dir . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /*  .----------------. .----------------. .----------------.
     * | .--------------. | .--------------. | .--------------. |
     * | |      __      | | |   ______     | | |     _____    | |
     * | |     /  \     | | |  |_   __ \   | | |    |_   _|   | |
     * | |    / /\ \    | | |    | |__) |  | | |      | |     | |
     * | |   / ____ \   | | |    |  ___/   | | |      | |     | |
     * | | _/ /    \ \_ | | |   _| |_      | | |     _| |_    | |
     * | ||____|  |____|| | |  |_____|     | | |    |_____|   | |
     * | |              | | |              | | |              | |
     * | '--------------' | '--------------' | '--------------' |
     *  '----------------' '----------------' '----------------'
     */

    /** @var bool */
    private $isHub = true;

    /**
     * @return bool
     */
    public function isHub(){
        return $this->isHub;
    }

    public function setNotHub(){
        $this->isHub = false;
    }

    /**
     * @param bool $playerQuit
     */
    public function updateServerName(){
        // The following just checks for "smiles" because we already use "Formatted Color codes" and we will not put bad words into it xD
        $this->getServer()->getNetwork()->setName($this->getCharts()->convertSmiles(TextFormat::AQUA . "/diamond/ " . TextFormat::AQUA . "Project-MU " . TextFormat::WHITE . "Network " . TextFormat::AQUA . "/diamond/ "));
    }

    /** @var string */
    private $popupMessage;

    /**
     * @return string
     */
    public function getPopupMessage(){
        return $this->popupMessage;
    }

    /**
     * @param $message
     */
    public function setPopupMessage($message){
        $this->popupMessage = $this->getCharts()->convertSmiles($message);
    }

    /**
     * @param string $origin
     * @param string $destination
     * @return bool
     */
    public function unzip($origin, $destination){
        $zip = new \ZipArchive();
        if(!$zip->open($origin)){
            return false;
        }
        if(!is_dir($destination)){
            @mkdir($destination);
        }
        $zip->extractTo($destination);
        $zip->close();
        return true;
    }

    /**
     * @return $this
     */
    public function getCore(){
        return $this;
    }

    /**
     * @return string
     */
    public function updaterName(){
        return "Core";
    }

    /*                 _   _
     *      /\        | | | |
     *     /  \  _   _| |_| |__
     *    / /\ \| | | | __| '_ \
     *   / ____ | |_| | |_| | | |
     *  /_/    \_\__,_|\__|_| |_|
     */

    const API = "https://minepocket.com/api.php";
    const XFAPI = "https://minepocket.com/xfapi.php";
    private $hash = "x8e8fca2dc6f896fd7cb4cw0031ba244";

    ////////////////////////////////////
    /**
     * @param array $args
     * @return array
     */
    private function minepocketAPI(array $args){
        return json_decode(Utils::postURL(self::API, $args), true);
    }
    ////////////////////////////////////

    /**
     * @param SuperPlayer $player
     * @return bool|mixed
     */
    public function getPlayerProfile(SuperPlayer $player){
        $json = $this->minepocketAPI([
            "action" => "getUser",
            "value" => $player->getName(),
            "hash" => $this->hash]);
        if(isset($json["error"]) && $json["error"] === 4){
            return false;
        }
        return $json;
    }

    /**
     * @param SuperPlayer $player
     * @return array|bool
     */
    public function getPlayerAccount(SuperPlayer $player){
        if(($p = $this->database->get($player->getName())) !== false){
            return $p;
        }
        return false;
    }

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isPlayerAuthenticated(SuperPlayer $player){
        return $player instanceof SuperPlayer;
    }

    /**
     * @param SuperPlayer $player
     * @param string $password
     * @param bool $ipMatch
     * @return bool
     */
    public function playerLogin(SuperPlayer $player, $password = "", $ipMatch = false){
        unset($password);
        unset($ipMatch);
        $this->authenticatePlayer($player);
        return true;
    }

    /**
     * @param SuperPlayer $player
     */
    public function authenticatePlayer(SuperPlayer $player){
        $player->authenticate();
    }

    /**
     * @param SuperPlayer $player
     */
    public function deauthenticatePlayer(SuperPlayer $player){
        $player->deauthenticate();
    }

    /**
     * @param SuperPlayer $player
     * @return PermissionAttachment
     */
    public function getPermissionAttachment(SuperPlayer $player){
        return (($attachment = $player->getPermissionAttachment()) === null ? false : $attachment);
    }

    /**
     * @param SuperPlayer $player
     * @return bool|int
     */
    public function getTaskID(SuperPlayer $player){
        return (($id = $player->getTaskID()) === null ? false : $id);
    }

    /**
     * @param SuperPlayer $player
     * @param int|null $taskId
     */
    public function setTaskID(SuperPlayer $player, $taskId){
        $player->setTaskID($taskId);
    }

    /**
     * @param SuperPlayer $player
     * @return int|null
     */
    public function getLastTyping(SuperPlayer $player){
        return (($time = $player) === null ? false : $time);
    }

    /**
     * @param SuperPlayer $player
     * @param int|null $time
     */
    public function setLastTyping(SuperPlayer $player, $time){
        $player->setLastTyping($time);
    }

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isPlayerRegistered(SuperPlayer $player){
        return $player instanceof SuperPlayer;
    }

    /**
     * @param SuperPlayer $player
     * @param string $password
     * @param string $email
     * @return bool
     */
    public function registerPlayer(SuperPlayer $player, $password, $email){
        unset($password);
        unset($email);
        $this->registerPlayerToDatabase($player);
        $this->authenticatePlayer($player);
        return true;
    }

    /**
     * @param SuperPlayer $player
     */
    public function registerPlayerToDatabase(SuperPlayer $player){
        $p = $this->database->get($player->getName(), [
            "account" => [
                "uuid" => $player->getUniqueId()
            ],
            "coins" => 0
        ]);
        $p["account"]["uuid"] = $player->getUniqueId();
        $this->database->set($player->getName(), $p);
    }

    /**
     * @param SuperPlayer $player
     */
    public function addToRegisterQueue(SuperPlayer $player){
        $player->setIntoRegistrationProcess();
    }

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isInRegistrationQueue(SuperPlayer $player){
        return $player->isInRegistrationProcess();
    }

    /**
     * @param SuperPlayer $player
     * @return bool|int
     */
    public function getNextRegistrationStep(SuperPlayer $player){
        if(is_int($r = $player->getNextRegistrationStep())){
            return $r;
        }elseif($player->getRegistrationInformation()["confirm_email"] !== null){
            return $this->registerPlayer($player, $player->getRegistrationInformation()["password"], $player->getRegistrationInformation()["email"]);
        }else{
            return false;
        }
    }

    /**
     * @param SuperPlayer $player
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function pushInfoToRegisterQueue(SuperPlayer $player, $key, $value){
        return $player->receiveRegistrationProcessInformation($key, $value);
    }

    /**
     * @param SuperPlayer $player
     */
    public function resetAllRegisterInformation(SuperPlayer $player){
        $player->resetRegisterInformation();
    }

    /**
     * @param SuperPlayer $player
     */
    public function closePlayer(SuperPlayer $player){
        $this->deauthenticatePlayer($player);
        $this->getServer()->getScheduler()->cancelTask($this->getTaskID($player));
    }

    /*    _____ _                _
     *   / ____| |              | |
     *  | |    | |__   __ _ _ __| |_ ___
     *  | |    | '_ \ / _` | '__| __/ __|
     *  | |____| | | | (_| | |  | |_\__ \
     *   \_____|_| |_|\__,_|_|   \__|___/
     */

    /** @var Charts */
    private $charts;

    /**
     * @return Charts
     */
    public function getCharts(){
        if($this->charts === null){
            $this->charts = new Charts($this);
        }
        return $this->charts;
    }

    /**
     * @param $string
     * @return bool|string
     */
    public function chatMagic($string){
        if(($k = $this->getCharts()->wordFilter($string)) !== true){
            return $k;
        }
        return $this->getCharts()->coloredChat($this->getCharts()->convertSmiles($string)); // First apply smiles, then color them :D
    }

    /*    _____      _
     *   / ____|    (_)
     *  | |     ___  _ _ __  ___
     *  | |    / _ \| | '_ \/ __|
     *  | |___| (_) | | | | \__ \
     *   \_____\___/|_|_| |_|___/
     */

    /**
     * @param SuperPlayer $player
     * @return int
     */
    public function getPlayerCoins(SuperPlayer $player){
        return $this->database->getNested($player->getName() . "coins");
    }

    /**
     * @param SuperPlayer $player
     * @param int $coins
     */
    public function addPlayerCoins(SuperPlayer $player, $coins){
        $this->database->setNested($player->getName() . "coins", $coins);
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

    /** @var array */
    private $disguisedEIDs;

    /**
     * @param $eid
     * @return SuperPlayer|bool
     */
    public function isDisguised($eid){
        if(!isset($this->disguisedEIDs[$eid])){
            return false;
        }else{
            $disguised = $this->disguisedEIDs[$eid];
            if($disguised instanceof SuperPlayer && $disguised->isOnline() && $disguised->isDisguised()){
                return $disguised;
            }else{
                return false;
            }
        }
    }

    /**
     * @param SuperPlayer $player
     */
    public function addDisguisedPlayer(SuperPlayer $player){
        $this->disguisedEIDs[$player->getId()] = $player;
    }

    /**
     * @param SuperPlayer $player
     */
    public function removeDisguisedPlayer(SuperPlayer $player){
        if(isset($this->disguisedEIDs[$player->getId()])){
            unset($this->disguisedEIDs[$player->getId()]);
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

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isMagicClockEnabled(SuperPlayer $player){
        return $player->isMagicClockEnabled();
    }

    /**
     * @param SuperPlayer $player
     * @param bool $state
     */
    public function setMagicClock(SuperPlayer $player, $state){
        $player->setMagicClock($state);
    }

    /**
     * @param SuperPlayer $player
     * @param Item $item
     */
    public function switchMagicClock(SuperPlayer $player, Item $item = null){
        $this->setMagicClock($player, $enabled = !$this->isMagicClockEnabled($player));
        if($item !== null && $player->getInventory()->getItemInHand()->getId() === Item::DYE){
            $player->getInventory()->setItemInHand(Item::get(Item::DYE, (!$enabled ? 8 : 10)));
            $player->sendTip((!$enabled ? TextFormat::GRAY . "Hiding" : TextFormat::GREEN . "Showing") . TextFormat::AQUA . " players...");
        }
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

    /** @var Languages */
    private $language;

    /**
     * @return Languages
     */
    public function getLanguagesAPI(){
        if($this->language === null){
            $this->language = new Languages($this);
        }
        return $this->language;
    }

    /*   _____             _
     *  |  __ \           | |
     *  | |__) |__ _ _ __ | | _____
     *  |  _  // _` | '_ \| |/ / __|
     *  | | \ \ (_| | | | |   <\__ \
     *  |_|  \_\__,_|_| |_|_|\_\___/
     */

    /** @var Ranks */
    private $rank;

    /**
     * @return Ranks
     */
    public function getRanksAPI(){
        if($this->rank === null){
            $this->rank = new Ranks($this);
        }
        return $this->rank;
    }

    /*    _____                                 _                       _
     *   / ____|                               | |                     (_)
     *  | (___   ___ _ ____   _____ _ __       | |_   _ _ __ ___  _ __  _ _ __   __ _
     *   \___ \ / _ | '__\ \ / / _ | '__|  _   | | | | | '_ ` _ \| '_ \| | '_ \ / _` |
     *   ____) |  __| |   \ V |  __| |    | |__| | |_| | | | | | | |_) | | | | | (_| |
     *  |_____/ \___|_|    \_/ \___|_|     \____/ \__,_|_| |_| |_| .__/|_|_| |_|\__, |
     *                                                           | |             __/ |
     *                                                           |_|            |___/
     */

    /** @var array */
    private $servers = [
        #"infected" => "infected.minepocket.com"
    ];

    /**
     * @return array
     */
    public function getServersList(){
        return $this->servers;
    }

    /**
     * @param $name
     * @return null||string
     */
    public function getServerByName($name){
        if(!isset($this->servers[$name])){
            return null;
        }
        return $this->servers[$name];
    }

    /** @var null|ServersListAsyncScheduler */
    private $ipTaskScheduler = null;

    public function getServersUpdaterTask(){
        return $this->ipTaskScheduler;
    }

    /** @var null|ServersIPListUpdater */
    private $ipUpdaterTask = null;

    public function updateAllServers(){
        if($this->ipUpdaterTask === null || $this->ipUpdaterTask->isTerminated()){
            $this->getServer()->getScheduler()->scheduleAsyncTask($task = new ServersIPListUpdater($this, $this->getServersList()));
            $this->ipUpdaterTask = $task;
        }
    }

    /**
     * @param string $name
     * @param string $ip
     */
    public function updateServerIP($name, $ip){
        $this->servers[$name] = $ip;
    }
}