<?php
namespace Core;

use Core\InternalAPI\CustomMoveEntityPacket;
use Core\InternalAPI\SuperPlayer;
use pocketmine\block\Air;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\FallingSandFallEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\item\Item;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;

class EventHandler implements Listener{
    /** @var Loader */
    public $plugin;

    /**
     * @param Loader $plugin
     */
    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    public function onFallingSandFall(FallingSandFallEvent $event){
        //if($event->getEntity()->getDataPropertyType("NoFall") === 1){
            $event->setCancelled();
       //}
    }
    /**
     * @param QueryRegenerateEvent $event
     *
     * @ignoreCancelled true
     * @priority HIGHEST
     */
    public function onQueryRegenerate(QueryRegenerateEvent $event){
        $event->setListPlugins(false);
        // TODO $event->setMaxPlayerCount();
    }

    /**
     * @param PlayerCreationEvent $event
     *
     * @ignoreCancelled true
     */
    public function onPlayerCreation(PlayerCreationEvent $event){
        $event->setPlayerClass(SuperPlayer::class);
    }

    /**
     * @param DataPacketSendEvent $event
     *
     * @ignoreCancelled true
     */
    public function onPacketSend(DataPacketSendEvent $event){
        $packet = $event->getPacket();
        if(
            ($eid = isset($packet->eid) ? $packet->eid : (isset($packet->entities) ? array_keys($packet->entities)[0] : false)) &&
            ($disguised = $this->plugin->isDisguised($eid)) !== false /*&&
            $event->getPlayer()->getId() !== $disguised->getId()*/
        ){
            //TODO: FIX ISSUES WITH DISSAPEARING DISGUISED ENTITIES
            if($packet instanceof MovePlayerPacket){
                $pk = new CustomMoveEntityPacket();
                $pk->entities = [$disguised->getId() => [$disguised->getId(), $packet->x, $packet->y, $packet->z, $packet->yaw, $packet->pitch]];
            }elseif($packet instanceof MoveEntityPacket && !($packet instanceof CustomMoveEntityPacket)){
                $pk = new CustomMoveEntityPacket();
                $pk->entities = $packet->entities;
                foreach($pk->entities as $id => $values){
                    $values[2] = $disguised->getY();
                    $pk->entities[$eid] = $values;
                }
            }elseif($packet instanceof AddPlayerPacket){
                $pk = new AddEntityPacket();
                $pk->eid = $disguised->getId();
                $pk->type = $disguised->getDisguiseID();
                $pk->x = $packet->x;
                $pk->y = $packet->y - 1;
                $pk->z = $packet->z;
                $pk->yaw = $packet->yaw;
                $pk->pitch = $packet->pitch;
                $pk->metadata = [];
            }elseif($packet instanceof RemovePlayerPacket){
                $pk = new RemoveEntityPacket();
                $pk->eid = $disguised->getId();
            }
            if(isset($pk)){
                $event->getPlayer()->dataPacket($pk);
                $event->setCancelled(true);
            }
        }
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
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(substr($event->getMessage(), 0, 1) !== "/" && $player->isInRegistrationProcess() !== true && $player->isAuthenticated()){
            if(is_int($s = $this->plugin->chatMagic($event->getMessage()))){
                $event->setCancelled(true);
                $event->getPlayer()->kick("%kick." . ($s === 0 ? "advertising" : "swearing"));
            }else{
                $event->setMessage($s);
            }
        }
    }

    /**
     * @param PlayerPreLoginEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerPreLogin(PlayerPreLoginEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(($b = $this->plugin->getPlayerProfile($player)) !== false && isset($b["is_banned"]) && $b["is_banned"] === 1){
            $event->getPlayer()->kick("%kick.banned");
        }
        foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
            /** @var SuperPlayer $p */
            if($p->getName() === $event->getPlayer()->getName() && $p->getAddress() !== $event->getPlayer()->getAddress()){
                if($p->isAuthenticated()){
                    $event->getPlayer()->kick("%kick.loggedin");
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
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        $this->plugin->getLanguagesAPI()->initPlayer($player);
        if(!$this->plugin->playerLogin($player, "", true)){
            $this->plugin->deauthenticatePlayer($player);
        }
    }

    /**
     * @param PlayerJoinEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerJoin(PlayerJoinEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        $this->plugin->updateServerName(false); // TODO: Remove
        $event->setJoinMessage("");
        foreach($player->getLevel()->getPlayers() as $p){
            /** @var SuperPlayer $p */
            if(!$p->isAuthenticated()){
                $event->getPlayer()->hidePlayer($p); // Hide those players that aren't logged
            }
        }
        $player->sendMessage("%motd", [$player->getName()]);
        if($player->isAuthenticated()){
            $message = "auth.login.successful";
        }elseif(!$this->plugin->isPlayerRegistered($player)){
            $message = "auth.register.join";
            $player->setIntoRegistrationProcess();
        }else{
            $message = "auth.login.join";
        }
        $player->sendMessage("%" . $message);
    }

    /**
     * @param PlayerKickEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onPlayerKick(PlayerKickEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        $this->plugin->updateServerName(true);
        $this->plugin->closePlayer($player);
    }

    /**
     * @param PlayerChatEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled false
     */
    public function onPlayerChat(PlayerChatEvent $event){
        $event->setFormat("%s:%s"); // [PLAYER-DISPLAY]:[MESSAGE]
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if($player->isInRegistrationProcess()){
            $event->setCancelled(true);
            $player->setLastTyping(time());
            switch($this->plugin->getNextRegistrationStep($player)){
                case 0: // Password enter
                    if(strpos($event->getMessage(), " ") !== false){
                        $player->sendMessage("%auth.register.password.invalid");
                    }else{
                        $player->receiveRegistrationProcessInformation("password", $event->getMessage());
                        $player->sendMessage("%auth.register.password.confirm");
                    }
                    break;
                case 1: // Password confirmation
                    if(strpos($event->getMessage(), " ") !== false){
                        $player->sendMessage("%auth.register.password.invalid");
                    }elseif(!$player->receiveRegistrationProcessInformation("confirm_password", $event->getMessage())){
                        $player->sendMessage("%auth.register.match");
                    }else{
                        $player->sendMessage("%auth.register.password.success");
                    }
                    break;
                case 2: // E-mail enter
                    if(!filter_var($event->getMessage(), FILTER_VALIDATE_EMAIL)){
                        $player->sendMessage("%auth.register.email.invalid");
                    }else{
                        $player->receiveRegistrationProcessInformation("email", $event->getMessage());
                        $player->sendMessage("%auth.register.email.confirm");
                    }
                    break;
                case 3: // E-mail confirmation
                    if(!filter_var($event->getMessage(), FILTER_VALIDATE_EMAIL)){
                        $player->sendMessage("%auth.register.email.invalid");
                    }elseif(!$player->receiveRegistrationProcessInformation("confirm_email", $event->getMessage())){
                        $player->sendMessage("%auth.register.email.match");
                    }else{ // Account creation...
                        $player->receiveRegistrationProcessInformation("confirm_email", $event->getMessage());
                        $player->sendMessage("%auth.register.email.success");
                        if($this->plugin->getNextRegistrationStep($player) === true){
                            $player->sendMessage("You've been successfully registered!\nHave fun!");
                            $player->sendMessage("%auth.register.successful");
                            foreach($this->plugin->getServer()->getOnlinePlayers() as $p){
                                if($p->getName() === $player->getName() && $p !== $player){
                                    $p->kick("%kick.notlogged");
                                }
                            }
                        }else{
                            $player->sendMessage("%auth.register.failure");
                            $this->plugin->resetAllRegisterInformation($player);
                        }
                    }
                    break;
                default:
                    break;
            }
        }elseif(!$player->isAuthenticated()){
            $event->setCancelled(true);
            $player->setLastTyping(time());
            if(strpos($event->getMessage(), " ") !== false){
                $player->sendMessage("%auth.register.password.invalid");
            }else{
                if(!$this->plugin->playerLogin($player, $event->getMessage())){
                    $player->sendMessage("%auth.login.failure");
                }else{
                    $player->sendMessage("%auth.login.successful");
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
        if($player instanceof SuperPlayer && $event->getTarget() === $this->plugin->getServer()->getDefaultLevel()){
            foreach($event->getTarget()->getPlayers() as $p){
                /** @var SuperPlayer $p */
                if($p->isMagicClockEnabled()){
                    $p->hidePlayer($player);
                }
                if(!$p->isAuthenticated() || $p->isMagicClockEnabled()){
                    $player->hidePlayer($p);
                }
            }
            // TODO: Restore default inventory xD
        }
    }

    /**
     * @param InventoryPickupItemEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerItemPickup(InventoryPickupItemEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getInventory()->getHolder();
        if(!$player->isAuthenticated()){
            $event->setCancelled(true);
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
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(!$player->isAuthenticated()){
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
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(!$player->isAuthenticated()){
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
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(!$player->isAuthenticated()){
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @ignoreCancelled true
     */
    public function onPlayerInteract(PlayerInteractEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(!$player->isAuthenticated()){
            $event->setCancelled(true);
        }elseif($event->getItem()->getId() === Item::CLOCK && $player->getLevel() === $this->plugin->getServer()->getDefaultLevel()){
            $player->switchMagicClock();
        }
    }

    /**
     * @param PlayerMoveEvent $event
     *
     * @ignoreCancelled true
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(!$player->isAuthenticated()){
            $event->setCancelled(true);
        }
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onPlayerAttack(EntityDamageEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getEntity();
        if(!$player->isAuthenticated()){
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerItemConsumeEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onItemConsume(PlayerItemConsumeEvent $event){
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        if(!$player->isAuthenticated()){
            $event->setCancelled(true);
        }
    }

    /**
     * @param PlayerQuitEvent $event
     *
     * @priority MONITOR
     * @ignoreCancelled true
     */
    public function onPlayerQuit(PlayerQuitEvent $event){
        $this->plugin->updateServerName(true); // TODO: Remove
        $event->setQuitMessage("");
        /** @var SuperPlayer $player */
        $player = $event->getPlayer();
        $this->plugin->closePlayer($player);
    }
}