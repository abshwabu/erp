<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Accounting\Models\AccountType;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\Journal;
use App\Modules\Accounting\Models\JournalLine;
use Carbon\Carbon;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        // Account Types
        $types = [
            ['name' => 'Asset', 'normal_balance' => 'debit', 'report_section' => 'Assets', 'sort_order' => 1],
            ['name' => 'Liability', 'normal_balance' => 'credit', 'report_section' => 'Liabilities', 'sort_order' => 2],
            ['name' => 'Equity', 'normal_balance' => 'credit', 'report_section' => 'Equity', 'sort_order' => 3],
            ['name' => 'Revenue', 'normal_balance' => 'credit', 'report_section' => 'Revenue', 'sort_order' => 4],
            ['name' => 'COGS', 'normal_balance' => 'debit', 'report_section' => 'Expenses', 'sort_order' => 5],
            ['name' => 'Expense', 'normal_balance' => 'debit', 'report_section' => 'Expenses', 'sort_order' => 6],
        ];

        foreach ($types as $type) {
            AccountType::updateOrCreate(['name' => $type['name']], $type);
        }

        $assetType = AccountType::where('name', 'Asset')->first()->id;
        $liabilityType = AccountType::where('name', 'Liability')->first()->id;
        $equityType = AccountType::where('name', 'Equity')->first()->id;
        $revenueType = AccountType::where('name', 'Revenue')->first()->id;
        $cogsType = AccountType::where('name', 'COGS')->first()->id;
        $expenseType = AccountType::where('name', 'Expense')->first()->id;

        // Basic Chart of Accounts
        $accounts = [
            ['code' => '1000', 'name' => 'Current Assets', 'account_type_id' => $assetType],
            ['code' => '1010', 'name' => 'Cash', 'account_type_id' => $assetType, 'parent_code' => '1000'],
            ['code' => '1020', 'name' => 'Bank', 'account_type_id' => $assetType, 'parent_code' => '1000'],
            ['code' => '1200', 'name' => 'Accounts Receivable', 'account_type_id' => $assetType, 'parent_code' => '1000', 'is_system_account' => true],
            ['code' => '1400', 'name' => 'Inventory', 'account_type_id' => $assetType, 'parent_code' => '1000', 'is_system_account' => true],
            
            ['code' => '2000', 'name' => 'Current Liabilities', 'account_type_id' => $liabilityType],
            ['code' => '2100', 'name' => 'Accounts Payable', 'account_type_id' => $liabilityType, 'parent_code' => '2000', 'is_system_account' => true],
            ['code' => '2200', 'name' => 'Tax Payable', 'account_type_id' => $liabilityType, 'parent_code' => '2000'],
            
            ['code' => '3000', 'name' => 'Equity', 'account_type_id' => $equityType],
            ['code' => '3100', 'name' => 'Share Capital', 'account_type_id' => $equityType, 'parent_code' => '3000'],
            ['code' => '3200', 'name' => 'Retained Earnings', 'account_type_id' => $equityType, 'parent_code' => '3000', 'is_system_account' => true],
            
            ['code' => '4000', 'name' => 'Revenue', 'account_type_id' => $revenueType],
            ['code' => '4100', 'name' => 'Sales', 'account_type_id' => $revenueType, 'parent_code' => '4000'],
            
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'account_type_id' => $cogsType],
            ['code' => '5100', 'name' => 'COGS - Products', 'account_type_id' => $cogsType, 'parent_code' => '5000'],
            
            ['code' => '6000', 'name' => 'Operating Expenses', 'account_type_id' => $expenseType],
            ['code' => '6100', 'name' => 'Salary Expense', 'account_type_id' => $expenseType, 'parent_code' => '6000'],
            ['code' => '6200', 'name' => 'Rent Expense', 'account_type_id' => $expenseType, 'parent_code' => '6000'],
        ];

        foreach ($accounts as $acc) {
            $parent = null;
            if (isset($acc['parent_code'])) {
                $parent = Account::where('code', $acc['parent_code'])->first();
            }

            Account::updateOrCreate(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'account_type_id' => $acc['account_type_id'],
                    'parent_id' => $parent?->id,
                    'currency_code' => 'USD',
                    'is_system_account' => $acc['is_system_account'] ?? false,
                ]
            );
        }

        // Create Fiscal Periods for the current year
        $year = Carbon::now()->year;
        $periods = [];
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $end = Carbon::create($year, $m, 1)->endOfMonth();

            $periods[$m] = FiscalPeriod::updateOrCreate(
                ['year' => $year, 'month' => $m],
                [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => $m < Carbon::now()->month ? 'closed' : 'open'
                ]
            );
        }

        // Add some journals to make trial balance, P&L, balance sheet dynamic
        $cashAcc = Account::where('code', '1010')->first();
        $bankAcc = Account::where('code', '1020')->first();
        $shareCapAcc = Account::where('code', '3100')->first();
        $salesAcc = Account::where('code', '4100')->first();
        $cogsAcc = Account::where('code', '5100')->first();
        $inventoryAcc = Account::where('code', '1400')->first();
        $rentAcc = Account::where('code', '6200')->first();

        // 1. Initial capital investment ($50,000 Cash, $50,000 Share Capital)
        $j1 = Journal::create([
            'reference' => 'JE-0001',
            'description' => 'Initial capital contribution',
            'journal_date' => Carbon::create($year, 1, 5)->toDateString(),
            'period_id' => $periods[1]->id,
            'status' => 'posted',
            'posted_at' => Carbon::create($year, 1, 5, 10, 0, 0),
        ]);

        JournalLine::create([
            'journal_id' => $j1->id,
            'account_id' => $cashAcc->id,
            'debit_cents' => 5000000,
            'credit_cents' => 0,
            'currency_code' => 'USD',
            'base_debit_cents' => 5000000,
            'base_credit_cents' => 0,
            'exchange_rate' => 1.0,
            'description' => 'Capital receipt'
        ]);

        JournalLine::create([
            'journal_id' => $j1->id,
            'account_id' => $shareCapAcc->id,
            'debit_cents' => 0,
            'credit_cents' => 5000000,
            'currency_code' => 'USD',
            'base_debit_cents' => 0,
            'base_credit_cents' => 5000000,
            'exchange_rate' => 1.0,
            'description' => 'Capital receipt'
        ]);

        // 2. Rent payment ($2,000 Rent Expense, paid from Bank)
        // First transfer from Cash to Bank ($30,000)
        $j2 = Journal::create([
            'reference' => 'JE-0002',
            'description' => 'Transfer cash to bank',
            'journal_date' => Carbon::create($year, 1, 10)->toDateString(),
            'period_id' => $periods[1]->id,
            'status' => 'posted',
            'posted_at' => Carbon::create($year, 1, 10, 11, 0, 0),
        ]);

        JournalLine::create([
            'journal_id' => $j2->id,
            'account_id' => $bankAcc->id,
            'debit_cents' => 3000000,
            'credit_cents' => 0,
            'currency_code' => 'USD',
            'base_debit_cents' => 3000000,
            'base_credit_cents' => 0,
            'exchange_rate' => 1.0,
        ]);

        JournalLine::create([
            'journal_id' => $j2->id,
            'account_id' => $cashAcc->id,
            'debit_cents' => 0,
            'credit_cents' => 3000000,
            'currency_code' => 'USD',
            'base_debit_cents' => 0,
            'base_credit_cents' => 3000000,
            'exchange_rate' => 1.0,
        ]);

        // Rent expense JE
        $j3 = Journal::create([
            'reference' => 'JE-0003',
            'description' => 'January Office Rent Payment',
            'journal_date' => Carbon::create($year, 1, 15)->toDateString(),
            'period_id' => $periods[1]->id,
            'status' => 'posted',
            'posted_at' => Carbon::create($year, 1, 15, 14, 0, 0),
        ]);

        JournalLine::create([
            'journal_id' => $j3->id,
            'account_id' => $rentAcc->id,
            'debit_cents' => 200000,
            'credit_cents' => 0,
            'currency_code' => 'USD',
            'base_debit_cents' => 200000,
            'base_credit_cents' => 0,
            'exchange_rate' => 1.0,
        ]);

        JournalLine::create([
            'journal_id' => $j3->id,
            'account_id' => $bankAcc->id,
            'debit_cents' => 0,
            'credit_cents' => 200000,
            'currency_code' => 'USD',
            'base_debit_cents' => 0,
            'base_credit_cents' => 200000,
            'exchange_rate' => 1.0,
        ]);

        // 3. Purchase Inventory ($10,000 Inventory, paid from Bank)
        $j4 = Journal::create([
            'reference' => 'JE-0004',
            'description' => 'Purchase stock inventory',
            'journal_date' => Carbon::create($year, 1, 20)->toDateString(),
            'period_id' => $periods[1]->id,
            'status' => 'posted',
            'posted_at' => Carbon::create($year, 1, 20, 9, 30, 0),
        ]);

        JournalLine::create([
            'journal_id' => $j4->id,
            'account_id' => $inventoryAcc->id,
            'debit_cents' => 1000000,
            'credit_cents' => 0,
            'currency_code' => 'USD',
            'base_debit_cents' => 1000000,
            'base_credit_cents' => 0,
            'exchange_rate' => 1.0,
        ]);

        JournalLine::create([
            'journal_id' => $j4->id,
            'account_id' => $bankAcc->id,
            'debit_cents' => 0,
            'credit_cents' => 1000000,
            'currency_code' => 'USD',
            'base_debit_cents' => 0,
            'base_credit_cents' => 1000000,
            'exchange_rate' => 1.0,
        ]);

        // 4. Sell goods ($15,000 Sales, Cash received; COGS $6,000 from inventory)
        $j5 = Journal::create([
            'reference' => 'JE-0005',
            'description' => 'Sales transaction and COGS recognition',
            'journal_date' => Carbon::create($year, 1, 25)->toDateString(),
            'period_id' => $periods[1]->id,
            'status' => 'posted',
            'posted_at' => Carbon::create($year, 1, 25, 16, 0, 0),
        ]);

        // Debit Cash $15,000
        JournalLine::create([
            'journal_id' => $j5->id,
            'account_id' => $cashAcc->id,
            'debit_cents' => 1500000,
            'credit_cents' => 0,
            'currency_code' => 'USD',
            'base_debit_cents' => 1500000,
            'base_credit_cents' => 0,
            'exchange_rate' => 1.0,
        ]);

        // Credit Sales $15,000
        JournalLine::create([
            'journal_id' => $j5->id,
            'account_id' => $salesAcc->id,
            'debit_cents' => 0,
            'credit_cents' => 1500000,
            'currency_code' => 'USD',
            'base_debit_cents' => 0,
            'base_credit_cents' => 1500000,
            'exchange_rate' => 1.0,
        ]);

        // Debit COGS $6,000
        JournalLine::create([
            'journal_id' => $j5->id,
            'account_id' => $cogsAcc->id,
            'debit_cents' => 600000,
            'credit_cents' => 0,
            'currency_code' => 'USD',
            'base_debit_cents' => 600000,
            'base_credit_cents' => 0,
            'exchange_rate' => 1.0,
        ]);

        // Credit Inventory $6,000
        JournalLine::create([
            'journal_id' => $j5->id,
            'account_id' => $inventoryAcc->id,
            'debit_cents' => 0,
            'credit_cents' => 600000,
            'currency_code' => 'USD',
            'base_debit_cents' => 0,
            'base_credit_cents' => 600000,
            'exchange_rate' => 1.0,
        ]);
    }
}
