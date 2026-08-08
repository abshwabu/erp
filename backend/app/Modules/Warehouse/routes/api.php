<?php

declare(strict_types=1);

use App\Modules\Warehouse\Controllers\WarehouseLocationController;
use App\Modules\Warehouse\Controllers\WarehouseStockController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/warehouse')->middleware('auth:api,sanctum')->group(function () {
    Route::get('locations', [WarehouseLocationController::class, 'index'])
        ->middleware('permission:warehouse.receive');

    Route::post('locations', [WarehouseLocationController::class, 'store'])
        ->middleware('permission:warehouse.receive');

    Route::post('receive', [WarehouseStockController::class, 'receive'])
        ->middleware('permission:warehouse.receive');

    Route::post('transfer', [WarehouseStockController::class, 'transfer'])
        ->middleware('permission:warehouse.pick');
});
