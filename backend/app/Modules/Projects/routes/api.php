<?php

declare(strict_types=1);

use App\Modules\Projects\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/projects')->middleware('auth:api,sanctum')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])
        ->middleware('permission:projects.view');

    Route::post('/', [ProjectController::class, 'store'])
        ->middleware('permission:projects.create');

    Route::get('/{id}', [ProjectController::class, 'show'])
        ->middleware('permission:projects.view');

    Route::put('/{id}', [ProjectController::class, 'update'])
        ->middleware('permission:projects.manage');

    Route::post('/{id}/tasks', [ProjectController::class, 'addTask'])
        ->middleware('permission:projects.manage');

    Route::put('/{id}/tasks/{taskId}', [ProjectController::class, 'updateTask'])
        ->middleware('permission:projects.manage');
});
