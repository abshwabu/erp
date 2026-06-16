<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/../app/Modules/HR/routes/api.php';
require __DIR__.'/../app/Modules/Accounting/routes/api.php';

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
]));

Route::get('/tenant-context', function () {
    $tenant = tenant();

    return response()->json([
        'tenant_id' => $tenant?->getTenantKey(),
        'tenant_slug' => $tenant?->slug,
        'timezone' => config('app.timezone'),
        'locale' => app()->getLocale(),
        'currency' => config('app.currency'),
    ]);
});
