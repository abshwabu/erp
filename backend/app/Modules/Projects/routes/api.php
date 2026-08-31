<?php

declare(strict_types=1);

use App\Modules\Projects\Controllers\ProjectController;
use App\Modules\Projects\Controllers\ProjectMilestoneController;
use App\Modules\Projects\Controllers\ProjectTaskController;
use App\Modules\Projects\Controllers\ProjectTimeLogController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/projects')->middleware('auth:api,sanctum')->group(function () {
    // Dashboard Stats
    Route::get('dashboard/stats', [ProjectController::class, 'dashboardStats'])
        ->middleware('permission:projects.view');

    // Projects CRUD
    Route::get('/', [ProjectController::class, 'index'])
        ->middleware('permission:projects.view');
    Route::post('/', [ProjectController::class, 'store'])
        ->middleware('permission:projects.create');
    Route::get('/{id}', [ProjectController::class, 'show'])
        ->middleware('permission:projects.view');
    Route::put('/{id}', [ProjectController::class, 'update'])
        ->middleware('permission:projects.manage');
    Route::delete('/{id}', [ProjectController::class, 'destroy'])
        ->middleware('permission:projects.manage');

    // Tasks & Kanban
    Route::get('tasks/all', [ProjectTaskController::class, 'index'])
        ->middleware('permission:projects.view');
    Route::post('tasks', [ProjectTaskController::class, 'store'])
        ->middleware('permission:projects.manage');
    Route::get('tasks/{id}', [ProjectTaskController::class, 'show'])
        ->middleware('permission:projects.view');
    Route::put('tasks/{id}', [ProjectTaskController::class, 'update'])
        ->middleware('permission:projects.manage');
    Route::patch('tasks/{id}/status', [ProjectTaskController::class, 'updateStatus'])
        ->middleware('permission:projects.manage');
    Route::delete('tasks/{id}', [ProjectTaskController::class, 'destroy'])
        ->middleware('permission:projects.manage');

    // Milestones
    Route::get('milestones/all', [ProjectMilestoneController::class, 'index'])
        ->middleware('permission:projects.view');
    Route::post('milestones', [ProjectMilestoneController::class, 'store'])
        ->middleware('permission:projects.manage');
    Route::put('milestones/{id}', [ProjectMilestoneController::class, 'update'])
        ->middleware('permission:projects.manage');
    Route::delete('milestones/{id}', [ProjectMilestoneController::class, 'destroy'])
        ->middleware('permission:projects.manage');

    // Time Logs / Timesheets
    Route::get('time-logs/all', [ProjectTimeLogController::class, 'index'])
        ->middleware('permission:projects.view');
    Route::post('time-logs', [ProjectTimeLogController::class, 'store'])
        ->middleware('permission:projects.manage');
    Route::put('time-logs/{id}', [ProjectTimeLogController::class, 'update'])
        ->middleware('permission:projects.manage');
    Route::delete('time-logs/{id}', [ProjectTimeLogController::class, 'destroy'])
        ->middleware('permission:projects.manage');
});
