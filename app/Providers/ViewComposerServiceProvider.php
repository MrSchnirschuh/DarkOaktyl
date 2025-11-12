<?php

namespace DarkOak\Providers;

use Illuminate\Support\ServiceProvider;
use DarkOak\Http\ViewComposers\AssetComposer;
use DarkOak\Http\ViewComposers\ThemeComposer;
use DarkOak\Http\ViewComposers\DarkOakComposer;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function boot(): void
    {
        $this->app->make('view')->composer('*', AssetComposer::class);
        $this->app->make('view')->composer('*', ThemeComposer::class);
        $this->app->make('view')->composer('*', DarkOakComposer::class);
    }
}

