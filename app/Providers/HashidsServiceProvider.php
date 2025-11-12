<?php

namespace DarkOak\Providers;

use DarkOak\Extensions\Hashids;
use Illuminate\Support\ServiceProvider;
use DarkOak\Contracts\Extensions\HashidsInterface;

class HashidsServiceProvider extends ServiceProvider
{
    /**
     * Register the ability to use Hashids.
     */
    public function register(): void
    {
        $this->app->singleton(HashidsInterface::class, function () {
            return new Hashids(
                config('hashids.salt', ''),
                config('hashids.length', 0),
                config('hashids.alphabet', 'abcdefghijkmlnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890')
            );
        });

        $this->app->alias(HashidsInterface::class, 'hashids');
    }
}

