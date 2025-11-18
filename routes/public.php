<?php

use DarkOak\Http\Controllers\Base;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Website Routes
|--------------------------------------------------------------------------
|
| Routes for the public-facing website at the root domain (darkoak.eu)
| These routes do not require authentication.
|
*/

Route::get('/', [Base\PublicWebsiteController::class, 'index'])->name('public.home');
Route::get('/documentation', [Base\PublicWebsiteController::class, 'documentation'])->name('public.documentation');
