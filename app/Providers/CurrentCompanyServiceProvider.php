<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CurrentCompany;

class CurrentCompanyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register CurrentCompany as singleton in the service container
        $this->app->singleton(CurrentCompany::class, function ($app) {
            return new CurrentCompany();
        });

        // Also register with an alias for easier access
        $this->app->alias(CurrentCompany::class, 'current.company');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Initialize the service after all providers are registered
        if ($this->app->runningInConsole() === false) {
            $currentCompany = $this->app->make(CurrentCompany::class);
            $currentCompany->initialize();
        }
    }
}
