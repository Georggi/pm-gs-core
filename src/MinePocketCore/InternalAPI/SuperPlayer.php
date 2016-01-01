<?php
namespace MinePocketCore\InternalAPI;

use MinePocketCore\Loader;
use pocketmine\network\Network;
use pocketmine\network\SourceInterface;
use pocketmine\Player;

class SuperPlayer extends Player{
    /** @var Loader */
    private $core;
    /** @var CoreSession */
    private $coreSession;

    public function __construct(SourceInterface $interface, $clientID, $ip, $port){
        parent::__construct($interface, $clientID, $ip, $port);
        $this->core = $this->getServer()->getPluginManager()->getPlugin("MinePocketCore");
        $this->coreSession = new CoreSession($this->core, $this);
        $this->core->getLanguagesAPI()->getPlayerLanguage($this);
    }

    /**
     * @return Loader
     */
    public function getCore(){
        return $this->core;
    }

    /**
     * @return CoreSession
     */
    public function getSession(){
        return $this->coreSession;
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
        if(preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $ip, $match) !== 1){
            return false;
        }
        $pk = new ServerJumpPacket($ip, $port);
        $this->dataPacket($pk->setChannel(Network::CHANNEL_ENTITY_SPAWNING));
        return true;
    }

    /**
     * @param \pocketmine\event\TextContainer|string $message
     */
    public function sendMessage($message){
        parent::sendMessage($this->getCore()->chatMagic($this->getTranslation($message)));
    }

    /**
     * @param string $message
     */
    public function sendPopup($message){
        parent::sendPopup($this->getCore()->chatMagic($this->getTranslation($message)));
    }

    /**
     * @param string $message
     */
    public function sendTip($message){
        parent::sendTip($this->getCore()->chatMagic($this->getTranslation($message)));
    }

    /**
     * @param string $message
     * @param bool $meh Not really used, just for compatibility things xD
     * @return bool
     */
    public function kick($message = '', $meh = false){
        return parent::kick($this->getTranslation($message . "%kick.sub"), false); // Set 'isAdmin' to false so it doesn't display "Kicked by admin" message
    }

    /**
     * @param string $string
     * @return string
     */
    private function getTranslation($string){
        return str_replace("@player", $this->getName(), preg_replace_callback(
            "/\%(\w\.|\w)+/",
            function(array $matches){
                return $this->getCore()->getLanguagesAPI()->getTranslation($matches[0], $this->getSession()->getLanguage());
            },
            strtolower($string)
        ));
    }
}