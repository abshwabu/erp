<?php

declare(strict_types=1);

use App\Modules\Support\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/support')->middleware('auth:api,sanctum')->group(function () {
    Route::get('tickets', [TicketController::class, 'index'])
        ->middleware('permission:support.tickets.view');

    Route::post('tickets', [TicketController::class, 'store'])
        ->middleware('permission:support.tickets.create');

    Route::get('tickets/{id}', [TicketController::class, 'show'])
        ->middleware('permission:support.tickets.view');

    Route::put('tickets/{id}', [TicketController::class, 'update'])
        ->middleware('permission:support.tickets.manage');

    Route::post('tickets/{id}/reply', [TicketController::class, 'reply'])
        ->middleware('permission:support.tickets.create');

    Route::delete('tickets/{id}', [TicketController::class, 'destroy'])
        ->middleware('permission:support.tickets.manage');
});
