<?php

declare(strict_types=1);

use App\Modules\Documents\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/documents')->middleware('auth:api,sanctum')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])
        ->middleware('permission:documents.view');

    Route::post('/', [DocumentController::class, 'store'])
        ->middleware('permission:documents.upload');

    Route::get('/{id}', [DocumentController::class, 'show'])
        ->middleware('permission:documents.view');

    Route::get('/{id}/download', [DocumentController::class, 'download'])
        ->middleware('permission:documents.view');

    Route::delete('/{id}', [DocumentController::class, 'destroy'])
        ->middleware('permission:documents.manage');
});
