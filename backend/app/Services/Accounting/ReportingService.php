<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\JournalLine;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportingService
{
    /**
     * Generate Trial Balance.
     */
    public function trialBalance(string $fromDate, string $toDate)
    {
        return DB::table('accounting_accounts as a')
            ->join('accounting_account_types as t', 'a.account_type_id', '=', 't.id')
            ->leftJoin('accounting_journal_lines as l', 'a.id', '=', 'l.account_id')
            ->leftJoin('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where(function($query) use ($toDate) {
                $query->where('j.status', 'posted')
                    ->where('j.journal_date', '<=', $toDate);
            })
            ->orWhereNull('j.id')
            ->select(
                'a.id',
                'a.code',
                'a.name',
                't.name as type_name',
                't.normal_balance',
                DB::raw('SUM(l.debit_cents) as total_debit'),
                DB::raw('SUM(l.credit_cents) as total_credit')
            )
            ->groupBy('a.id', 'a.code', 'a.name', 't.name', 't.normal_balance')
            ->orderBy('a.code')
            ->get()
            ->map(function ($row) {
                $debit = (int)$row->total_debit;
                $credit = (int)$row->total_credit;
                
                if ($row->normal_balance === 'debit') {
                    $row->balance = $debit - $credit;
                } else {
                    $row->balance = $credit - $debit;
                }
                
                return $row;
            });
    }

    /**
     * Generate Profit and Loss Statement.
     */
    public function profitAndLoss(string $fromDate, string $toDate)
    {
        $movements = DB::table('accounting_accounts as a')
            ->join('accounting_account_types as t', 'a.account_type_id', '=', 't.id')
            ->join('accounting_journal_lines as l', 'a.id', '=', 'l.account_id')
            ->join('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where('j.status', 'posted')
            ->whereBetween('j.journal_date', [$fromDate, $toDate])
            ->whereIn('t.name', ['Revenue', 'Expense', 'COGS'])
            ->select(
                't.name as type_name',
                'a.name as account_name',
                'a.code as account_code',
                DB::raw('SUM(l.debit_cents) as debit'),
                DB::raw('SUM(l.credit_cents) as credit')
            )
            ->groupBy('t.name', 'a.name', 'a.code')
            ->get();

        $sections = [
            'Revenue' => [],
            'COGS' => [],
            'Expense' => [],
        ];

        foreach ($movements as $m) {
            $balance = ($m->type_name === 'Revenue') 
                ? (int)$m->credit - (int)$m->debit 
                : (int)$m->debit - (int)$m->credit;
            
            $sections[$m->type_name][] = [
                'name' => $m->account_name,
                'code' => $m->account_code,
                'amount' => $balance
            ];
        }

        $totalRevenue = collect($sections['Revenue'])->sum('amount');
        $totalCOGS = collect($sections['COGS'])->sum('amount');
        $totalExpense = collect($sections['Expense'])->sum('amount');
        
        $grossProfit = $totalRevenue - $totalCOGS;
        $netIncome = $grossProfit - $totalExpense;

        return [
            'sections' => $sections,
            'totals' => [
                'revenue' => $totalRevenue,
                'cogs' => $totalCOGS,
                'gross_profit' => $grossProfit,
                'expense' => $totalExpense,
                'net_income' => $netIncome,
            ],
            'period' => [
                'from' => $fromDate,
                'to' => $toDate
            ]
        ];
    }

    /**
     * Generate Balance Sheet.
     */
    public function balanceSheet(string $asOfDate)
    {
        $balances = DB::table('accounting_accounts as a')
            ->join('accounting_account_types as t', 'a.account_type_id', '=', 't.id')
            ->leftJoin('accounting_journal_lines as l', 'a.id', '=', 'l.account_id')
            ->leftJoin('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where(function($query) use ($asOfDate) {
                $query->where('j.status', 'posted')
                    ->where('j.journal_date', '<=', $asOfDate);
            })
            ->whereIn('t.name', ['Asset', 'Liability', 'Equity'])
            ->select(
                't.name as type_name',
                'a.name as account_name',
                'a.code as account_code',
                't.normal_balance',
                DB::raw('SUM(l.debit_cents) as debit'),
                DB::raw('SUM(l.credit_cents) as credit')
            )
            ->groupBy('t.name', 'a.name', 'a.code', 't.normal_balance')
            ->get();

        // Calculate Net Income for Equity section (Revenue - Expense - COGS up to date)
        $plMovements = DB::table('accounting_accounts as a')
            ->join('accounting_account_types as t', 'a.account_type_id', '=', 't.id')
            ->join('accounting_journal_lines as l', 'a.id', '=', 'l.account_id')
            ->join('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where('j.status', 'posted')
            ->where('j.journal_date', '<=', $asOfDate)
            ->whereIn('t.name', ['Revenue', 'Expense', 'COGS'])
            ->select(
                't.name as type_name',
                DB::raw('SUM(l.debit_cents) as debit'),
                DB::raw('SUM(l.credit_cents) as credit')
            )
            ->groupBy('t.name')
            ->get();

        $retainedEarnings = 0;
        foreach ($plMovements as $pm) {
            if ($pm->type_name === 'Revenue') {
                $retainedEarnings += ((int)$pm->credit - (int)$pm->debit);
            } else {
                $retainedEarnings -= ((int)$pm->debit - (int)$pm->credit);
            }
        }

        $sections = [
            'Asset' => [],
            'Liability' => [],
            'Equity' => [],
        ];

        foreach ($balances as $b) {
            $amount = ($b->normal_balance === 'debit')
                ? (int)$b->debit - (int)$b->credit
                : (int)$b->credit - (int)$b->debit;

            $sections[$b->type_name][] = [
                'name' => $b->account_name,
                'code' => $b->account_code,
                'amount' => $amount
            ];
        }

        // Add Retained Earnings to Equity
        $sections['Equity'][] = [
            'name' => 'Retained Earnings (Net Income)',
            'code' => '9999',
            'amount' => $retainedEarnings
        ];

        return [
            'sections' => $sections,
            'totals' => [
                'assets' => collect($sections['Asset'])->sum('amount'),
                'liabilities' => collect($sections['Liability'])->sum('amount'),
                'equity' => collect($sections['Equity'])->sum('amount'),
            ],
            'as_of_date' => $asOfDate
        ];
    }

    /**
     * Generate General Ledger for an account.
     */
    public function generalLedger(string $accountId, string $fromDate, string $toDate)
    {
        $account = Account::findOrFail($accountId);

        // Opening Balance
        $opening = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where('l.account_id', $accountId)
            ->where('j.status', 'posted')
            ->where('j.journal_date', '<', $fromDate)
            ->select(
                DB::raw('SUM(l.debit_cents) as debit'),
                DB::raw('SUM(l.credit_cents) as credit')
            )
            ->first();

        $openingDebit = (int)($opening->debit ?? 0);
        $openingCredit = (int)($opening->credit ?? 0);

        // Movements
        $movements = DB::table('accounting_journal_lines as l')
            ->join('accounting_journals as j', 'l.journal_id', '=', 'j.id')
            ->where('l.account_id', $accountId)
            ->where('j.status', 'posted')
            ->whereBetween('j.journal_date', [$fromDate, $toDate])
            ->select(
                'j.journal_date',
                'j.reference',
                'l.description',
                'l.debit_cents',
                'l.credit_cents'
            )
            ->orderBy('j.journal_date')
            ->orderBy('j.created_at')
            ->get();

        $runningBalance = $openingDebit - $openingCredit;
        $report = [
            'opening_balance' => $runningBalance,
            'movements' => []
        ];

        foreach ($movements as $m) {
            $runningBalance += (int)$m->debit_cents;
            $runningBalance -= (int)$m->credit_cents;
            
            $report['movements'][] = [
                'date' => $m->journal_date,
                'reference' => $m->reference,
                'description' => $m->description,
                'debit' => (int)$m->debit_cents,
                'credit' => (int)$m->credit_cents,
                'balance' => $runningBalance
            ];
        }

        $report['closing_balance'] = $runningBalance;
        return $report;
    }

    /**
     * Accounts Receivable Aging.
     */
    public function arAging()
    {
        // Simplistic version using journal lines linked to customers
        // In a full implementation, we'd need an Invoice model and track payments
        // Here we'll just look at the AR account movements
        return []; 
    }

    /**
     * Accounts Payable Aging.
     */
    public function apAging()
    {
        return [];
    }
}
