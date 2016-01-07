<?php
namespace Core\InternalAPI\Commands;

use Core\BaseFiles\BaseCommand;
use Core\InternalAPI\SuperPlayer;
use Core\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DisguiseCommand extends BaseCommand{
    public function __construct(Loader $core){
        parent::__construct($core, "disguise", "Disguise as any Entity!", "/disguise <entity> [NBT]", ["d"]);
    }

    public function execute(CommandSender $sender, $alias, array $args){
        if(!$sender->isOp()){
            $sender->sendMessage(TextFormat::RED . "You're not allowed to use this...");
            return false;
        }elseif(!$sender instanceof SuperPlayer){
            $sender->sendMessage("You're not a SuperPlayer! Please do it with a qualified player instance.");
            return false;
        }
        switch(count($args)){
            case 0:
                if(!$sender->isDisguised()){
                    $sender->sendMessage($this->getUsage());
                    return false;
                }else{
                    $sender->stopDisguise();
                    $sender->sendMessage("Disguise removed!");
                }
                break;
            default:
                $entity = array_shift($args);
                if($sender->getDisguiseFromString($entity) === 0 && !$sender->isValidID($entity)){
                    $sender->sendMessage("Invalid Entity/ID, please choose another one");
                    return false;
                }
                $nbt = null;
                if(count($args) > 0){
                    $nbt = implode(" ", $args);
                }
                if(!$sender->startDisguise($entity, $nbt)){
                    $sender->sendMessage("Failed starting disguise!");
                    return false;
                }
                $sender->sendMessage("Disguise applied!");
                break;
        }
        return true;
    }
}