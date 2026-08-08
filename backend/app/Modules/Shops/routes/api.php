<?php

declare(strict_types=1);

use App\Modules\Shops\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/shops')->middleware('auth:api,sanctum')->group(function () {
    Route::get('mine', [ShopController::class, 'mine'])
        ->middleware('permission:pos.sessions.open|shops.view');

    Route::get('assignable-users', [ShopController::class, 'assignableUsers'])
        ->middleware('permission:shops.manage');

    Route::get('/', [ShopController::class, 'index'])
        ->middleware('permission:shops.view');

    Route::post('/', [ShopController::class, 'store'])
        ->middleware('permission:shops.manage');

    Route::get('{shop}', [ShopController::class, 'show'])
        ->middleware('permission:shops.view');

    Route::put('{shop}', [ShopController::class, 'update'])
        ->middleware('permission:shops.view');

    Route::get('{shop}/keepers', [ShopController::class, 'keepers'])
        ->middleware('permission:shops.manage');

    Route::put('{shop}/keepers', [ShopController::class, 'syncKeepers'])
        ->middleware('permission:shops.manage');

    Route::get('{shop}/stock', [ShopController::class, 'stock'])
        ->middleware('permission:shops.view|inventory.stock.view|pos.sessions.open');

    Route::post('{shop}/stock/adjust', [ShopController::class, 'adjustStock'])
        ->middleware('permission:shops.view|inventory.stock.adjust');
});
