<?php

namespace App\Libs\IM;


use App\Libs\IM\Api\AbstractApi;
use App\Libs\IM\Drivers\AbstractDriver;

class Im
{
    public function __construct(protected ImManager $manager) {}

    public function driver(?string $driver = null): AbstractDriver
    {
        return $this->manager->driver($driver);
    }

    public function api(string $name): AbstractApi
    {
        return $this->driver()->api($name);
    }

    public static function __callStatic(string $name, array $arguments = [])
    {
        return (new static(app(ImManager::class)))->api($name);
    }

    /**
     * Instance proxy for non-static calls (so Facade resolved instance can call ->user())
     *
     * @param string $name
     * @param array $arguments
     * @return \App\Libs\IM\Api\AbstractApi
     */
    public function __call(string $name, array $arguments = [])
    {
        return $this->api($name);
    }
}
