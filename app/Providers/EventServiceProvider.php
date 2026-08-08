<?php

namespace DarkOak\Providers;

use DarkOak\Models\User;
use DarkOak\Models\Server;
use DarkOak\Models\Subuser;
use DarkOak\Models\EggVariable;
use DarkOak\Observers\UserObserver;
use DarkOak\Observers\ServerObserver;
use DarkOak\Observers\SubuserObserver;
use DarkOak\Listeners\TwoFactorListener;
use DarkOak\Listeners\RevocationListener;
use DarkOak\Observers\EggVariableObserver;
use DarkOak\Listeners\AuthenticationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $subscribe = [
        AuthenticationListener::class,
        TwoFactorListener::class,
        RevocationListener::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        User::observe(UserObserver::class);
        Server::observe(ServerObserver::class);
        Subuser::observe(SubuserObserver::class);
        EggVariable::observe(EggVariableObserver::class);
    }
}
