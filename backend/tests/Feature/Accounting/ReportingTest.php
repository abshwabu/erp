<?php

declare(strict_types=1);

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Models\User;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = $this->createTenant();
    $this->initializeTenant($this->tenant);
    $this->artisan('tenants:migrate');

    // Setup basic accounting data
    $this->assetType = AccountType::firstOrCreate(['name' => 'Asset'], [
        'normal_balance' => 'debit',
        'report_section' => 'Assets'
    ]);

    $this->revenueType = AccountType::firstOrCreate(['name' => 'Revenue'], [
        'normal_balance' => 'credit',
        'report_section' => 'Revenue'
    ]);

    $this->expenseType = AccountType::firstOrCreate(['name' => 'Expense'], [
        'normal_balance' => 'debit',
        'report_section' => 'Expenses'
    ]);

    $this->cashAccount = Account::firstOrCreate(['code' => '1010'], [
        'name' => 'Cash',
        'account_type_id' => $this->assetType->id,
        'currency_code' => 'USD'
    ]);

    $this->salesAccount = Account::firstOrCreate(['code' => '4100'], [
        'name' => 'Sales',
        'account_type_id' => $this->revenueType->id,
        'currency_code' => 'USD'
    ]);

    $this->rentAccount = Account::firstOrCreate(['code' => '6200'], [
        'name' => 'Rent',
        'account_type_id' => $this->expenseType->id,
        'currency_code' => 'USD'
    ]);

    $this->period = FiscalPeriod::firstOrCreate(['year' => 2026, 'month' => 6], [
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
        'status' => 'open'
    ]);
    $this->period->update(['status' => 'open']);

    $this->journalService = new JournalService();
    $this->reportingService = new ReportingService();
});

test('profit and loss net income calculation', function () {
    // 1. Sale: Dr Cash 1000, Cr Sales 1000
    $sale = $this->journalService->createJournal([
        'reference' => 'S001',
        'description' => 'Sale',
        'journal_date' => '2026-06-10',
        'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_cents' => 100000],
            ['account_id' => $this->salesAccount->id, 'credit_cents' => 100000],
        ]
    ]);
    $this->journalService->postJournal($sale->id);

    // 2. Expense: Dr Rent 400, Cr Cash 400
    $expense = $this->journalService->createJournal([
        'reference' => 'E001',
        'description' => 'Rent Payment',
        'journal_date' => '2026-06-15',
        'lines' => [
            ['account_id' => $this->rentAccount->id, 'debit_cents' => 40000],
            ['account_id' => $this->cashAccount->id, 'credit_cents' => 40000],
        ]
    ]);
    $this->journalService->postJournal($expense->id);

    $report = $this->reportingService->profitAndLoss('2026-06-01', '2026-06-30');

    expect($report['totals']['revenue'])->toBe(100000);
    expect($report['totals']['expense'])->toBe(40000);
    expect($report['totals']['net_income'])->toBe(60000);
});

test('trial balance totals match', function () {
    $sale = $this->journalService->createJournal([
        'reference' => 'S001',
        'description' => 'Sale',
        'journal_date' => '2026-06-10',
        'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_cents' => 100000],
            ['account_id' => $this->salesAccount->id, 'credit_cents' => 100000],
        ]
    ]);
    $this->journalService->postJournal($sale->id);

    $tb = $this->reportingService->trialBalance('2026-06-01', '2026-06-30');

    $totalDebits = $tb->sum('debits');
    $totalCredits = $tb->sum('credits');

    expect($totalDebits)->toBe($totalCredits);
    expect($totalDebits)->toBeGreaterThanOrEqual(100000);
});
