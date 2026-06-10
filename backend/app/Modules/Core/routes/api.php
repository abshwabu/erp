<?php

declare(strict_types=1);

use App\Modules\Core\Controllers\AuthController;
use App\Modules\Core\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

// ── Auth ─────────────────────────────────────────────────────────────────────
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

// ── Roles & Permissions (all require authentication) ─────────────────────────
Route::prefix('api')->middleware('auth:api,sanctum')->group(function () {

    // Role CRUD
    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('permission:core.roles.view');

    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('permission:core.roles.create');

    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('permission:core.roles.view');

    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('permission:core.roles.edit');

    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:core.roles.delete');

    // Permission sync on a role
    Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->middleware('permission:core.roles.edit');

    // User ↔ Role assignment
    Route::post('users/{user}/roles', [RoleController::class, 'assignRole'])
        ->middleware('permission:core.roles.edit');

    Route::delete('users/{user}/roles/{role}', [RoleController::class, 'revokeRole'])
        ->middleware('permission:core.roles.edit');
});
