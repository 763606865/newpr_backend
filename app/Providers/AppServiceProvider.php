<?php

namespace App\Providers;

use App\Models\AdminUser;
use App\Models\Area;
use App\Models\BUser;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ImSystemUser;
use App\Models\Rc\Job;
use App\Models\Rc\Resume;
use App\Models\Rc\UserIm;
use App\Models\School;
use App\Models\Token;
use App\Observers\EmployeeObserver;
use App\Services\MetaService;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MetaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }
        Relation::morphMap([
            'job' => Job::class,
            'resume' => Resume::class,
            'company' => Company::class,
            'school' => School::class,
            'area' => Area::class,
            'admin_user' => AdminUser::class,
            'b_user' => BUser::class,
            'rc_user_im' => UserIm::class,
            'im_system_user' => ImSystemUser::class,
        ]);

        Select::configureUsing(static function (Select $component): void {
            // Use Filament's JS select globally to replace browser-native style.
            $component->native(false);
        });

        Export::polymorphicUserRelationship();
        Passport::useTokenModel(Token::class);
        Employee::observe(EmployeeObserver::class);
    }
}
