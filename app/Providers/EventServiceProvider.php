<?php

namespace DarkOak\Providers;

use DarkOak\Models\User;
use DarkOak\Models\Server;
use DarkOak\Models\Subuser;
use DarkOak\Models\EggVariable;
use DarkOak\Observers\UserObserver;
use DarkOak\Listeners\Domains\DomainEventSubscriber;
use DarkOak\Listeners\Emails\EmailTriggerEventSubscriber;
use DarkOak\Observers\ServerObserver;
use DarkOak\Observers\SubuserObserver;
use DarkOak\Observers\EggVariableObserver;
use DarkOak\Listeners\Auth\AuthenticationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $subscribe = [
        AuthenticationListener::class,
        EmailTriggerEventSubscriber::class,
        DomainEventSubscriber::class,
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

