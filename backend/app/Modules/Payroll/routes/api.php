<?php

declare(strict_types=1);

use App\Modules\Payroll\Controllers\PayrollRunController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/payroll')->middleware('auth:api,sanctum')->group(function () {
    Route::get('runs', [PayrollRunController::class, 'index'])
        ->middleware('permission:payroll.runs.view');

    Route::post('runs', [PayrollRunController::class, 'store'])
        ->middleware('permission:payroll.runs.process');

    Route::post('runs/{id}/process', [PayrollRunController::class, 'process'])
        ->middleware('permission:payroll.runs.process');

    Route::get('runs/{id}/payslips', [PayrollRunController::class, 'payslips'])
        ->middleware('permission:payroll.payslips.view');
});
