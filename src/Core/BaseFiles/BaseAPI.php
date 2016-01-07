<?php
namespace Core\BaseFiles;

use Core\Loader;

abstract class BaseAPI{
    /** @var Loader */
    private $core;

    /**
     * @param Loader $core
     */
    public function __construct(Loader $core){
        $this->core = $core;
    }

    /**
     * @return Loader
     */
    public function getCore(){
        return $this->core;
    }
}