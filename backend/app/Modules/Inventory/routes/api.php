<?php

declare(strict_types=1);

use App\Modules\Inventory\Controllers\BarcodeController;
use App\Modules\Inventory\Controllers\ProductCategoryController;
use App\Modules\Inventory\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/inventory')->middleware('auth:api,sanctum')->group(function () {

    // ── Product Categories ────────────────────────────────────────────────────
    Route::get('categories/tree', [ProductCategoryController::class, 'tree'])
        ->middleware('permission:inventory.products.view');

    Route::get('categories', [ProductCategoryController::class, 'index'])
        ->middleware('permission:inventory.products.view');

    Route::post('categories', [ProductCategoryController::class, 'store'])
        ->middleware('permission:inventory.products.create');

    Route::get('categories/{category}', [ProductCategoryController::class, 'show'])
        ->middleware('permission:inventory.products.view');

    Route::put('categories/{category}', [ProductCategoryController::class, 'update'])
        ->middleware('permission:inventory.products.edit');

    Route::delete('categories/{category}', [ProductCategoryController::class, 'destroy'])
        ->middleware('permission:inventory.products.delete');

    // ── Products ──────────────────────────────────────────────────────────────
    Route::post('products/import', [ProductController::class, 'import'])
        ->middleware('permission:inventory.products.create');

    Route::get('products', [ProductController::class, 'index'])
        ->middleware('permission:inventory.products.view');

    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:inventory.products.create');

    Route::get('products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:inventory.products.view');

    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:inventory.products.edit');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:inventory.products.delete');

    Route::get('products/{product}/variants', [ProductController::class, 'variants'])
        ->middleware('permission:inventory.products.view');

    Route::get('products/{product}/stock', [ProductController::class, 'stock'])
        ->middleware('permission:inventory.stock.view');

    // ── Barcode Lookup (POS scanner endpoint) ─────────────────────────────────
    Route::get('barcodes/lookup', [BarcodeController::class, 'lookup'])
        ->middleware('permission:inventory.products.view');
});
