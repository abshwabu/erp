<?php

declare(strict_types=1);

use App\Modules\Procurement\Controllers\PurchaseOrderController;
use App\Modules\Procurement\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/procurement')->middleware('auth:api,sanctum')->group(function () {
    Route::get('suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:procurement.suppliers.manage');

    Route::post('suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:procurement.suppliers.manage');

    Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
        ->middleware('permission:procurement.purchase_orders.view');

    Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
        ->middleware('permission:procurement.purchase_orders.create');

    Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive'])
        ->middleware('permission:procurement.purchase_orders.create');
});
