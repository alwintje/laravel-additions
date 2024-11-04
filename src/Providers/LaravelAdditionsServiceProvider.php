<?php

namespace Kroesen\LaravelAdditions\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Kroesen\LaravelAdditions\Services\Contenter;

class LaravelAdditionsServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        parent::register();
        $this->app->bind('contenter' , Contenter::class);
    }

    public function boot()
    {
        Blade::componentNamespace('Kroesen\\LaravelAdditions\\Views', 'laravel-additions');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'laravel-additions');
    }
}
