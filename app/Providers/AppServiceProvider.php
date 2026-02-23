<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\UserHierarchy;
use App\Observers\UserHierarchyObserver;

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
        UserHierarchy::observe(UserHierarchyObserver::class);
    }
}
