<?php

namespace App\Libs\ThirdParty\JucaiDT;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(JucaiDT::class, function (Application $app) {
            return new JucaiDT(app: $app, config: config('jucai.dt'));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
