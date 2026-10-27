<?php

use Illuminate\Support\Facades\Route;

// Explicit 404 for missing static asset files under /assets/
// (Prevents falling back to index.html with Content-Type text/html for missing chunks)
Route::get('/assets/{path}', function () {
    return response('Asset not found', 404, [
        'Content-Type' => 'text/plain',
    ]);
})->where('path', '.*');

// Fallback route to serve public storage files if not intercepted by web server
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = ltrim($path, '/');
    if (str_contains($cleanPath, '..')) {
        abort(404);
    }

    $filePath = storage_path('app/public/' . $cleanPath);
    if (!file_exists($filePath) || is_dir($filePath)) {
        abort(404, 'File not found');
    }

    $mime = mime_content_type($filePath) ?: 'application/octet-stream';

    return response()->file($filePath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
        'Access-Control-Allow-Origin' => '*',
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

