<?php

namespace App\Libs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Libs\ThirdParty\CJWL\Api\Position position()
 * @method static \App\Libs\ThirdParty\CJWL\Api\RecruitmentDetail recruitmentDetail()
 */
class CJWL extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Libs\ThirdParty\CJWL\CJWL::class;
    }
}
