<?php

declare(strict_types=1);

use App\Modules\Assets\Controllers\AssetController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/assets')->middleware('auth:api,sanctum')->group(function () {
    Route::get('/', [AssetController::class, 'index'])
        ->middleware('permission:assets.view');

    Route::post('/', [AssetController::class, 'store'])
        ->middleware('permission:assets.create');

    Route::get('/{id}', [AssetController::class, 'show'])
        ->middleware('permission:assets.view');

    Route::put('/{id}', [AssetController::class, 'update'])
        ->middleware('permission:assets.manage');

    Route::post('/{id}/depreciation-schedule', [AssetController::class, 'generateDepreciationSchedule'])
        ->middleware('permission:assets.manage');

    Route::delete('/{id}', [AssetController::class, 'destroy'])
        ->middleware('permission:assets.manage');
});
