<?php

declare(strict_types=1);

use App\Modules\Manufacturing\Controllers\BomController;
use App\Modules\Manufacturing\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/manufacturing')->middleware('auth:api,sanctum')->group(function () {
    // Bill of Materials
    Route::get('boms', [BomController::class, 'index'])
        ->middleware('permission:manufacturing.bom.manage');

    Route::post('boms', [BomController::class, 'store'])
        ->middleware('permission:manufacturing.bom.manage');

    Route::get('boms/{id}', [BomController::class, 'show'])
        ->middleware('permission:manufacturing.bom.manage');

    Route::post('boms/{id}/activate', [BomController::class, 'activate'])
        ->middleware('permission:manufacturing.bom.manage');

    Route::post('boms/{id}/archive', [BomController::class, 'archive'])
        ->middleware('permission:manufacturing.bom.manage');

    Route::delete('boms/{id}', [BomController::class, 'destroy'])
        ->middleware('permission:manufacturing.bom.manage');

    // Work Orders
    Route::get('work-orders', [WorkOrderController::class, 'index'])
        ->middleware('permission:manufacturing.work_orders.view');

    Route::post('work-orders', [WorkOrderController::class, 'store'])
        ->middleware('permission:manufacturing.work_orders.create');

    Route::get('work-orders/{id}', [WorkOrderController::class, 'show'])
        ->middleware('permission:manufacturing.work_orders.view');

    Route::post('work-orders/{id}/start', [WorkOrderController::class, 'start'])
        ->middleware('permission:manufacturing.work_orders.create');

    Route::post('work-orders/{id}/complete', [WorkOrderController::class, 'complete'])
        ->middleware('permission:manufacturing.work_orders.create');

    Route::post('work-orders/{id}/cancel', [WorkOrderController::class, 'cancel'])
        ->middleware('permission:manufacturing.work_orders.create');

    Route::delete('work-orders/{id}', [WorkOrderController::class, 'destroy'])
        ->middleware('permission:manufacturing.work_orders.create');
});
