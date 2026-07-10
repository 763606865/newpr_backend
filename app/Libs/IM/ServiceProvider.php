<?php

namespace App\Libs\IM;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImManager::class, fn (Application $app): ImManager => new ImManager($app));

        $this->app->singleton(Im::class, fn (Application $app): Im => new Im($app->make(ImManager::class)));
    }
}
