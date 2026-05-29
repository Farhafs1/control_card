<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL; // Imported URL facade
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // --- FORCE HTTPS ON RENDER AND PRODUCTION ENVIRONMENTS ---
        if (config('app.env') === 'production' || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            URL::forceScheme('https');
        }

        $this->configureDefaults();

        // --- GLOBAL SETTINGS LOADER ---
        if (Schema::hasTable('settings')) {
            $siteSettings = Setting::first() ?? Setting::create([
                'fiscal_year' => 2026,
                'budget_status' => 'active',
                'app_name' => 'Budget Management System',
                'state_name' => 'Katsina State Government',
                'currency_symbol' => '₦',
                'allow_overspending' => false,
            ]);
            
            View::share('siteSettings', $siteSettings);
        }

        // --- AUTHENTICATION GATES ---

        // 1. GATE: Only allow Admins
        Gate::define('admin-only', function (User $user) {
            return $user->role === 'admin';
        });

        // 2. GATE: Only allow Budget Officers
        Gate::define('officer-only', function (User $user) {
            return $user->role === 'officer';
        });

        // 3. GATE: Global Safety (Only active users)
        Gate::define('active-user', function (User $user) {
            return (bool) $user->is_active;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}