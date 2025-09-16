<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CurrentUser;

class CurrentUserServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register CurrentUser as singleton in the service container
        $this->app->singleton(CurrentUser::class, function ($app) {
            return new CurrentUser();
        });

        // Also register with an alias for easier access
        $this->app->alias(CurrentUser::class, 'current.user');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Initialize the service after all providers are registered
        // Only in web requests, not in console commands
        if ($this->app->runningInConsole() === false) {
            $currentUser = $this->app->make(CurrentUser::class);
            $currentUser->initialize();
        }
    }
}
