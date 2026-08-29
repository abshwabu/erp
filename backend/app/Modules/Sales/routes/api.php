<?php

declare(strict_types=1);

use App\Modules\Sales\Controllers\CustomerController;
use App\Modules\Sales\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/sales')->middleware('auth:api,sanctum')->group(function () {
    // Customers
    Route::get('customers', [CustomerController::class, 'index'])
        ->middleware('permission:sales.invoices.create|sales.orders.view|sales.orders.create');

    Route::post('customers', [CustomerController::class, 'store'])
        ->middleware('permission:sales.invoices.create|sales.orders.create');

    Route::get('customers/{id}', [CustomerController::class, 'show'])
        ->middleware('permission:sales.invoices.create|sales.orders.view|sales.orders.create');

    Route::put('customers/{id}', [CustomerController::class, 'update'])
        ->middleware('permission:sales.invoices.create|sales.orders.create');

    Route::patch('customers/{id}', [CustomerController::class, 'update'])
        ->middleware('permission:sales.invoices.create|sales.orders.create');

    Route::delete('customers/{id}', [CustomerController::class, 'destroy'])
        ->middleware('permission:sales.invoices.create|sales.orders.create');

    // Invoices
    Route::get('invoices', [InvoiceController::class, 'index'])
        ->middleware('permission:sales.invoices.create|sales.orders.view');

    Route::post('invoices', [InvoiceController::class, 'store'])
        ->middleware('permission:sales.invoices.create');

    Route::get('invoices/{id}', [InvoiceController::class, 'show'])
        ->middleware('permission:sales.invoices.create|sales.orders.view');

    Route::post('invoices/{id}/mark-sent', [InvoiceController::class, 'markSent'])
        ->middleware('permission:sales.invoices.send|sales.invoices.create');

    Route::post('invoices/{id}/payments', [InvoiceController::class, 'recordPayment'])
        ->middleware('permission:sales.invoices.create');
});
