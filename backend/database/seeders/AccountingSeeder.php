<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\AccountType;
use App\Models\Accounting\Account;
use App\Models\Accounting\FiscalPeriod;
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
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($year, $m, 1)->startOfMonth();
            $end = Carbon::create($year, $m, 1)->endOfMonth();

            FiscalPeriod::updateOrCreate(
                ['year' => $year, 'month' => $m],
                [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'status' => 'open'
                ]
            );
        }
    }
}
