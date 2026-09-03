<?php

declare(strict_types=1);

use App\Modules\Core\Controllers\AuthController;
use App\Modules\Core\Controllers\RoleController;
use App\Modules\Core\Controllers\SettingsController;
use App\Modules\Core\Controllers\SuperAdminController;
use App\Modules\Core\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth ─────────────────────────────────────────────────────────────────────
Route::prefix('api/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api,sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api,sanctum');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:api,sanctum');

    Route::prefix('mfa')->group(function () {
        Route::post('enable', [AuthController::class, 'enableMfa'])->middleware('auth:api,sanctum');
        Route::post('verify', [AuthController::class, 'verifyMfa'])->middleware('auth:api,sanctum');
        Route::post('challenge', [AuthController::class, 'challengeMfa']);
    });
});

// ── Authenticated Core Endpoints ─────────────────────────────────────────────
Route::prefix('api')->middleware('auth:api,sanctum')->group(function () {

    // Tenant Billing & Plan Activation
    Route::prefix('billing')->group(function () {
        Route::get('plans', [AuthController::class, 'availablePlans']);
        Route::post('select-plan', [AuthController::class, 'selectPlan']);
    });

    // Super Admin & Multi-Tenant Management
    Route::prefix('super-admin')->group(function () {
        Route::get('metrics', [SuperAdminController::class, 'metrics']);
        Route::get('tenants', [SuperAdminController::class, 'index']);
        Route::post('tenants', [SuperAdminController::class, 'store']);
        Route::get('tenants/{id}', [SuperAdminController::class, 'show']);
        Route::put('tenants/{id}', [SuperAdminController::class, 'update']);
        Route::patch('tenants/{id}/status', [SuperAdminController::class, 'updateStatus']);
        Route::post('tenants/{id}/impersonate', [SuperAdminController::class, 'impersonate']);
        Route::delete('tenants/{id}', [SuperAdminController::class, 'destroy']);
        Route::get('plans', [SuperAdminController::class, 'plans']);
    });

    // Users CRUD
    Route::get('users', [UserController::class, 'index'])
        ->middleware('permission:core.users.view');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('permission:core.users.create');
    Route::get('users/{id}', [UserController::class, 'show'])
        ->middleware('permission:core.users.view');
    Route::put('users/{id}', [UserController::class, 'update'])
        ->middleware('permission:core.users.edit');
    Route::patch('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])
        ->middleware('permission:core.users.edit');
    Route::delete('users/{id}', [UserController::class, 'destroy'])
        ->middleware('permission:core.users.delete');

    // Role CRUD
    Route::get('permissions', [RoleController::class, 'permissions'])
        ->middleware('permission:core.roles.view');
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

    // Tenant settings
    Route::get('core/settings', [SettingsController::class, 'show'])
        ->middleware('permission:core.settings.view');
    Route::post('core/settings', [SettingsController::class, 'update'])
        ->middleware('permission:core.settings.edit');

    // Module Management & Dynamic Dependency Toggling
    Route::get('core/modules', [\App\Modules\Core\Controllers\ModuleController::class, 'index'])
        ->middleware('permission:core.settings.view');
    Route::post('core/modules/toggle', [\App\Modules\Core\Controllers\ModuleController::class, 'toggle'])
        ->middleware('permission:core.settings.edit');

    // Consolidated live dashboard stats
    Route::get('core/dashboard', [\App\Modules\Core\Controllers\DashboardController::class, 'stats']);
});
