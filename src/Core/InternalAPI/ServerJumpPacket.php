<?php
namespace Core\InternalAPI;

use pocketmine\network\protocol\DataPacket;

class ServerJumpPacket extends DataPacket{
    const NETWORK_ID = 0x1b;

    public $ip;
    public $port = 19132;

    public function pid(){
        return 0x1b;
    }

    protected function putAddress($ip, $port){
        $this->putByte(4); // IPv4
        foreach(explode(".", $ip) as $b){
            $this->putByte((~((int) $b)) & 0xff);
        }
        $this->putShort($port);
    }

    public function decode(){
    }

    public function encode(){
        $this->reset();
        $this->putAddress($this->ip, $this->port);
    }
}