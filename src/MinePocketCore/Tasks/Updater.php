<?php
namespace MinePocketCore\Tasks;

use MinePocketCore\BaseFiles\BaseUpdater;

class Updater extends BaseUpdater{
    public function __construct($directory){
        parent::__construct($directory, "core", "MinePocketCore");
    }
}