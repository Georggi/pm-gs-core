<?php
namespace Core\BaseFiles;

use Core\Loader;
use pocketmine\command\Command;
use pocketmine\command\PluginIdentifiableCommand;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand{
    /** @var Loader */
    private $core;

    /**
     * @param Loader $core
     * @param string $name
     * @param null|string $description
     * @param array|\string[] $usage
     * @param array $alias
     */
    public function __construct(Loader $core, $name, $description, $usage, array $alias){
        parent::__construct($name, $description, $usage, $alias);
        $this->core = $core;
    }

    /**
     * @return Loader
     */
    public final function getPlugin(){
        return $this->core;
    }
}