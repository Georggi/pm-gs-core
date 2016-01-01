<?php
namespace MinePocketCore\BaseFiles;


use MinePocketCore\Loader;
use pocketmine\block\Air;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\plugin\PluginBase;
use pocketmine\tile\Sign;

abstract class MiniGameProject extends PluginBase{
    /**
     * @param Loader $core
     */
    public abstract function initGames(Loader $core);

    /** @var array */
    private $spawnPoints = [];

    /**
     * @param Level $level
     * @return Location[]
     */
    public final function getWorldSpawnPoints(Level $level){
        if(!isset($this->spawnPoints[$level->getId()]) || count($this->spawnPoints[$level->getId()]) < 2){
            $this->spawnPoints[$level->getId()] = [];
            foreach($level->getTiles() as $tile){
                if($tile instanceof Sign && $tile->getText()[0] === "[spawn]"){
                    $this->spawnPoints[$level->getId()][] = new Location($tile->getFloorX() + 0.5, $tile->getY(), $tile->getFloorZ() + 0.5, $tile->getText()[1], $tile->getText()[2], $level);
                    $level->setBlock($tile->add(0, 0, 0), new Air(), true, false);
                }
            }
        }
        return $this->spawnPoints[$level->getId()];
    }
}