<?php
namespace Core\InternalAPI;

use Core\BaseFiles\BaseAPI;
use Core\Loader;

class Ranks extends BaseAPI{
    const ADMIN = 0;
    const MODERATOR = 1;
    const STAFF = 2;
    const GUEST = 3;
    private static $list;

    public function __construct(Loader $core){
        parent::__construct($core);

        $c = new \ReflectionClass($this);
        foreach($c->getConstants() as $k => $v){
            self::$list[$v] = ucfirst(strtolower($k));
        }
    }

    /**
     * @param int $rank
     * @return string
     */
    public static function identifyRank($rank){
        return isset(self::$list[$rank]) ? self::$list[$rank] : "Guest";
    }

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