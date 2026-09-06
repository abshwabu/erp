<?php

use Illuminate\Support\Facades\Route;

// Explicit 404 for missing static asset files under /assets/
// (Prevents falling back to index.html with Content-Type text/html for missing chunks)
Route::get('/assets/{path}', function () {
    return response('Asset not found', 404, [
        'Content-Type' => 'text/plain',
    ]);
})->where('path', '.*');

// Fallback to compiled Vue SPA for all client-side routes
Route::get('/{any?}', function () {
    $indexPath = public_path('index.html');
    if (file_exists($indexPath)) {
        return response()->file($indexPath, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    return view('welcome');
})->where('any', '^(?!api|storage|sanctum|up).*$');

