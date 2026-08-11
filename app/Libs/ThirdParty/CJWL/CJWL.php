<?php

namespace App\Libs\ThirdParty\CJWL;

use App\Libs\ThirdParty\Application;

class CJWL extends Application
{
    public static function __callStatic($method, $args)
    {
        $class = 'App\\Libs\\ThirdParty\\CJWL\\Api\\'.ucfirst($method);
        if (class_exists($class)) {
            return new $class(...$args);
        }
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }
}
