<?php

namespace Winterpk\LaravelStagingSync;

use Illuminate\Support\ServiceProvider;
use Winterpk\LaravelStagingSync\Console\Commands\SyncDatabase;
use Winterpk\LaravelStagingSync\Console\Commands\ClearBackups;

class LaravelStagingSyncServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-staging-sync.php'),
            ], 'config');

            $this->commands([
                SyncDatabase::class,
                ClearBackups::class
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-staging-sync');
    }
}
