<?php
namespace MinePocketCore\InternalAPI;

use pocketmine\network\protocol\DataPacket;

class ServerJumpPacket extends DataPacket{
    protected $ip;
    protected $port = 19132;

    public function __construct($ip, $port){
        $this->ip = $ip;
        $this->port = $port;
    }

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