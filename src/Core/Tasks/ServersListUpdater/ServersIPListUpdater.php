<?php
namespace Core\Tasks\ServersListUpdater;

use Core\Loader;
use pocketmine\scheduler\AsyncTask;

class ServersIPListUpdater extends AsyncTask{
    /** @var Loader */
    private $core;
    /** @var array */
    private $servers;

    public function __construct(Loader $core, array $serverList){
        $this->core = $core;
        $this->servers = $serverList;
    }

    public function onRun(){
        $newList = [];
        foreach($this->servers as $name => $ip){
            if(($newIP = gethostbyname($origin = $name . "minepocket.com")) !== $origin && $newIP !== $ip){
                $newList[$name] = $newIP;
            }
        }
        $this->setResult($newList);
    }

    public function onCompletion(){
        foreach($this->getResult() as $name => $ip){
            $this->core->updateServerIP($name, $ip);
        }
    }
}