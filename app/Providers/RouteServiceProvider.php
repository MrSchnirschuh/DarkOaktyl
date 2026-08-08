<?php

namespace DarkOak\Providers;

use DarkOak\Models\Database;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use DarkOak\Http\Middleware\TrimStrings;
use DarkOak\Http\Middleware\EnsureStatefulRequests;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use DarkOak\Http\Middleware\AdminAuthenticate;
use DarkOak\Http\Middleware\RequireTwoFactorAuthentication;
use DarkOak\Http\Controllers\Base\IndexController;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected const FILE_PATH_REGEX = '/^\/api\/client\/servers\/([a-z0-9-]{36})\/files(\/?$|\/(.)*$)/i';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Disable trimming string values when requesting file information — it isn't helpful
        // and messes up the ability to actually open a directory that ends with a space.
        TrimStrings::skipWhen(function (Request $request) {
            return preg_match(self::FILE_PATH_REGEX, $request->getPathInfo()) === 1;
        });

        // This is needed to make use of the "resolveRouteBinding" functionality in the
        // model. Without it, you'll never trigger that logic flow thus resulting in a 404
        // error because we request databases with a HashID, and not with a normal ID.
        Route::model('database', Database::class);

        $this->routes(function () {
            // Determine if we're using subdomain routing
            $panelSubdomain = env('PANEL_SUBDOMAIN', 'panel');
            $rootDomain = env('APP_ROOT_DOMAIN', null);
            
            // Panel routes closure
            $panelRoutes = function () {
                Route::middleware('web')->group(function () {
                    Route::middleware(['auth.session', RequireTwoFactorAuthentication::class])
                        ->group(base_path('routes/base.php'));

                    Route::middleware(['auth.session', RequireTwoFactorAuthentication::class, AdminAuthenticate::class])
                        ->prefix('/admin')
                        ->group(base_path('routes/admin.php'));

                    Route::middleware('guest')->prefix('/auth')->group(base_path('routes/auth.php'));

                    // Public SPA routes (no auth required — legal pages, etc.)
                    Route::prefix('/legal')->withoutMiddleware(['auth.session', RequireTwoFactorAuthentication::class])->group(function () {
                        Route::get('/{path?}', [IndexController::class, 'index'])->where('path', '.*');
                    });
                });

                Route::middleware(['api', RequireTwoFactorAuthentication::class])->group(function () {
                    Route::middleware(['application-api', 'throttle:api.application'])
                        ->prefix('/api/application')
                        ->scopeBindings()
                        ->group(base_path('routes/api-application.php'));

                    Route::middleware(['client-api', 'throttle:api.client'])
                        ->prefix('/api/client')
                        ->scopeBindings()
                        ->group(base_path('routes/api-client.php'));
                });

                // Public legal API — no auth required
                Route::prefix('/api/legal')
                    ->middleware(['throttle:api.client', EnsureStatefulRequests::class])
                    ->group(base_path('routes/api-legal.php'));

                Route::middleware(['daemon', 'throttle:api.daemon'])
                    ->prefix('/api/remote')
                    ->scopeBindings()
                    ->group(base_path('routes/api-remote.php'));
            };
            
            if ($rootDomain) {
                // Domain separation enabled
                // Public website routes on root domain
                Route::domain($rootDomain)
                    ->middleware('web')
                    ->group(base_path('routes/public.php'));
                
                // Panel routes on subdomain
                Route::domain($panelSubdomain . '.' . $rootDomain)->group($panelRoutes);
            } else {
                // No domain separation, load all panel routes normally
                $panelRoutes();
            }
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Authentication rate limiting. For login and checkpoint endpoints we'll apply
        // a limit of 10 requests per minute, for the forgot password endpoint apply a
        // limit of two per minute for the requester so that there is less ability to
        // trigger email spam.
        RateLimiter::for('authentication', function (Request $request) {
            if ($request->route()->named('auth.post.forgot-password')) {
                return Limit::perMinute(2)->by($request->ip());
            }

            return Limit::perMinute(10)->by($request->ip());
        });

        // Configure the throttles for both the application and client APIs below.
        // This is configurable per-instance in "config/http.php". By default this
        // limiter will be tied to the specific request user, and falls back to the
        // request IP if there is no request user present for the key.
        //
        // This means that an authenticated API user cannot use IP switching to get
        // around the limits.
        RateLimiter::for('api.client', function (Request $request) {
            $key = optional($request->user())->uuid ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.client_period'),
                config('http.rate_limit.client')
            )->by($key);
        });

        RateLimiter::for('api.application', function (Request $request) {
            $key = optional($request->user())->uuid ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.application_period'),
                config('http.rate_limit.application')
            )->by($key);
        });

        // Daemon API rate limiting - Protects Wings daemon communication endpoints
        // from DoS attacks while allowing legitimate high-frequency operations
        RateLimiter::for('api.daemon', function (Request $request) {
            // Use node ID if available, otherwise fall back to IP
            $key = optional($request->attributes->get('node'))->id ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.daemon_period'),
                config('http.rate_limit.daemon')
            )->by('daemon:' . $key)->response(function (Request $request) {
                // Log rate limit hits for monitoring
                \Illuminate\Support\Facades\Log::warning('Daemon API rate limit exceeded', [
                    'ip' => $request->ip(),
                    'node_id' => optional($request->attributes->get('node'))->id,
                    'route' => $request->route()->getName() ?? $request->path(),
                ]);

                return new \Illuminate\Http\Response('Too Many Requests - Daemon API rate limit exceeded', 429);
            });
        });

        // Stricter rate limiting for high-impact daemon endpoints (commands, SFTP)
        RateLimiter::for('api.daemon.command', function (Request $request) {
            $key = optional($request->attributes->get('node'))->id ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.daemon_command_period'),
                config('http.rate_limit.daemon_command')
            )->by('daemon-cmd:' . $key)->response(function (Request $request) {
                \Illuminate\Support\Facades\Log::warning('Daemon Command API rate limit exceeded', [
                    'ip' => $request->ip(),
                    'node_id' => optional($request->attributes->get('node'))->id,
                    'route' => $request->route()->getName() ?? $request->path(),
                ]);

                return new \Illuminate\Http\Response('Too Many Requests - Command rate limit exceeded', 429);
            });
        });

        RateLimiter::for('api.daemon.sftp', function (Request $request) {
            $key = optional($request->attributes->get('node'))->id ?: $request->ip();

            return Limit::perMinutes(
                config('http.rate_limit.daemon_sftp_period'),
                config('http.rate_limit.daemon_sftp')
            )->by('daemon-sftp:' . $key)->response(function (Request $request) {
                \Illuminate\Support\Facades\Log::warning('Daemon SFTP API rate limit exceeded', [
                    'ip' => $request->ip(),
                    'node_id' => optional($request->attributes->get('node'))->id,
                    'route' => $request->route()->getName() ?? $request->path(),
                ]);

                return new \Illuminate\Http\Response('Too Many Requests - SFTP rate limit exceeded', 429);
            });
        });
    }
}

