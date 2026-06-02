<?php

use Illuminate\Support\Facades\Route;
use DarkOak\Http\Controllers\Api\Client;
use DarkOak\Http\Controllers\Api\Client\Notifications\PushSubscriptionController;
use DarkOak\Http\Controllers\Api\Client\AutoScalingController;
use DarkOak\Http\Controllers\Api\Client\BillingController as ClientBillingController;
use DarkOak\Http\Controllers\Api\Client\ServerTemplateController;
use DarkOak\Http\Controllers\Api\Client\RegionController;
use DarkOak\Http\Controllers\Api\Client\Organizations\OrganizationController;
use DarkOak\Http\Controllers\Api\Client\LinksController;
use DarkOak\Http\Controllers\Api\Client\AccountController;
use DarkOak\Http\Controllers\Api\Client\PasskeyController;
use DarkOak\Http\Middleware\BillingEnabled;
use DarkOak\Http\Middleware\SuspendedAccount;
use DarkOak\Http\Middleware\Activity\ServerSubject;
use DarkOak\Http\Middleware\Activity\AccountSubject;
use DarkOak\Http\Middleware\RequireTwoFactorAuthentication;
use DarkOak\Http\Middleware\Api\Client\Server\BillingUpgradesEnabled;
use DarkOak\Http\Middleware\Api\Client\Server\ResourceBelongsToServer;
use DarkOak\Http\Middleware\Api\Client\Server\AuthenticateServerAccess;


/*
|--------------------------------------------------------------------------
| Client API Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/client
|
*/

Route::prefix('/')->middleware([SuspendedAccount::class])->group(function () {
    // Dashboard root
    Route::get('/', [Client\ClientController::class, 'index'])->name('api:client.index');
    Route::get('/permissions', [Client\ClientController::class, 'permissions']);
    Route::get('/links', [Client\LinkController::class, 'index']);

    // Server Groups
    Route::prefix('/groups')->group(function () {
        Route::get('/', [Client\ServerGroupController::class, 'index']);
        Route::post('/', [Client\ServerGroupController::class, 'store']);
        Route::patch('/{id}', [Client\ServerGroupController::class, 'update']);
        Route::delete('/{id}', [Client\ServerGroupController::class, 'delete']);
        Route::post('/{id}/add', [Client\ServerGroupController::class, 'add']);
        Route::post('/{id}/remove', [Client\ServerGroupController::class, 'remove']);
    });

    // Account (2FA excluded from 2FA requirement)
    Route::prefix('/account')->middleware(AccountSubject::class)->group(function () {
        Route::prefix('/')->withoutMiddleware(RequireTwoFactorAuthentication::class)->group(function () {
            Route::get('/', [Client\AccountController::class, 'index'])->name('api:client.account');
            Route::get('/two-factor', [Client\TwoFactorController::class, 'index']);
            Route::post('/two-factor', [Client\TwoFactorController::class, 'store']);
            Route::post('/two-factor/disable', [Client\TwoFactorController::class, 'delete']);
        });

        Route::put('/email', [Client\AccountController::class, 'updateEmail'])->name('api:client.account.update-email');
        Route::put('/password', [Client\AccountController::class, 'updatePassword'])->name('api:client.account.update-password');

        Route::get('/activity', Client\ActivityLogController::class)->name('api:client.account.activity');

        // API Keys
        Route::get('/api-keys', [Client\ApiKeyController::class, 'index']);
        Route::post('/api-keys', [Client\ApiKeyController::class, 'store']);
        Route::delete('/api-keys/{identifier}', [Client\ApiKeyController::class, 'delete']);

        // SSH Keys
        Route::prefix('/ssh-keys')->group(function () {
            Route::get('/', [Client\SSHKeyController::class, 'index']);
            Route::post('/', [Client\SSHKeyController::class, 'store']);
            Route::post('/remove', [Client\SSHKeyController::class, 'delete']);
        });

        // Passkeys (WebAuthn)
        Route::prefix('/passkeys')->group(function () {
            Route::get('/', [PasskeyController::class, 'index']);
            Route::post('/', [PasskeyController::class, 'store']);
            Route::post('/options', [PasskeyController::class, 'options']);
            Route::patch('/{id}', [PasskeyController::class, 'update']);
            Route::post('/remove', [PasskeyController::class, 'delete']);
        });

        // Auth Login Method
        Route::get('/auth-login-method', [AccountController::class, 'authLoginMethod']);
        Route::put('/auth-login-method', [AccountController::class, 'updateAuthLoginMethod']);

        // Tickets
        Route::prefix('/tickets')->group(function () {
            Route::get('/', [Client\TicketController::class, 'index']);
            Route::post('/', [Client\TicketController::class, 'store']);
            Route::get('/{ticket:id}', [Client\TicketController::class, 'view']);
            Route::delete('/{ticket:id}', [Client\TicketController::class, 'delete']);
            Route::post('/{ticket:id}/messages', [Client\TicketController::class, 'message']);
        });

        Route::post('/setup', [Client\AccountController::class, 'setup']);

        // Push Notifications (service worker)
        Route::prefix('/notifications/push')->group(function () {
            Route::get('/config', [PushSubscriptionController::class, 'config']);
            Route::post('/subscribe', [PushSubscriptionController::class, 'store']);
            Route::post('/test', [PushSubscriptionController::class, 'test']);
            Route::delete('/unsubscribe', [PushSubscriptionController::class, 'delete']);
            Route::patch('/preferences', [PushSubscriptionController::class, 'updatePreferences']);
        });

        // Account Appearance
        Route::get('/appearance', [AccountController::class, 'appearance']);
        Route::put('/appearance', [AccountController::class, 'updateAppearance']);
    });

    // DarkOaktyl Usage-Based Billing
    Route::prefix('/billing')->group(function () {
        Route::get('/balance', [ClientBillingController::class, 'balance']);
        Route::get('/history', [ClientBillingController::class, 'history']);
        Route::get('/summary', [ClientBillingController::class, 'summary']);
        Route::get('/servers/{server}/estimate', [ClientBillingController::class, 'estimate']);
        Route::get('/transactions', [ClientBillingController::class, 'transactions']);
        Route::post('/credits', [ClientBillingController::class, 'addCredits']);
        Route::patch('/threshold', [ClientBillingController::class, 'updateThreshold']);
    });

    // Auto-Scaling
    Route::prefix('/servers/{server}/autoscale')->group(function () {
        Route::get('/', [AutoScalingController::class, 'show']);
        Route::post('/', [AutoScalingController::class, 'update']);
        Route::delete('/', [AutoScalingController::class, 'destroy']);
        Route::get('/history', [AutoScalingController::class, 'history']);
        Route::post('/evaluate', [AutoScalingController::class, 'evaluate']);
    });

    // Server Templates (One-Click Apps)
    Route::prefix('/templates')->group(function () {
        Route::get('/', [ServerTemplateController::class, 'index']);
        Route::get('/featured', [ServerTemplateController::class, 'featured']);
        Route::get('/types', [ServerTemplateController::class, 'types']);
        Route::get('/search', [ServerTemplateController::class, 'search']);
        Route::get('/type/{type}', [ServerTemplateController::class, 'byType']);
        Route::get('/{uuid}', [ServerTemplateController::class, 'show']);
        Route::post('/{uuid}/deploy', [ServerTemplateController::class, 'deploy']);
    });

    // Multi-Region
    Route::prefix('/regions')->group(function () {
        Route::get('/', [RegionController::class, 'index']);
        Route::get('/default', [RegionController::class, 'default']);
        Route::get('/recommend', [RegionController::class, 'recommend']);
        Route::get('/{code}', [RegionController::class, 'show']);
        Route::get('/{code}/nodes', [RegionController::class, 'nodes']);
        Route::get('/{code}/stats', [RegionController::class, 'stats']);
    });

    // Client Links
    Route::get('/links', [LinksController::class, 'index']);

    // Organizations (Teams)
    Route::prefix('/organizations')->group(function () {
        Route::get('/', [OrganizationController::class, 'index']);
        Route::post('/', [OrganizationController::class, 'store']);
        Route::get('/{organization}', [OrganizationController::class, 'show']);
        Route::patch('/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/{organization}', [OrganizationController::class, 'destroy']);

        // Members
        Route::get('/{organization}/members', [OrganizationController::class, 'members']);
        Route::post('/{organization}/members', [OrganizationController::class, 'addMember']);
        Route::patch('/{organization}/members/{user}', [OrganizationController::class, 'updateMember']);
        Route::delete('/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);

        // Invitations
        Route::get('/{organization}/invitations', [OrganizationController::class, 'invitations']);
        Route::post('/{organization}/invitations', [OrganizationController::class, 'inviteMember']);
        Route::post('/invitations/{token}/accept', [OrganizationController::class, 'acceptInvitation']);
        Route::post('/invitations/{token}/reject', [OrganizationController::class, 'rejectInvitation']);
        Route::delete('/{organization}/invitations/{invitation}', [OrganizationController::class, 'cancelInvitation']);

        // Billing
        Route::get('/{organization}/billing', [OrganizationController::class, 'billing']);
        Route::get('/{organization}/split-costs', [OrganizationController::class, 'splitCosts']);
        Route::post('/{organization}/split-costs/toggle', [OrganizationController::class, 'toggleSplitCosts']);
    });

    // Store / JexPanel Billing
    Route::prefix('/billing')->group(function () {
        Route::post('/nodes/{product:id}', [Client\Billing\NodesController::class, 'index']);
        Route::get('/categories', [Client\Billing\CategoryController::class, 'index']);
        Route::post('/discount-codes', [Client\Billing\DiscountCodeController::class, 'index']);
        Route::get('/categories/{id}', [Client\Billing\ProductController::class, 'index']);
        Route::get('/products/{id}', [Client\Billing\ProductController::class, 'view']);
        Route::get('/products/{id}/variables', [Client\Billing\EggController::class, 'index']);
        Route::get('/orders', [Client\Billing\OrderController::class, 'index']);
        Route::get('/orders/{id}', [Client\Billing\OrderController::class, 'view']);
        Route::post('/stripe/create', [Client\Billing\StripeController::class, 'create']);
        Route::post('/stripe/process', [Client\Billing\StripeController::class, 'process']);
        Route::post('/free/process', [Client\Billing\FreeProductController::class, 'process']);
    });

    /*
    |--------------------------------------------------------------------------
    | Server Control API
    |--------------------------------------------------------------------------
    |
    | Endpoint: /api/client/servers/{server}
    |
    */
    Route::group([
        'prefix' => '/servers/{server}',
        'middleware' => [
            ServerSubject::class,
            AuthenticateServerAccess::class,
            ResourceBelongsToServer::class,
        ],
    ], function () {
        Route::get('/', [Client\Servers\ServerController::class, 'index'])->name('api:client:server.view');
        Route::get('/websocket', Client\Servers\WebsocketController::class)->name('api:client:server.ws');
        Route::get('/resources', Client\Servers\ResourceUtilizationController::class)->name('api:client:server.resources');
        Route::get('/activity', Client\Servers\ActivityLogController::class)->name('api:client:server.activity');

        Route::post('/command', [Client\Servers\CommandController::class, 'index']);
        Route::post('/power', [Client\Servers\PowerController::class, 'index']);
        Route::post('/ai', [Client\Servers\AIController::class, 'index']);

        Route::group(['prefix' => '/databases'], function () {
            Route::get('/', [Client\Servers\DatabaseController::class, 'index']);
            Route::post('/', [Client\Servers\DatabaseController::class, 'store']);
            Route::post('/{database}/rotate-password', [Client\Servers\DatabaseController::class, 'rotatePassword']);
            Route::delete('/{database}', [Client\Servers\DatabaseController::class, 'delete']);
        });

        Route::group(['prefix' => '/files'], function () {
            Route::get('/list', [Client\Servers\FileController::class, 'directory']);
            Route::get('/contents', [Client\Servers\FileController::class, 'contents']);
            Route::get('/download', [Client\Servers\FileController::class, 'download']);
            Route::put('/rename', [Client\Servers\FileController::class, 'rename']);
            Route::post('/copy', [Client\Servers\FileController::class, 'copy']);
            Route::post('/write', [Client\Servers\FileController::class, 'write']);
            Route::post('/compress', [Client\Servers\FileController::class, 'compress']);
            Route::post('/decompress', [Client\Servers\FileController::class, 'decompress']);
            Route::post('/delete', [Client\Servers\FileController::class, 'delete']);
            Route::post('/create-folder', [Client\Servers\FileController::class, 'create']);
            Route::post('/chmod', [Client\Servers\FileController::class, 'chmod']);
            Route::post('/pull', [Client\Servers\FileController::class, 'pull'])->middleware(['throttle:10,5']);
            Route::get('/upload', Client\Servers\FileUploadController::class);
        });

        Route::group(['prefix' => '/schedules'], function () {
            Route::get('/', [Client\Servers\ScheduleController::class, 'index']);
            Route::post('/', [Client\Servers\ScheduleController::class, 'store']);
            Route::get('/{schedule}', [Client\Servers\ScheduleController::class, 'view']);
            Route::post('/{schedule}', [Client\Servers\ScheduleController::class, 'update']);
            Route::post('/{schedule}/execute', [Client\Servers\ScheduleController::class, 'execute']);
            Route::delete('/{schedule}', [Client\Servers\ScheduleController::class, 'delete']);
            Route::post('/{schedule}/tasks', [Client\Servers\ScheduleTaskController::class, 'store']);
            Route::post('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'update']);
            Route::delete('/{schedule}/tasks/{task}', [Client\Servers\ScheduleTaskController::class, 'delete']);
        });

        Route::group(['prefix' => '/network'], function () {
            Route::get('/allocations', [Client\Servers\NetworkAllocationController::class, 'index']);
            Route::post('/allocations', [Client\Servers\NetworkAllocationController::class, 'store']);
            Route::post('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'update']);
            Route::post('/allocations/{allocation}/primary', [Client\Servers\NetworkAllocationController::class, 'setPrimary']);
            Route::delete('/allocations/{allocation}', [Client\Servers\NetworkAllocationController::class, 'delete']);
        });

        Route::group(['prefix' => '/users'], function () {
            Route::get('/', [Client\Servers\SubuserController::class, 'index']);
            Route::post('/', [Client\Servers\SubuserController::class, 'store']);
            Route::get('/{user}', [Client\Servers\SubuserController::class, 'view']);
            Route::post('/{user}', [Client\Servers\SubuserController::class, 'update']);
            Route::delete('/{user}', [Client\Servers\SubuserController::class, 'delete']);
        });

        Route::group(['prefix' => '/backups'], function () {
            Route::get('/', [Client\Servers\BackupController::class, 'index']);
            Route::post('/', [Client\Servers\BackupController::class, 'store']);
            Route::get('/{backup}', [Client\Servers\BackupController::class, 'view']);
            Route::get('/{backup}/download', [Client\Servers\BackupController::class, 'download']);
            Route::post('/{backup}/lock', [Client\Servers\BackupController::class, 'toggleLock']);
            Route::post('/{backup}/restore', [Client\Servers\BackupController::class, 'restore']);
            Route::delete('/{backup}', [Client\Servers\BackupController::class, 'delete']);
        });

        Route::group(['prefix' => '/startup'], function () {
            Route::get('/', [Client\Servers\StartupController::class, 'index']);
            Route::put('/variable', [Client\Servers\StartupController::class, 'update']);
        });

        Route::group(['prefix' => '/settings'], function () {
            Route::post('/rename', [Client\Servers\SettingsController::class, 'rename']);
            Route::post('/reinstall', [Client\Servers\SettingsController::class, 'reinstall']);
            Route::put('/docker-image', [Client\Servers\SettingsController::class, 'dockerImage']);
        });

        Route::prefix('/upgrade')
            ->middleware([BillingEnabled::class, BillingUpgradesEnabled::class])
            ->group(function () {
                Route::get('/', [Client\Billing\UpgradeController::class, 'index']);
                Route::post('/', [Client\Billing\UpgradeController::class, 'create']);
                Route::post('/charge', [Client\Billing\UpgradeController::class, 'charge']);
            });
    });
});
