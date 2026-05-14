<?php

use App\Providers\AppServiceProvider;
use App\Providers\DbLogServerProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    App\Libs\ThirdParty\Jucai\ServiceProvider::class,
    AppServiceProvider::class,
    DbLogServerProvider::class,
    AdminPanelProvider::class,
    App\Libs\Oss\ServiceProvider::class
];
