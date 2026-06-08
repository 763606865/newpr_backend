<?php

namespace App\Libs\Amap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Amap::class, function (Application $app): Amap {
            return new Amap(config('amap', []));
        });
    }
}
