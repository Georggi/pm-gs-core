<?php
namespace Core\BaseFiles;

use Core\InternalAPI\SuperPlayer;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;

abstract class BaseInventory extends PlayerInventory{
    /**
     * @param SuperPlayer $player
     * @param Item[] $items
     */
    public function __construct(SuperPlayer $player, array $items){
        parent::__construct($player);
        foreach($items as $slot => $item){
            $this->setItem($slot, $item);
        }
    }
}