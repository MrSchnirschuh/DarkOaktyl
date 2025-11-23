<?php

use DarkOak\Http\Controllers\Base;
use Illuminate\Support\Facades\Route;
use DarkOak\Http\Middleware\RequireTwoFactorAuthentication;

Route::get('/', [Base\IndexController::class, 'index'])->name('index')->fallback();
Route::get('/account', [Base\IndexController::class, 'index'])
    ->withoutMiddleware(RequireTwoFactorAuthentication::class)
    ->name('account');

Route::get('/legal/terms-of-service', [Base\LegalPageController::class, 'termsOfService'])
    ->name('legal.agb')
    ->withoutMiddleware(['auth', 'auth.session', RequireTwoFactorAuthentication::class]);

Route::get('/legal/legal-notice', [Base\LegalPageController::class, 'legalNotice'])
    ->name('legal.impressum')
    ->withoutMiddleware(['auth', 'auth.session', RequireTwoFactorAuthentication::class]);

Route::redirect('/legal/agb', '/legal/terms-of-service')
    ->withoutMiddleware(['auth', 'auth.session', RequireTwoFactorAuthentication::class]);
Route::redirect('/legal/impressum', '/legal/legal-notice')
    ->withoutMiddleware(['auth', 'auth.session', RequireTwoFactorAuthentication::class]);

Route::get('/{react}', [Base\IndexController::class, 'index'])
    ->where('react', '^(?!(\/)?(api|auth|admin|daemon)).+');

