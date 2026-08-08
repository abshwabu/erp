<?php

declare(strict_types=1);

use App\Modules\POS\Controllers\POSSessionController;
use App\Modules\POS\Controllers\POSTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/pos')->middleware('auth:api,sanctum')->group(function () {
    Route::get('terminals', [POSSessionController::class, 'terminals'])
        ->middleware('permission:pos.sessions.open');

    Route::get('sessions/current', [POSSessionController::class, 'current'])
        ->middleware('permission:pos.sessions.open');

    Route::post('sessions/open', [POSSessionController::class, 'open'])
        ->middleware('permission:pos.sessions.open');

    Route::post('sessions/{session}/close', [POSSessionController::class, 'close'])
        ->middleware('permission:pos.sessions.close');

    Route::post('checkout', [POSTransactionController::class, 'checkout'])
        ->middleware('permission:pos.sessions.open');

    Route::get('receipts/{receiptNumber}', [POSTransactionController::class, 'receipt'])
        ->middleware('permission:pos.sessions.open');
});
