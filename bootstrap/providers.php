<?php

use App\Libs\ThirdParty\Jucai\ServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DbLogServerProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    ServiceProvider::class,
    AppServiceProvider::class,
    DbLogServerProvider::class,
    AdminPanelProvider::class,
];
