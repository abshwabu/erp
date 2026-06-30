<?php

declare(strict_types=1);

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\Journal;
use App\Models\User;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = $this->createTenant();
    $this->initializeTenant($this->tenant);
    $this->artisan('tenants:migrate');

    // Setup basic accounting data
    $this->debitType = AccountType::create([
        'name' => 'Asset',
        'normal_balance' => 'debit',
        'report_section' => 'Assets'
    ]);

    $this->creditType = AccountType::create([
        'name' => 'Revenue',
        'normal_balance' => 'credit',
        'report_section' => 'Revenue'
    ]);

    $this->cashAccount = Account::create([
        'name' => 'Cash',
        'code' => '1010',
        'account_type_id' => $this->debitType->id,
        'currency_code' => 'USD'
    ]);

    $this->salesAccount = Account::create([
        'name' => 'Sales',
        'code' => '4100',
        'account_type_id' => $this->creditType->id,
        'currency_code' => 'USD'
    ]);

    $this->period = FiscalPeriod::create([
        'year' => 2026,
        'month' => 6,
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
        'status' => 'open'
    ]);

    $this->service = new JournalService();
});

test('journal must balance', function () {
    $data = [
        'reference' => 'J001',
        'description' => 'Unbalanced journal',
        'journal_date' => '2026-06-16',
        'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_cents' => 10000],
            ['account_id' => $this->salesAccount->id, 'credit_cents' => 9000],
        ]
    ];

    expect(fn() => $this->service->createJournal($data))
        ->toThrow(Exception::class, "The journal entry is not balanced. Total debits must equal total credits.");
});

test('cannot post to closed period', function () {
    $this->period->update(['status' => 'hard_closed']);

    $data = [
        'reference' => 'J001',
        'description' => 'Closed period journal',
        'journal_date' => '2026-06-16',
        'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_cents' => 10000],
            ['account_id' => $this->salesAccount->id, 'credit_cents' => 10000],
        ]
    ];

    expect(fn() => $this->service->createJournal($data))
        ->toThrow(Exception::class, "The fiscal period for this date is closed or does not exist.");
});

test('reversing creates correct mirror journal', function () {
    $data = [
        'reference' => 'J001',
        'description' => 'Original journal',
        'journal_date' => '2026-06-16',
        'lines' => [
            ['account_id' => $this->cashAccount->id, 'debit_cents' => 10000, 'description' => 'Line 1'],
            ['account_id' => $this->salesAccount->id, 'credit_cents' => 10000, 'description' => 'Line 2'],
        ]
    ];

    $journal = $this->service->createJournal($data);
    $this->service->postJournal($journal->id);

    $reversal = $this->service->reverseJournal($journal->id, '2026-06-17');

    expect($reversal->reference)->toBe('REV-J001');
    expect($reversal->status)->toBe('posted');
    expect($reversal->lines)->toHaveCount(2);

    $debitLine = $reversal->lines->where('debit_cents', '>', 0)->first();
    $creditLine = $reversal->lines->where('credit_cents', '>', 0)->first();

    expect($debitLine->account_id)->toBe($this->salesAccount->id);
    expect($debitLine->debit_cents)->toBe(10000);

    expect($creditLine->account_id)->toBe($this->cashAccount->id);
    expect($creditLine->credit_cents)->toBe(10000);

    $journal->refresh();
    expect($journal->status)->toBe('reversed');
});
