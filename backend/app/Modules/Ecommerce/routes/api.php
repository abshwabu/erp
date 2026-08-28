<?php

declare(strict_types=1);

use App\Modules\Ecommerce\Controllers\ChannelController;
use App\Modules\Ecommerce\Controllers\PublicStorefrontController;
use App\Modules\Ecommerce\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

// Authenticated Merchant / Tenant Builder Routes
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

    Route::patch('orders/{id}/fulfill', [ChannelController::class, 'fulfillOrder'])
        ->middleware('permission:ecommerce.orders.fulfill');

    // Storefront Builder & CMS
    Route::get('storefronts', [StorefrontController::class, 'index'])
        ->middleware('permission:ecommerce.storefront.view');

    Route::post('storefronts', [StorefrontController::class, 'store'])
        ->middleware('permission:ecommerce.storefront.manage');

    Route::get('storefronts/{id}', [StorefrontController::class, 'show'])
        ->middleware('permission:ecommerce.storefront.view');

    Route::put('storefronts/{id}', [StorefrontController::class, 'update'])
        ->middleware('permission:ecommerce.storefront.manage');

    Route::put('storefronts/{id}/pages/{pageId}', [StorefrontController::class, 'updatePageSections'])
        ->middleware('permission:ecommerce.storefront.manage');

    Route::delete('storefronts/{id}', [StorefrontController::class, 'destroy'])
        ->middleware('permission:ecommerce.storefront.manage');
});

// Public Customer-Facing Storefront Routes (No auth required)
Route::prefix('api/store')->group(function () {
    Route::get('{slug}', [PublicStorefrontController::class, 'getStore']);
    Route::post('{slug}/checkout', [PublicStorefrontController::class, 'checkout']);
});
