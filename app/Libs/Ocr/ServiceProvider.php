<?php

namespace App\Libs\Ocr;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OcrManager::class, fn (Application $app): OcrManager => new OcrManager($app));

        $this->app->singleton(Ocr::class, fn (Application $app): Ocr => new Ocr($app->make(OcrManager::class)));
    }
}
