<?php

declare(strict_types=1);

use App\Modules\Integrations\Controllers\IntegrationController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/integrations')->middleware('auth:api,sanctum')->group(function () {
    Route::get('/', [IntegrationController::class, 'index'])
        ->middleware('permission:integrations.view');

    Route::post('/', [IntegrationController::class, 'store'])
        ->middleware('permission:integrations.manage');

    Route::get('/{id}', [IntegrationController::class, 'show'])
        ->middleware('permission:integrations.view');

    Route::put('/{id}', [IntegrationController::class, 'update'])
        ->middleware('permission:integrations.manage');

    Route::post('/{id}/test', [IntegrationController::class, 'testConnection'])
        ->middleware('permission:integrations.manage');

    Route::get('/{id}/logs', [IntegrationController::class, 'logs'])
        ->middleware('permission:integrations.view');

    Route::delete('/{id}', [IntegrationController::class, 'destroy'])
        ->middleware('permission:integrations.manage');
});
