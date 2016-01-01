<?php
namespace MinePocketCore;

#use MinePocketCore\Tasks\Updater;
use MinePocketCore\BaseFiles\BaseMiniGame;
use MinePocketCore\BaseFiles\MiniGameProject;
use MinePocketCore\InternalAPI\Charts;
use MinePocketCore\InternalAPI\Languages;
use MinePocketCore\InternalAPI\SuperPlayer;
use MinePocketCore\Tasks\DatabasePing;
use MinePocketCore\Tasks\DataCleaner;
use MinePocketCore\Tasks\PopupMessages\PopupSend;
use MinePocketCore\Tasks\PopupMessages\RandomizeMessages;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;

class Loader extends PluginBase{
    /** @var \mysqli */
    public $database;

    public function onEnable(){
        // Before anything else, lets clean up...
        $this->recursiveDirectoryCleaner($this->getServer()->getDataPath() . "players" . DIRECTORY_SEPARATOR, false);
        $this->recursiveDirectoryCleaner($this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR, false);

        // Database
        $this->database = new \mysqli("192.99.152.145", "developm_develop", "L3ZqZm+JMNKx", "developm_test", 3306);
        if($this->database->connect_error){
            $this->getLogger()->critical("Couldn't connect to MySQL: ". $this->database->connect_error);
            $this->getServer()->forceShutdown();
        }else{
            $this->getLogger()->info(TextFormat::GREEN . "Connection to the Database established!");
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new DatabasePing($this), 600);
        }
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);

        // Tasks
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new DataCleaner($this), 6000);
            // Popup Messages
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new RandomizeMessages($this), 200);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new PopupSend($this), 5);

        // Import spawn...
        $this->saveResource("world.zip", true);
        $this->unzip($this->getDataFolder() . "world.zip", $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . "world" . DIRECTORY_SEPARATOR);

        //Charts
        $this->charts = new Charts($this);
        // Languages
        $this->language = new Languages($this);

        // Server dynamic name...
        $this->updateServerName();
    }

    public function onDisable(){
        $this->database->close();
        $this->unregisterSigns();
        $this->forceGamesClose();
        #$this->getServer()->getScheduler()->scheduleAsyncTask(new Updater($this->getServer()->getDataPath() . "plugins" . DIRECTORY_SEPARATOR));
    }

    /**
     * @param string $dir
     */
    public function recursiveDirectoryCleaner($dir, $removeParent = false){
        foreach(scandir($dir) as $world){
            if($world !== "." && $world !== ".."){
                if(preg_match("/\w\.\w/", $world) !== 1){
                    $this->recursiveDirectoryCleaner($dir . $world . DIRECTORY_SEPARATOR);
                }else{
                    unlink($dir . $world);
                }
            }
        }
        if($removeParent){
            rmdir($dir);
        }
    }

    /** .----------------. .----------------. .----------------.
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

    /**
     * @return bool
     */
    public function isHub(){
        return $this->getMiniGameProject() === null;
    }

    /**
     * @param bool $playerQuit
     */
    public function updateServerName($playerQuit = false){
        $count = count($this->getServer()->getOnlinePlayers());
        $counter = ($playerQuit ? $count - 1 : $count) . "/" . $this->getServer()->getMaxPlayers();
        $color = count($this->getServer()->getOnlinePlayers()) >= $this->getServer()->getMaxPlayers() ? TextFormat::RED : TextFormat::GREEN;
        $counter = $color . "/right/ " . TextFormat::GRAY . $counter . $color . " /left/";
        // The following just checks for "smiles" because we already use "Formatted Color codes" and we will not put bad words into it xD
        $this->getServer()->getNetwork()->setName($this->getCharts()->convertSmiles(TextFormat::AQUA . "/diamond/ " . TextFormat::BLUE . "MinePocket " . TextFormat::WHITE . "Network " . TextFormat::AQUA . "/diamond/ " . TextFormat::BOLD . $counter));
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

    /**  __  __       _ _   _        _
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
        return $this->language;
    }

    /**   _____ _                _
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
        return $this->charts;
    }

    /**
     * @param $string
     * @return bool|string
     */
    public function chatMagic($string){
        if(!($k = $this->getCharts()->wordFilter($string))){
            return $k;
        }
        return $this->getCharts()->coloredChat($this->getCharts()->convertSmiles($string)); // First apply smiles, then color them :D
    }

    /**                _   _
     *      /\        | | | |
     *     /  \  _   _| |_| |__
     *    / /\ \| | | | __| '_ \
     *   / ____ | |_| | |_| | | |
     *  /_/    \_\__,_|\__|_| |_|
     */

    const API = "http://minepocket.com/api.php";
    const XFAPI = "http://minepocket.com/xfapi.php";
    const HASH = "x8e8fca2dc6f896fd7cb4cw0031ba244";

    ////////////////////////////////////
    /**
     * @param array $args
     * @return array
     */
    private function minepocketAPI(array $args){
        return json_decode(Utils::getURL(self::API . "?" . $this->pasteValuesForURL($args)), true);
    }

    /**
     * @param array $args
     * @return array
     */
    private function playerXFAPI(array $args){
        return json_decode(Utils::getURL(self::XFAPI . "?" . $this->pasteValuesForURL($args)), true);
    }

    /**
     * @param array $args
     * @return string
     */
    private function pasteValuesForURL(array $args){
        $values = "";
        foreach($args as $k => $v){
            $values .= "&" .  $k . "=" . $v;
        }
        return substr($values, 1); // So it doesn't return the "&" a the beggining of the line...
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
            "hash" => self::HASH]);
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
        $result = $this->database->query("SELECT * FROM account_system WHERE username = '" . $this->database->escape_string($player->getName()) . "'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["username"])){
                unset($data["username"]);
            }
            return $data;
        }
        return false;
    }

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isPlayerAuthenticated(SuperPlayer $player){
        return $player->getSession()->isAuthenticated();
    }

    /**
     * @param SuperPlayer $player
     * @param string $password
     * @param bool $ipMatch
     * @return bool
     */
    public function playerLogin(SuperPlayer $player, $password = "", $ipMatch = false){
        /*if(!$this->getPlayerProfile($player)){
            return false;
        }*/
        $a = $this->getPlayerAccount($player);
        if($ipMatch && ((is_array($a) && $player->getAddress() === $a["a"]) || ($this->isPlayerRegistered($player) && $this->playerXFAPI(["action" => "sameip", "username" => $player->getName(), "newip" => $player->getAddress(), "hash" => self::HASH]) === true))){
            $this->authenticatePlayer($player);
            return true;
        }
        if(trim($password) === ""){
            return false;
        }
        $json = $this->minepocketAPI([
            "action" => "authenticate",
            "username" => $player->getName(),
            "password" => $password
        ]);
        if(isset($json["error"]) && $json["error"] === 5){
            return false;
        }
        $this->authenticatePlayer($player);
        return true;
    }

    /**
     * @param SuperPlayer $player
     */
    public function authenticatePlayer(SuperPlayer $player){
        $player->getSession()->authenticate();
    }

    /**
     * @param SuperPlayer $player
     */
    public function deauthenticatePlayer(SuperPlayer $player){
        $player->getSession()->deauthenticate();
    }

    /**
     * @param SuperPlayer $player
     * @return PermissionAttachment
     */
    public function getPermissionAttachment(SuperPlayer $player){
        return (($attachment = $player->getSession()->getPermissionAttachment()) === null ? false : $attachment);
    }

    /**
     * @param SuperPlayer $player
     * @return bool|int
     */
    public function getTaskID(SuperPlayer $player){
        return (($id = $player->getSession()->getTaskID()) === null ? false : $id);
    }

    /**
     * @param SuperPlayer $player
     * @param int|null $taskId
     */
    public function setTaskID(SuperPlayer $player, $taskId){
        $player->getSession()->setTaskID($taskId);
    }

    /**
     * @param SuperPlayer $player
     * @return int|null
     */
    public function getLastTyping(SuperPlayer $player){
        return (($time = $player->getSession()) === null ? false : $time);
    }

    /**
     * @param SuperPlayer $player
     * @param int|null $time
     */
    public function setLastTyping(SuperPlayer $player, $time){
        $player->getSession()->setLastTyping($time);
    }

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isPlayerRegistered(SuperPlayer $player){
        return $this->getPlayerProfile($player) !== false; // Need to do "!==" due to it may return an array if player is registered xD
    }

    /**
     * @param SuperPlayer $player
     * @param string $password
     * @param string $email
     * @return bool
     */
    public function registerPlayer(SuperPlayer $player, $password, $email){
        if(!$this->isPlayerRegistered($player)){
            $this->getLogger()->debug("Registrating...");
            $json = $this->minepocketAPI([
                "action" => "register",
                "hash" => self::HASH,
                "username" => $player->getName(),
                "password" => $password,
                "email" => $email
            ]);
            if(isset($json["error"]) && $json["error"] === 7){
                return false;
            }
        }
        $this->authenticatePlayer($player);
        return true;
    }

    /**
     * @param SuperPlayer $player
     */
    public function registerPlayerToDatabase(SuperPlayer $player){
        // Little account things
        $result = $this->database->query("SELECT * FROM account_system WHERE username = '" . ($name = $this->database->escape_string($player->getName())) . "'");
        if(!$result instanceof \mysqli_result){
            $this->database->query("INSERT INTO account_system (username, ip) VALUES ('" . $name . "', '" . $this->database->escape_string($player->getAddress()) . "'");
        }else{
            $this->database->query("UPDATE account_system SET ip = '" . $this->database->escape_string($player->getAddress()) . "' WHERE username = " . $name);
        }
        // Coins system
        $result = $this->database->query("SELECT * FROM coins_system WHERE username = '" . $name . "'");
        if(!$result instanceof \mysqli_result){
            $this->database->query("INSERT INTO coins_system (username, coins) VALUES ('" . $name . "', 100)");
        }
        //TODO More things as being implemented
    }

    /**
     * @param SuperPlayer $player
     */
    public function addToRegisterQueue(SuperPlayer $player){
        $player->getSession()->setIntoRegistrationProcess();
    }

    /**
     * @param SuperPlayer $player
     * @return bool
     */
    public function isInRegistrationQueue(SuperPlayer $player){
        return $player->getSession()->isInRegistrationProcess();
    }

    /**
     * @param SuperPlayer $player
     * @return bool|int
     */
    public function getNextRegistrationStep(SuperPlayer $player){
        $s = $player->getSession();
        if(is_int($r = $s->getNextRegistrationStep())){
            return $r;
        }elseif($s->getRegistrationInformation()["confirm_email"] !== null){
            return $this->registerPlayer($player, $s->getRegistrationInformation()["password"], $s->getRegistrationInformation()["email"]);
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
        return $player->getSession()->receiveRegistrationProcessInformation($key, $value);
    }

    /**
     * @param SuperPlayer $player
     */
    public function resetAllRegisterInformation(SuperPlayer $player){
        $player->getSession()->resetRegisterInformation();
    }

    /**
     * @param SuperPlayer $player
     */
    public function closePlayer(SuperPlayer $player){
        $this->deauthenticatePlayer($player);
        $this->getServer()->getScheduler()->cancelTask($this->getTaskID($player));
    }

    /**   _____      _
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
        $result = $this->database->query("SELECT * FROM coins_system WHERE username = '" . $this->database->escape_string($player->getName()) . "'");
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            return $data["coins"];
        }
        return false;
    }

    /**
     * @param SuperPlayer $player
     * @param int $coins
     */
    public function addPlayerCoins(SuperPlayer $player, $coins){
        $this->database->query("UPDATE coins_system SET coins = " . ($this->getPlayerCoins($player) + $coins) . " WHERE username = '" . $this->database->escape_string($player->getName()) . "'");
    }

    /**     /\
     *     /  \   _ __ ___ _ __   __ _ ___
     *    / /\ \ | '__/ _ | '_ \ / _` / __|
     *   / ____ \| | |  __| | | | (_| \__ \
     *  /_/    \_|_|  \___|_| |_|\__,_|___/
     */

    /** @var Sign[]|null */
    private $signs = null;

    public function registerSigns(){
        foreach($this->getServer()->getDefaultLevel()->getTiles() as $tile){
            if($tile instanceof Sign && strtolower($tile->getText()[0]) === "[arena]"){
                $this->signs[] = $tile;
            }
        }
    }

    public function unregisterSigns(){
        if($this->signs === null){
            return;
        }
        foreach($this->signs as $sign){
            $sign->setText("[Arena]", "", $sign->getText()[2]);
        }
    }

    /**
     * @return Sign[]
     */
    public function getRegisteredSigns(){
        if($this->signs === null){
            $this->registerSigns();
        }
        return $this->signs;
    }

    /**   _____
     *   / ____|
     *  | |  __  __ _ _ __ ___   ___ ___
     *  | | |_ |/ _` | '_ ` _ \ / _ / __|
     *  | |__| | (_| | | | | | |  __\__ \
     *   \_____|\__,_|_| |_| |_|\___|___/
     */

    /** @var null|MiniGameProject */
    private $gameProject = null;

    /**
     * @param MiniGameProject $game
     */
    public function registerMiniGameProject(MiniGameProject $game){
        $this->gameProject = $game;
        $game->saveResource("worlds.zip");
        $this->unzip($game->getDataFolder() . "worlds.zip", $game->getDataFolder());
    }

    /**
     * @return MiniGameProject|null
     */
    public function getMiniGameProject(){
        return $this->gameProject;
    }

    public function initGames(){
        if($this->getMiniGameProject() !== null){
            $this->getMiniGameProject()->initGames($this);
        }
    }

    /** @var BaseMiniGame[] */
    private $games = [];

    /**
     * @param BaseMiniGame $game
     */
    public function addNewGame(BaseMiniGame $game){
        $this->games[$game->getLevel()->getId()] = $game;
        if(count($game->getPlayers()) >= $game->getMaxPlayers()){
            $color = TextFormat::GOLD . TextFormat::ITALIC;
        }else{
            $color = TextFormat::GREEN . TextFormat::BOLD;
        }
        $game->getSign()->setText($color . "[Join] ", $color . count($game->getLevel()->getPlayers()) . TextFormat::RESET . TextFormat::AQUA . " / " . $color . $game->getMaxPlayers(), TextFormat::LIGHT_PURPLE . preg_replace("/[0-9]+/", "", $game->getLevel()->getName()));
    }

    /**
     * @param BaseMiniGame $game
     */
    public function removeGame(BaseMiniGame $game){
        if(isset($this->games[$game->getLevel()->getId()])){
            unlink($this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $game->getLevel()->getName());
            unset($this->games[$game->getLevel()->getId()]);
        }
    }

    /**
     * ONLY USE THIS FUNCTION WHEN THE SERVER IS SHUTTING DOWN...
     */
    public function forceGamesClose(){
        foreach($this->games as $game){
            $game->endGame(true);
        }
    }

    /**
     * @param Level $level
     * @return bool|BaseMiniGame
     */
    public function getGame(Level $level){
        if(!isset($this->games[$level->getId()])){
            return false;
        }
        return $this->games[$level->getId()];
    }

    /**
     * @param string $name
     * @param $zipDir
     * @return Level
     */
    public function generateNewLevel($name, $zipDir){
        $dir = $this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR;
        $count = [];
        foreach(scandir($dir) as $world){
            if(($world !== "." && $world !== "..") && strpos($world, $name) !== false){
                $count[] = preg_replace("/[^0-9]+/", "", $world);
            }
        }
        $newName = $name . (count($count) > 0 ? max($count) + 1 : 1);
        mkdir($dir = $dir . $newName . DIRECTORY_SEPARATOR);
        $this->unzip($zipDir, $dir);
        $this->getServer()->loadLevel($newName);
        return $this->getServer()->getLevelByName($newName);
    }

    /**
     * @param string $origin
     * @param string $destination
     * @return bool
     */
    private function unzip($origin, $destination){
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

    /**  __  __             _       _____ _            _
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
        return $player->getSession()->isMagicClockEnabled();
    }

    /**
     * @param SuperPlayer $player
     * @param bool $state
     */
    public function setMagicClock(SuperPlayer $player, $state){
        $player->getSession()->setMagicClock($state);
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

    /**   _____                                 _                       _
     *   / ____|                               | |                     (_)
     *  | (___   ___ _ ____   _____ _ __       | |_   _ _ __ ___  _ __  _ _ __   __ _
     *   \___ \ / _ | '__\ \ / / _ | '__|  _   | | | | | '_ ` _ \| '_ \| | '_ \ / _` |
     *   ____) |  __| |   \ V |  __| |    | |__| | |_| | | | | | | |_) | | | | | (_| |
     *  |_____/ \___|_|    \_/ \___|_|     \____/ \__,_|_| |_| |_| .__/|_|_| |_|\__, |
     *                                                           | |             __/ |
     *                                                           |_|            |___/
     */
}