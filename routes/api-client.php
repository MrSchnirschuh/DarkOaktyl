<?php

use Illuminate\Support\Facades\Route;
use DarkOak\Http\Controllers\Api\Client\Notifications\PushSubscriptionController;
use DarkOak\Http\Controllers\Api\Client\AutoScalingController;
use DarkOak\Http\Controllers\Api\Client\BillingController as ClientBillingController;
use DarkOak\Http\Controllers\Api\Client\ServerTemplateController;
use DarkOak\Http\Controllers\Api\Client\RegionController;
use DarkOak\Http\Controllers\Api\Client\Organizations\OrganizationController;

/*
|--------------------------------------------------------------------------
| Client API Routes
|--------------------------------------------------------------------------
|
| These routes are for the authenticated client API.
|
*/

// Push Notifications
Route::prefix('notifications')->group(function () {
    Route::get('/', [PushSubscriptionController::class, 'index']);
    Route::post('/', [PushSubscriptionController::class, 'store']);
    Route::get('/events', [PushSubscriptionController::class, 'events']);
    Route::get('/vapid-key', [PushSubscriptionController::class, 'vapidPublicKey']);
    Route::post('/test', [PushSubscriptionController::class, 'sendTest']);
    Route::patch('/{uuid}', [PushSubscriptionController::class, 'update']);
    Route::delete('/{uuid}', [PushSubscriptionController::class, 'destroy']);
    Route::delete('/', [PushSubscriptionController::class, 'destroyAll']);
});

// Auto-Scaling
Route::prefix('servers/{server}/autoscale')->group(function () {
    Route::get('/', [AutoScalingController::class, 'show']);
    Route::post('/', [AutoScalingController::class, 'update']);
    Route::delete('/', [AutoScalingController::class, 'destroy']);
    Route::get('/history', [AutoScalingController::class, 'history']);
    Route::post('/evaluate', [AutoScalingController::class, 'evaluate']);
});

// Usage-Based Billing
Route::prefix('billing')->group(function () {
    Route::get('/balance', [ClientBillingController::class, 'balance']);
    Route::get('/history', [ClientBillingController::class, 'history']);
    Route::get('/summary', [ClientBillingController::class, 'summary']);
    Route::get('/servers/{server}/estimate', [ClientBillingController::class, 'estimate']);
    Route::get('/transactions', [ClientBillingController::class, 'transactions']);
    Route::post('/credits', [ClientBillingController::class, 'addCredits']);
    Route::patch('/threshold', [ClientBillingController::class, 'updateThreshold']);
});

// Server Templates (One-Click Apps)
Route::prefix('templates')->group(function () {
    Route::get('/', [ServerTemplateController::class, 'index']);
    Route::get('/featured', [ServerTemplateController::class, 'featured']);
    Route::get('/types', [ServerTemplateController::class, 'types']);
    Route::get('/search', [ServerTemplateController::class, 'search']);
    Route::get('/type/{type}', [ServerTemplateController::class, 'byType']);
    Route::get('/{uuid}', [ServerTemplateController::class, 'show']);
    Route::post('/{uuid}/deploy', [ServerTemplateController::class, 'deploy']);
});

// Multi-Region
Route::prefix('regions')->group(function () {
    Route::get('/', [RegionController::class, 'index']);
    Route::get('/default', [RegionController::class, 'default']);
    Route::get('/recommend', [RegionController::class, 'recommend']);
    Route::get('/{code}', [RegionController::class, 'show']);
    Route::get('/{code}/nodes', [RegionController::class, 'nodes']);
    Route::get('/{code}/stats', [RegionController::class, 'stats']);
});

// Organizations (Teams)
Route::prefix('organizations')->group(function () {
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