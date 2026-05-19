<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\Token;
use App\Observers\EmployeeObserver;
use Filament\Forms\Components\Select;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Select::configureUsing(static function (Select $component): void {
            // Use Filament's JS select globally to replace browser-native style.
            $component->native(false);
        });

        Passport::useTokenModel(Token::class);
        Employee::observe(EmployeeObserver::class);
    }
}
