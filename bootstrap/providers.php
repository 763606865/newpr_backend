<?php

use App\Providers\AppServiceProvider;
use App\Providers\DbLogServerProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    DbLogServerProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
    App\Libs\Amap\ServiceProvider::class,
    App\Libs\Ocr\ServiceProvider::class,
    App\Libs\Oss\ServiceProvider::class,
    App\Libs\ThirdParty\Jucai\ServiceProvider::class,
    App\Libs\ThirdParty\JucaiDT\ServiceProvider::class,
    App\Libs\IM\ServiceProvider::class,
];
