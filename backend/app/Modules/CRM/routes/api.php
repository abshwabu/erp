<?php

declare(strict_types=1);

use App\Modules\CRM\Controllers\ActivityController;
use App\Modules\CRM\Controllers\ContactController;
use App\Modules\CRM\Controllers\CrmDashboardController;
use App\Modules\CRM\Controllers\DealController;
use App\Modules\CRM\Controllers\LeadController;
use App\Modules\CRM\Controllers\LeadFormController;
use Illuminate\Support\Facades\Route;

// ── Public Lead Intake & Form Endpoints (No Auth Required) ────────────────────
Route::prefix('api/public/leads')->group(function () {
    Route::get('forms/{idOrSlug}', [LeadFormController::class, 'publicShow']);
    Route::post('forms/{idOrSlug}/submit', [LeadFormController::class, 'publicSubmit']);
});

// ── Authenticated CRM Routes ──────────────────────────────────────────────────
Route::prefix('api/crm')->middleware('auth:api,sanctum')->group(function () {
    // Analytics & Overview
    Route::get('dashboard/stats', [CrmDashboardController::class, 'stats'])
        ->middleware('permission:crm.contacts.view');

    // Lead Forms & Multi-Channel Sources
    Route::get('lead-forms', [LeadFormController::class, 'index'])
        ->middleware('permission:crm.contacts.view');
    Route::post('lead-forms', [LeadFormController::class, 'store'])
        ->middleware('permission:crm.contacts.manage');
    Route::get('lead-forms/{id}', [LeadFormController::class, 'show'])
        ->middleware('permission:crm.contacts.view');
    Route::patch('lead-forms/{id}', [LeadFormController::class, 'update'])
        ->middleware('permission:crm.contacts.manage');
    Route::delete('lead-forms/{id}', [LeadFormController::class, 'destroy'])
        ->middleware('permission:crm.contacts.manage');

    // Leads Management
    Route::get('leads', [LeadController::class, 'index'])
        ->middleware('permission:crm.contacts.view');
    Route::post('leads', [LeadController::class, 'store'])
        ->middleware('permission:crm.contacts.manage');
    Route::get('leads/{id}', [LeadController::class, 'show'])
        ->middleware('permission:crm.contacts.view');
    Route::patch('leads/{id}', [LeadController::class, 'update'])
        ->middleware('permission:crm.contacts.manage');
    Route::delete('leads/{id}', [LeadController::class, 'destroy'])
        ->middleware('permission:crm.contacts.manage');
    Route::post('leads/{id}/convert', [LeadController::class, 'convert'])
        ->middleware('permission:crm.contacts.manage');

    // Deals & Pipeline
    Route::get('deals', [DealController::class, 'index'])
        ->middleware('permission:crm.contacts.view');
    Route::post('deals', [DealController::class, 'store'])
        ->middleware('permission:crm.contacts.manage');
    Route::get('deals/{id}', [DealController::class, 'show'])
        ->middleware('permission:crm.contacts.view');
    Route::patch('deals/{id}', [DealController::class, 'update'])
        ->middleware('permission:crm.contacts.manage');
    Route::patch('deals/{id}/stage', [DealController::class, 'updateStage'])
        ->middleware('permission:crm.contacts.manage');
    Route::delete('deals/{id}', [DealController::class, 'destroy'])
        ->middleware('permission:crm.contacts.manage');

    // Contacts Directory
    Route::get('contacts', [ContactController::class, 'index'])
        ->middleware('permission:crm.contacts.view');
    Route::post('contacts', [ContactController::class, 'store'])
        ->middleware('permission:crm.contacts.manage');
    Route::get('contacts/{id}', [ContactController::class, 'show'])
        ->middleware('permission:crm.contacts.view');
    Route::patch('contacts/{id}', [ContactController::class, 'update'])
        ->middleware('permission:crm.contacts.manage');
    Route::delete('contacts/{id}', [ContactController::class, 'destroy'])
        ->middleware('permission:crm.contacts.manage');

    // Activities & Interactions
    Route::get('activities', [ActivityController::class, 'index'])
        ->middleware('permission:crm.contacts.view');
    Route::post('activities', [ActivityController::class, 'store'])
        ->middleware('permission:crm.contacts.manage');
    Route::patch('activities/{id}', [ActivityController::class, 'update'])
        ->middleware('permission:crm.contacts.manage');
    Route::patch('activities/{id}/toggle-complete', [ActivityController::class, 'toggleComplete'])
        ->middleware('permission:crm.contacts.manage');
    Route::delete('activities/{id}', [ActivityController::class, 'destroy'])
        ->middleware('permission:crm.contacts.manage');
});
