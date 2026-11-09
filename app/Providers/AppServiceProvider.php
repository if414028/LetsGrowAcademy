<?php

namespace App\Providers;

use App\Console\LocalServeCommand;
use Illuminate\Foundation\Console\ServeCommand;
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
        $this->app->extend(ServeCommand::class, fn () => new LocalServeCommand);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        UserHierarchy::observe(UserHierarchyObserver::class);
    }
}
