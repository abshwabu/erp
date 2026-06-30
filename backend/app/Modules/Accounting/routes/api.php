<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Accounting\Controllers\ChartOfAccountsController;
use App\Modules\Accounting\Controllers\JournalController;
use App\Modules\Accounting\Controllers\ReportController;

Route::prefix('api/accounting')->middleware('auth:api,sanctum')->group(function () {
    // Chart of Accounts
    Route::get('accounts', [ChartOfAccountsController::class, 'index']);
    Route::get('accounts/tree', [ChartOfAccountsController::class, 'tree']);
    Route::get('account-types', [ChartOfAccountsController::class, 'accountTypes']);
    Route::post('accounts', [ChartOfAccountsController::class, 'store']);
    Route::get('accounts/{id}', [ChartOfAccountsController::class, 'show']);
    Route::patch('accounts/{id}', [ChartOfAccountsController::class, 'update']);
    Route::delete('accounts/{id}', [ChartOfAccountsController::class, 'destroy']);
    Route::post('accounts/import', [ChartOfAccountsController::class, 'importCsv']);

    // Journals
    Route::get('journals', [JournalController::class, 'index']);
    Route::post('journals', [JournalController::class, 'store']);
    Route::get('journals/{id}', [JournalController::class, 'show']);
    Route::post('journals/{id}/post', [JournalController::class, 'post']);
    Route::post('journals/{id}/reverse', [JournalController::class, 'reverse']);
    Route::get('journals/{id}/lines', [JournalController::class, 'lines']);

    // Reports
    Route::get('reports/trial-balance', [ReportController::class, 'trialBalance']);
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);
    Route::get('reports/general-ledger', [ReportController::class, 'generalLedger']);
    Route::get('reports/ar-aging', [ReportController::class, 'arAging']);
    Route::get('reports/ap-aging', [ReportController::class, 'apAging']);
});
