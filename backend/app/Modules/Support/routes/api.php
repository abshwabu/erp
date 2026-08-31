<?php

declare(strict_types=1);

use App\Modules\Support\Controllers\KnowledgeArticleController;
use App\Modules\Support\Controllers\SupportDashboardController;
use App\Modules\Support\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/support')->middleware('auth:api,sanctum')->group(function () {
    // Dashboard Stats
    Route::get('dashboard/stats', [SupportDashboardController::class, 'stats'])
        ->middleware('permission:support.tickets.view');

    // Tickets
    Route::get('tickets', [TicketController::class, 'index'])
        ->middleware('permission:support.tickets.view');
    Route::post('tickets', [TicketController::class, 'store'])
        ->middleware('permission:support.tickets.create');
    Route::get('tickets/{id}', [TicketController::class, 'show'])
        ->middleware('permission:support.tickets.view');
    Route::put('tickets/{id}', [TicketController::class, 'update'])
        ->middleware('permission:support.tickets.manage');
    Route::post('tickets/{id}/reply', [TicketController::class, 'reply'])
        ->middleware('permission:support.tickets.manage');
    Route::delete('tickets/{id}', [TicketController::class, 'destroy'])
        ->middleware('permission:support.tickets.manage');

    // Knowledge Base Articles
    Route::get('articles', [KnowledgeArticleController::class, 'index'])
        ->middleware('permission:support.tickets.view');
    Route::post('articles', [KnowledgeArticleController::class, 'store'])
        ->middleware('permission:support.tickets.manage');
    Route::get('articles/{idOrSlug}', [KnowledgeArticleController::class, 'show'])
        ->middleware('permission:support.tickets.view');
    Route::put('articles/{id}', [KnowledgeArticleController::class, 'update'])
        ->middleware('permission:support.tickets.manage');
    Route::delete('articles/{id}', [KnowledgeArticleController::class, 'destroy'])
        ->middleware('permission:support.tickets.manage');
});
