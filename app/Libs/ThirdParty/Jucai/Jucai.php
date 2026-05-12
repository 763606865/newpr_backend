<?php

namespace App\Libs\ThirdParty\Jucai;

use App\Libs\ThirdParty\Application;

class Jucai extends Application
{
    public static function __callStatic($method, $args)
    {
        $class = 'App\\Libs\\ThirdParty\\Jucai\\Api\\'.ucfirst($method);
        if (class_exists($class)) {
            return new $class(...$args);
        }
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
