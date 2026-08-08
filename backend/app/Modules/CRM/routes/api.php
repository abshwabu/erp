<?php

declare(strict_types=1);

use App\Modules\CRM\Controllers\ContactController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/crm')->middleware('auth:api,sanctum')->group(function () {
    Route::get('contacts', [ContactController::class, 'index'])
        ->middleware('permission:crm.contacts.view');

    Route::post('contacts', [ContactController::class, 'store'])
        ->middleware('permission:crm.contacts.manage');
});
