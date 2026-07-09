<?php

namespace App\Libs\ThirdParty\JucaiDT;

use App\Libs\ThirdParty\Application;

class JucaiDT extends Application
{
    public static function __callStatic($method, $args)
    {
        $class = 'App\\Libs\\ThirdParty\\JucaiDT\\Api\\'.ucfirst($method);
        if (class_exists($class)) {
            return new $class(...$args);
        }
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
