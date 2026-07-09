<?php

namespace App\Libs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Libs\ThirdParty\JucaiDT\Api\Resume resume()
 */
class JucaiDT extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\ThirdParty\JucaiDT\JucaiDT::class;
    }
}
