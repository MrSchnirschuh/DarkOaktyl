<?php

use Illuminate\Support\Facades\Route;
use DarkOak\Http\Controllers\Api\Application\Legal\LegalDocumentsController;

/*
|--------------------------------------------------------------------------
| Public Legal Document Routes
|--------------------------------------------------------------------------
|
| Endpoint: /api/legal
| These are public-facing routes for the legal pages (terms, imprint).
| No auth required — only published documents are returned.
|
*/

Route::get('/published', [LegalDocumentsController::class, 'published']);
Route::get('/published/{slug}', function (string $slug) {
    $controller = app(LegalDocumentsController::class);

    return $controller->showPublished($slug);
});
