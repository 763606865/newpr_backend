<?php

namespace App\Libs\AI;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class, fn (Application $app): AIManager => new AIManager($app));

        $this->app->singleton(AI::class, fn (Application $app): AI => new AI($app->make(AIManager::class)));
    }
}
