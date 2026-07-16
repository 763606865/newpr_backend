<?php

namespace App\Libs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Libs\IM\Api\AbstractApi user()
 * @method static \App\Libs\IM\Api\AbstractApi conversation()
 */
class Im extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\IM\Im::class;
    }
}
