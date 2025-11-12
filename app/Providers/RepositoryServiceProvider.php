<?php

namespace DarkOak\Providers;

use Illuminate\Support\ServiceProvider;
use DarkOak\Repositories\Eloquent\EggRepository;
use DarkOak\Repositories\Eloquent\NestRepository;
use DarkOak\Repositories\Eloquent\NodeRepository;
use DarkOak\Repositories\Eloquent\TaskRepository;
use DarkOak\Repositories\Eloquent\UserRepository;
use DarkOak\Repositories\Eloquent\ThemeRepository;
use DarkOak\Repositories\Eloquent\ApiKeyRepository;
use DarkOak\Repositories\Eloquent\ServerRepository;
use DarkOak\Repositories\Eloquent\SessionRepository;
use DarkOak\Repositories\Eloquent\SubuserRepository;
use DarkOak\Repositories\Eloquent\DatabaseRepository;
use DarkOak\Repositories\Eloquent\LocationRepository;
use DarkOak\Repositories\Eloquent\ScheduleRepository;
use DarkOak\Repositories\Eloquent\SettingsRepository;
use DarkOak\Repositories\Eloquent\AllocationRepository;
use DarkOak\Contracts\Repository\EggRepositoryInterface;
use DarkOak\Repositories\Eloquent\EggVariableRepository;
use DarkOak\Contracts\Repository\NestRepositoryInterface;
use DarkOak\Contracts\Repository\NodeRepositoryInterface;
use DarkOak\Contracts\Repository\TaskRepositoryInterface;
use DarkOak\Contracts\Repository\UserRepositoryInterface;
use DarkOak\Repositories\Eloquent\DatabaseHostRepository;
use DarkOak\Contracts\Repository\ThemeRepositoryInterface;
use DarkOak\Contracts\Repository\ApiKeyRepositoryInterface;
use DarkOak\Contracts\Repository\ServerRepositoryInterface;
use DarkOak\Repositories\Eloquent\ServerVariableRepository;
use DarkOak\Contracts\Repository\SessionRepositoryInterface;
use DarkOak\Contracts\Repository\SubuserRepositoryInterface;
use DarkOak\Contracts\Repository\DatabaseRepositoryInterface;
use DarkOak\Contracts\Repository\LocationRepositoryInterface;
use DarkOak\Contracts\Repository\ScheduleRepositoryInterface;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Contracts\Repository\AllocationRepositoryInterface;
use DarkOak\Contracts\Repository\EggVariableRepositoryInterface;
use DarkOak\Contracts\Repository\DatabaseHostRepositoryInterface;
use DarkOak\Contracts\Repository\ServerVariableRepositoryInterface;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register all the repository bindings.
     */
    public function register(): void
    {
        // Eloquent Repositories
        $this->app->bind(AllocationRepositoryInterface::class, AllocationRepository::class);
        $this->app->bind(ApiKeyRepositoryInterface::class, ApiKeyRepository::class);
        $this->app->bind(DatabaseRepositoryInterface::class, DatabaseRepository::class);
        $this->app->bind(DatabaseHostRepositoryInterface::class, DatabaseHostRepository::class);
        $this->app->bind(EggRepositoryInterface::class, EggRepository::class);
        $this->app->bind(EggVariableRepositoryInterface::class, EggVariableRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, LocationRepository::class);
        $this->app->bind(NestRepositoryInterface::class, NestRepository::class);
        $this->app->bind(NodeRepositoryInterface::class, NodeRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
        $this->app->bind(ServerRepositoryInterface::class, ServerRepository::class);
        $this->app->bind(ServerVariableRepositoryInterface::class, ServerVariableRepository::class);
        $this->app->bind(SessionRepositoryInterface::class, SessionRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(ThemeRepositoryInterface::class, ThemeRepository::class);
        $this->app->bind(SubuserRepositoryInterface::class, SubuserRepository::class);
        $this->app->bind(TaskRepositoryInterface::class, TaskRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}

