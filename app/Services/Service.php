<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class Service
{
    protected static array $instances = [];

    public static function make()
    {
        $class = static::class;
        if (! isset(static::$instances[$class])) {
            static::$instances[$class] = new static;
        }

        return static::$instances[$class];
    }

    public function getGuardName(): string
    {
        return Auth::getDefaultDriver();
    }
}
