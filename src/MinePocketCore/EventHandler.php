<?php
namespace MinePocketCore;

use MinePocketCore\InternalAPI\SuperPlayer;
use pocketmine\block\Air;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class EventHandler implements Listener{
    /** @var Loader */
    public $plugin;

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerCreationEvent $event
     */
    public function onPlayerCreation(PlayerCreationEvent $event){
        $event->setPlayerClass(SuperPlayer::class);
    }

    /**
     * @param SignChangeEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onSignChange(SignChangeEvent $event){
        $break = " /signbreak/ ";
        if(is_int($s = $this->plugin->chatMagic(implode($break, $event->getLines())))){
            $event->setCancelled(true);
            $event->getPlayer()->kick("%kick." . ($s === 0 ? "advertising" : "swearing"));
            $event->getBlock()->getLevel()->setBlock($event->getBlock(), new Air(), true, true);
        }else{
            $s = explode($break, $s);
            for($i = 0; $i < count($s); $i++){
                $event->setLine($i, $s[$i]);
            }
        }
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onCommandPreProcess(PlayerCommandPreprocessEvent $event){
        $s = $this->plugin->chatMagic($event->getMessage());
        if(is_int($s)){
            $event->setCancelled(true);
            $event->getPlayer()->kick("%kick." . ($s === 0 ? "advertising" : "swearing"));
        }
        if(!($event->getPlayer() instanceof Player) || ($event->getPlayer() instanceof Player && !$this->plugin->isInRegistrationQueue($event->getPlayer()) && $this->plugin->isPlayerAuthenticated($event->getPlayer()))){
            $event->setMessage($s);
        }
    }

    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        if(($b = $this->plugin->getPlayerProfile($event->getPlayer())) !== false && isset($b["is_banned"]) && $b["is_banned"] === 1){
            $event->setKickMessage("%kick.banned");
            $event->setCancelled(true);
        }
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
            if($p->getName() === $event->getPlayer()->getName() && $p->getAddress() !== $event->getPlayer()->getAddress()){
                if($this->plugin->isPlayerAuthenticated($p)){
                    $event->setKickMessage("%kick.loggedin");
                    $event->setCancelled(true);
                }else{
                    $p->kick("%kick.notlogged");
                }
            }
        }
    }

    /**
     * @param PlayerLoginEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onPlayerLogin(PlayerLoginEvent $event){
        $this->plugin->getLanguagesAPI()->initPlayer($event->getPlayer());
        if(!$this->plugin->playerLogin($event->getPlayer(), "", true)){
            $this->plugin->deauthenticatePlayer($event->getPlayer());
        }
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        $this->plugin->updateServerName(false);
        $event->setJoinMessage("");
        if(count($this->plugin->getServer()->getOnlinePlayers()) === 1 && $this->plugin->getMiniGameProject() !== null){
            $this->plugin->initGames();
        }
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
            if(!$this->plugin->isPlayerAuthenticated($p)){
                $event->getPlayer()->hidePlayer($p); // Hide those players that aren't logged
            }
        }
        $event->getPlayer()->sendMessage("%motd");
        if($this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $message = "auth.login.successful";
        }elseif(!$this->plugin->isPlayerRegistered($event->getPlayer())){
            $message = "auth.register.join";
            $this->plugin->addToRegisterQueue($event->getPlayer());
        }else{
            $message = "auth.login.join";
        }
        $event->getPlayer()->sendMessage("%" . $message);
    }

    /**
     * @param PlayerKickEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerKick(PlayerKickEvent $event){
        $this->plugin->updateServerName(true);
        $this->plugin->closePlayer($event->getPlayer());
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onPlayerChat(PlayerChatEvent $event){
        if($this->plugin->isInRegistrationQueue($event->getPlayer())){
            $event->setCancelled(true);
            $this->plugin->setLastTyping($event->getPlayer(), time());
            switch($this->plugin->getNextRegistrationStep($event->getPlayer())){
                case 0: // Password enter
                    if(strpos($event->getMessage(), " ") !== false){
                        $event->getPlayer()->sendMessage("%auth.register.password.invalid");
                    }else{
                        $this->plugin->pushInfoToRegisterQueue($event->getPlayer(), "password", $event->getMessage());
                        $event->getPlayer()->sendMessage("%auth.register.password.confirm");
                    }
                    break;
                case 1: // Password confirmation
                    if(strpos($event->getMessage(), " ") !== false){
                        $event->getPlayer()->sendMessage("%auth.register.password.invalid");
                    }elseif(!$this->plugin->pushInfoToRegisterQueue($event->getPlayer(), "confirm_password", $event->getMessage())){
                        $event->getPlayer()->sendMessage("%auth.register.match");
                    }else{
                        $event->getPlayer()->sendMessage("%auth.register.password.success");
                    }
                    break;
                case 2: // E-mail enter
                    if(!filter_var($event->getMessage(), FILTER_VALIDATE_EMAIL)){
                        $event->getPlayer()->sendMessage("%auth.register.email.invalid");
                    }else{
                        $this->plugin->pushInfoToRegisterQueue($event->getPlayer(), "email", $event->getMessage());
                        $event->getPlayer()->sendMessage("%auth.register.email.confirm");
                    }
                    break;
                case 3: // E-mail confirmation
                    if(!filter_var($event->getMessage(), FILTER_VALIDATE_EMAIL)){
                        $event->getPlayer()->sendMessage("%auth.register.email.invalid");
                    }elseif(!$this->plugin->pushInfoToRegisterQueue($event->getPlayer(), "confirm_email", $event->getMessage())){
                        $event->getPlayer()->sendMessage("%auth.register.email.match");
                    }else{ // Account creation...
                        $this->plugin->pushInfoToRegisterQueue($event->getPlayer(), "confirm_email", $event->getMessage());
                        $event->getPlayer()->sendMessage("%auth.register.email.success");
                        if($this->plugin->getNextRegistrationStep($event->getPlayer()) === true){
                            $event->getPlayer()->sendMessage("You've been successfully registered!\nHave fun!");
                            $event->getPlayer()->sendMessage("%auth.register.successful");
                            foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
                                if($p->getName() === $event->getPlayer()->getName() && $p !== $event->getPlayer()){
                                    $p->kick("%kick.notlogged");
                                }
                            }
                        }else{
                            $event->getPlayer()->sendMessage("%auth.register.failure");
                            $this->plugin->resetAllRegisterInformation($event->getPlayer());
                        }
                    }
                    break;
                default:
                    break;
            }
        }elseif(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
            $this->plugin->setLastTyping($event->getPlayer(), time());
            if(strpos($event->getMessage(), " ") !== false){
                $event->getPlayer()->sendMessage("%auth.register.password.invalid");
            }else{
                if(!$this->plugin->playerLogin($event->getPlayer(), $event->getMessage())){
                    $event->getPlayer()->sendMessage("%auth.login.failure");
                }else{
                    $event->getPlayer()->sendMessage("%auth.login.successful");
                }
            }
        }
    }

    /**
     * @param EntityLevelChangeEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerLevelChange(EntityLevelChangeEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player && $event->getTarget() === $this->plugin->getServer()->getDefaultLevel()){
            foreach($event->getTarget()->getPlayers() as $p){
                if($this->plugin->isMagicClockEnabled($p)){
                    $p->hidePlayer($player);
                }
                if($this->plugin->isMagicClockEnabled($player)){
                    $player->hidePlayer($p);
                }
            }
        }
    }

    /**
     * @param InventoryPickupItemEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerItemPickup(InventoryPickupItemEvent $event){
        $player = $event->getInventory()->getHolder();
        if($player instanceof Player){
            if(!$this->plugin->isPlayerAuthenticated($player)){
                $event->setCancelled(true);
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event){
        $event->setCancelled(true);
    }

    /**
     * @param InventoryOpenEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerInventoryOpen(InventoryOpenEvent $event){
        if(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
        }
    }

    /**
     * @param BlockPlaceEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBlockPlace(BlockPlaceEvent $event){
        if(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
        }
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBlockBreak(BlockBreakEvent $event){
        if(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event){
        if(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
        }elseif(($game = $this->plugin->getGame($event->getPlayer()->getLevel())) !== false){
            $game->onPlayerInteract($event);
        }elseif($event->getItem()->getId() === Item::DYE && ($event->getItem()->getDamage() === 10 || $event->getItem()->getDamage() === 8)){
            $this->plugin->switchMagicClock($event->getPlayer(), $event->getItem());
        }
    }

    /**
     * Passed events...
     */

    /**
     * @param PlayerMoveEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        if(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
        }
        if(($game = $this->plugin->getGame($event->getPlayer()->getLevel())) !== false){
            $game->onPlayerMove($event);
        }
    }

    /**
     * @param EntityMotionEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerMotionChange(EntityMotionEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player && ($game = $this->plugin->getGame($player->getLevel())) !== false){
            $game->onPlayerMotionChange($event);
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerAttack(EntityDamageEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            if(!$this->plugin->isPlayerAuthenticated($player)){
                $event->setCancelled(true);
            }
            if(($game = $this->plugin->getGame($player->getLevel())) !== false){
                $game->onEntityDamage($event);
            }
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onItemConsume(PlayerItemConsumeEvent $event){
        if(!$this->plugin->isPlayerAuthenticated($event->getPlayer())){
            $event->setCancelled(true);
        }
        if(($game = $this->plugin->getGame($event->getPlayer()->getLevel())) !== false){
            $game->onItemConsume($event);
        }
    }

    /**
     * @param PlayerDeathEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerDeath(PlayerDeathEvent $event){
        if(($game = $this->plugin->getGame($event->getEntity()->getLevel())) !== false){
            $game->onPlayerDeath($event);
        }
    }

    /**
     * @param PlayerRespawnEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerRespawn(PlayerRespawnEvent $event){
        if(($game = $this->plugin->getGame($event->getPlayer()->getLevel())) !== false){
            $game->onPlayerRespawn($event);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        $this->plugin->updateServerName(true);
        $event->setQuitMessage("");
        $this->plugin->closePlayer($event->getPlayer());
        if(($game = $this->plugin->getGame($event->getPlayer()->getLevel())) !== false){
            $game->onPlayerQuit($event);
        }
    }
}