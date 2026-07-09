<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Im extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'im';
    }
}
