<?php

use App\Libs\ThirdParty\Jucai\ServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DbLogServerProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    DbLogServerProvider::class,
    AdminPanelProvider::class,
    ServiceProvider::class,
];
