<?php

use App\Providers\AppServiceProvider;
use App\Providers\DbLogServerProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    App\Libs\Amap\ServiceProvider::class,
    App\Libs\Oss\ServiceProvider::class,
    App\Libs\ThirdParty\Jucai\ServiceProvider::class,
    AppServiceProvider::class,
    DbLogServerProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
];
