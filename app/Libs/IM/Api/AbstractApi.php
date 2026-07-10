<?php

namespace App\Libs\IM\Api;

use App\Libs\IM\Drivers\AbstractDriver;

abstract class AbstractApi
{
    public function __construct(protected AbstractDriver $driver)
    {

    }

    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }
}
