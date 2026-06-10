<?php

declare(strict_types=1);

use App\Modules\Core\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api,sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api,sanctum');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::prefix('mfa')->group(function () {
        Route::post('enable', [AuthController::class, 'enableMfa'])->middleware('auth:api,sanctum');
        Route::post('verify', [AuthController::class, 'verifyMfa'])->middleware('auth:api,sanctum');
        Route::post('challenge', [AuthController::class, 'challengeMfa']);
    });
});
