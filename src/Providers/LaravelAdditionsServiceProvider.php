<?php

namespace Kroesen\LaravelAdditions\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Kroesen\LaravelAdditions\Commands\Archiving;
use Kroesen\LaravelAdditions\Commands\UpdateArchivedTables;
use Kroesen\LaravelAdditions\Middleware\EncryptCookies;
use Kroesen\LaravelAdditions\Services\Contenter;

class LaravelAdditionsServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->bind('contenter' , Contenter::class);
        $this->app->bind(EncryptCookies::class , EncryptCookies::class);
        $this->mergeConfigFrom(__DIR__ . '/../config/laravel_additions.php', 'laravel_additions');

        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateArchivedTables::class,
                Archiving::class,
            ]);
        }
    }

    public function boot()
    {
        Blade::componentNamespace('Kroesen\\LaravelAdditions\\Views', 'laravel-additions');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'laravel-additions');
    }
}
