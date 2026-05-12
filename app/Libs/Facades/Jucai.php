<?php

namespace App\Libs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Libs\ThirdParty\Jucai\Api\Sms sms()
 */
class Jucai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\ThirdParty\Jucai\Jucai::class;
    }
}
