<?php

namespace App\Libs\ThirdParty;

use Illuminate\Contracts\Foundation\Application as LaravelApplication;

class Application
{
    public function __construct(public LaravelApplication $app, public array $config) {}

    public static function __callStatic($method, $args)
    {
        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    public function __call($method, $args)
    {
        $args['app'] = $this;

        return static::__callStatic($method, $args);
    }
}
