<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Finance\Controllers\FinanceTransactionController;
use App\Modules\Finance\Controllers\FinanceCategoryController;

Route::prefix('api/finance')->middleware('auth:api,sanctum')->group(function () {
    Route::get('summary', [FinanceTransactionController::class, 'summary']);
    Route::get('accounts', [FinanceTransactionController::class, 'accounts']);
    Route::match(['get', 'post'], 'export', [FinanceTransactionController::class, 'export']);

    Route::get('categories', [FinanceCategoryController::class, 'index']);
    Route::post('categories', [FinanceCategoryController::class, 'store']);
    Route::put('categories/{id}', [FinanceCategoryController::class, 'update']);
    Route::delete('categories/{id}', [FinanceCategoryController::class, 'destroy']);

    Route::get('transactions', [FinanceTransactionController::class, 'index']);
    Route::post('transactions', [FinanceTransactionController::class, 'store']);
    Route::get('transactions/{id}', [FinanceTransactionController::class, 'show']);
    Route::put('transactions/{id}', [FinanceTransactionController::class, 'update']);
    Route::delete('transactions/{id}', [FinanceTransactionController::class, 'destroy']);
});
