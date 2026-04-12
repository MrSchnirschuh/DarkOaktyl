<?php

use Illuminate\Support\Facades\Route;
use DarkOak\Http\Controllers\Api\Remote;

/*
|--------------------------------------------------------------------------
| Daemon API Routes (Wings)
|--------------------------------------------------------------------------
|
| These routes handle communication from the Wings daemon back to the
| Panel. They are protected by Daemon authentication middleware and
| rate limiting to prevent DoS attacks while allowing legitimate
| high-frequency operations.
|
| Rate Limits:
| - General Daemon API: 120 requests/minute per node (configurable)
| - SFTP Auth: 60 requests/minute per node (configurable)
| - Commands: 30 requests/minute per node (configurable)
|
*/

// SFTP Authentication endpoint - uses stricter rate limiting
Route::post('/sftp/auth', Remote\SftpAuthenticationController::class)
    ->middleware(['throttle:api.daemon.sftp']);

// Server listing and activity endpoints - general daemon rate limit
Route::get('/servers', [Remote\Servers\ServerDetailsController::class, 'list']);
Route::post('/servers/reset', [Remote\Servers\ServerDetailsController::class, 'resetState'])
    ->middleware(['throttle:api.daemon.command']);
Route::post('/activity', Remote\ActivityProcessingController::class);

// Server-specific endpoints with varying rate limits
Route::group(['prefix' => '/servers/{uuid}'], function () {
    // Read-only endpoints - standard rate limit
    Route::get('/', Remote\Servers\ServerDetailsController::class);
    Route::get('/install', [Remote\Servers\ServerInstallController::class, 'index']);

    // Write endpoints - stricter rate limiting for commands
    Route::post('/install', [Remote\Servers\ServerInstallController::class, 'store'])
        ->middleware(['throttle:api.daemon.command']);

    // Transfer endpoints - command rate limit (high impact)
    Route::get('/transfer/failure', [Remote\Servers\ServerTransferController::class, 'failure'])
        ->middleware(['throttle:api.daemon.command']);
    Route::get('/transfer/success', [Remote\Servers\ServerTransferController::class, 'success'])
        ->middleware(['throttle:api.daemon.command']);
    Route::post('/transfer/failure', [Remote\Servers\ServerTransferController::class, 'failure'])
        ->middleware(['throttle:api.daemon.command']);
    Route::post('/transfer/success', [Remote\Servers\ServerTransferController::class, 'success'])
        ->middleware(['throttle:api.daemon.command']);
});

// Backup endpoints - standard daemon rate limit
Route::group(['prefix' => '/backups'], function () {
    Route::get('/{backup}', Remote\Backups\BackupRemoteUploadController::class);
    Route::post('/{backup}', [Remote\Backups\BackupStatusController::class, 'index']);
    Route::post('/{backup}/restore', [Remote\Backups\BackupStatusController::class, 'restore'])
        ->middleware(['throttle:api.daemon.command']);
});
