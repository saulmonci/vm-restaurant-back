<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Repositories\BaseRepositoryInterface::class, \App\Repositories\BaseRepository::class);
        $this->app->bind(\App\Repositories\CompanyRepository::class, function ($app) {
            return new \App\Repositories\CompanyRepository(new \App\Models\Company);
        });
        $this->app->bind(\App\Repositories\MenuItemRepository::class, function ($app) {
            return new \App\Repositories\MenuItemRepository(new \App\Models\MenuItem);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
