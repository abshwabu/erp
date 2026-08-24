<?php

declare(strict_types=1);

use App\Modules\Ecommerce\Controllers\ChannelController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/ecommerce')->middleware('auth:api,sanctum')->group(function () {
    Route::get('channels', [ChannelController::class, 'index'])
        ->middleware('permission:ecommerce.channels.view');

    Route::post('channels', [ChannelController::class, 'store'])
        ->middleware('permission:ecommerce.channels.manage');

    Route::get('channels/{id}', [ChannelController::class, 'show'])
        ->middleware('permission:ecommerce.channels.view');

    Route::put('channels/{id}', [ChannelController::class, 'update'])
        ->middleware('permission:ecommerce.channels.manage');

    Route::post('channels/{id}/sync', [ChannelController::class, 'sync'])
        ->middleware('permission:ecommerce.channels.manage');

    Route::post('channels/{id}/orders', [ChannelController::class, 'syncOrder'])
        ->middleware('permission:ecommerce.channels.manage');

    Route::delete('channels/{id}', [ChannelController::class, 'destroy'])
        ->middleware('permission:ecommerce.channels.manage');

    Route::get('orders', [ChannelController::class, 'orders'])
        ->middleware('permission:ecommerce.orders.view');
});
