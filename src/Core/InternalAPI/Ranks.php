<?php
namespace Core\InternalAPI;

use Core\BaseFiles\BaseAPI;

class Ranks extends BaseAPI{
    /** @var array */
    private $guest = [
        "prefix" => "[Guest]"
    ];
    /** @var array */
    private $builder = [
        "inheritance" => "guest",
        "prefix" => "[Builder]"
    ];
    /** @var array */
    private $dev = [
        "inheritance" => "builder",
        "prefix" => "[Dev]"
    ];
    /** @var array */
    private $mod = [
        "inheritance" => "dev",
        "prefix" => "[Mod]"
    ];
    /** @var array */
    private $admin = [
        "inheritance" => "mod",
        "prefix" => "[Admin]"
    ];
    /** @var array */
    private $owner = [
        "inheritance" => "admin",
        "prefix" => "[Owner]"
    ];

    /**
     * @param SuperPlayer $player
     * @return string
     */
    public function getPlayerRank(SuperPlayer $player){
        if($player->getRank() === null){
            if(!$player->isAuthenticated()){
                $player->setRank("Guest");
            }else{
                // TODO: Fetch from DB
                $player->setRank("Owner");
            }
        }
        return $player->getRank();
    }

    public function getRankPrefix($rank){
        if(isset($this->{$rank}) && is_array($rank = $this->{$rank}) && isset($rank["prefix"])){
            return $rank["prefix"];
        }
        return null;
    }

    /** @var bool */
    private $calculated = false;

    /**
     * @param string $rank
     * @return array
     */
    public function getRankPermissions($rank){
        if(isset($this->{$rank}) && is_array($rank)){
            if(!$this->calculated){
                $this->calculated = true;
                $this->recalculatePermissions($rank);
            }
            return $this->{$rank};
        }
        return [];
    }

    /**
     * @param string $rank
     * @return array
     */
    public function recalculatePermissions($rank){
        $rank = strtolower($rank);
        $permissions = [];
        if(isset($this->{$rank}["permissions"]) && is_array($p = $this->{$rank}["permissions"])){
            if(isset($this->{$rank}["inheritance"])){
                foreach($this->recalculatePermissions($this->{$rank}["inheritance"]) as $sp => $sb){
                    $permissions[$sp] = $sb;
                }
            }else{
                foreach($this->getCore()->getServer()->getPluginManager()->getPermissions() as $perm){
                    if(!isset($permissions[$perm->getName()])){
                        $perm[$perm->getName()] = false;
                    }
                }
            }
            uksort($permissions, [SuperPlayer::class, "orderPermissionsCallback"]);
            $this->{$rank}["permissions"] = $permissions;
            return $this->{$rank};
        }
        return $permissions;
    }
}