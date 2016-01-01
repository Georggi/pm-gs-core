<?php
namespace Core\Tasks;

use Core\BaseFiles\BaseUpdater;

class Updater extends BaseUpdater{
    public function __construct($directory){
        parent::__construct($directory, "core", "Core");
    }
}