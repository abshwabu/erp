<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Sales\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            ->select(
                'a.id',
                'a.code',
                'a.name',
                't.name as type_name',
                't.normal_balance',
                DB::raw("SUM(CASE WHEN j.status = 'posted' AND j.journal_date < '{$fromDate}' THEN l.debit_cents ELSE 0 END) as opening_debit"),
                DB::raw("SUM(CASE WHEN j.status = 'posted' AND j.journal_date < '{$fromDate}' THEN l.credit_cents ELSE 0 END) as opening_credit"),
                DB::raw("SUM(CASE WHEN j.status = 'posted' AND j.journal_date BETWEEN '{$fromDate}' AND '{$toDate}' THEN l.debit_cents ELSE 0 END) as period_debit"),
                DB::raw("SUM(CASE WHEN j.status = 'posted' AND j.journal_date BETWEEN '{$fromDate}' AND '{$toDate}' THEN l.credit_cents ELSE 0 END) as period_credit")
            )
            ->groupBy('a.id', 'a.code', 'a.name', 't.name', 't.normal_balance')
            ->orderBy('a.code')
            ->get()
            ->map(function ($row) {
                $opDebit = (int)$row->opening_debit;
                $opCredit = (int)$row->opening_credit;
                $pDebit = (int)$row->period_debit;
                $pCredit = (int)$row->period_credit;
                
                $normal = $row->normal_balance;
                if ($normal === 'debit') {
                    $row->opening_balance = $opDebit - $opCredit;
                    $row->closing_balance = $row->opening_balance + $pDebit - $pCredit;
                } else {
                    $row->opening_balance = $opCredit - $opDebit;
                    $row->closing_balance = $row->opening_balance + $pCredit - $pDebit;
                }
                
                $row->debits = $pDebit;
                $row->credits = $pCredit;
                
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
     * Pulls open sales invoices (sent with outstanding balance) when Sales tables exist.
     */
    public function arAging()
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasTable('customers')) {
            return [];
        }

        $invoices = Invoice::query()
            ->with('customer')
            ->where('status', 'sent')
            ->whereColumn('amount_paid_cents', '<', 'total_cents')
            ->get();

        if ($invoices->isEmpty()) {
            return [];
        }

        $today = Carbon::today();
        $totalOutstandingCents = $invoices->sum(fn (Invoice $invoice) => $invoice->outstandingCents());
        $grouped = $invoices->groupBy('customer_id');
        $rows = [];

        foreach ($grouped as $customerId => $customerInvoices) {
            $customer = $customerInvoices->first()->customer;
            $bucket0 = 0;
            $bucket31 = 0;
            $bucket61 = 0;
            $bucket90 = 0;
            $invoiceRows = [];

            foreach ($customerInvoices as $invoice) {
                $outstandingCents = $invoice->outstandingCents();
                $outstanding = $outstandingCents / 100;
                $daysOverdue = max(0, (int) Carbon::parse($invoice->due_date)->startOfDay()->diffInDays($today, false));

                if ($daysOverdue <= 30) {
                    $bucket = '0-30';
                    $bucket0 += $outstandingCents;
                } elseif ($daysOverdue <= 60) {
                    $bucket = '31-60';
                    $bucket31 += $outstandingCents;
                } elseif ($daysOverdue <= 90) {
                    $bucket = '61-90';
                    $bucket61 += $outstandingCents;
                } else {
                    $bucket = '90+';
                    $bucket90 += $outstandingCents;
                }

                $invoiceRows[] = [
                    'id' => $invoice->id,
                    'reference' => $invoice->number,
                    'dueDate' => $invoice->due_date->toDateString(),
                    'invoiceDate' => $invoice->issue_date->toDateString(),
                    'amount' => $outstanding,
                    'bucket' => $bucket,
                    'daysOverdue' => $daysOverdue,
                ];
            }

            $outstandingCents = $bucket0 + $bucket31 + $bucket61 + $bucket90;

            $rows[] = [
                'id' => (string) $customerId,
                'customerName' => $customer?->name ?? 'Unknown customer',
                'invoiceCount' => $customerInvoices->count(),
                'outstanding' => $outstandingCents / 100,
                'bucket0_30' => $bucket0 / 100,
                'bucket31_60' => $bucket31 / 100,
                'bucket61_90' => $bucket61 / 100,
                'bucket90_plus' => $bucket90 / 100,
                'percentage' => $totalOutstandingCents > 0
                    ? (int) round(($outstandingCents / $totalOutstandingCents) * 100)
                    : 0,
                'invoices' => $invoiceRows,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['outstanding'] <=> $a['outstanding']);

        return $rows;
    }

    /**
     * Accounts Payable Aging.
     * Uses open purchase orders (ordered, not fully received conceptually as unpaid) when present.
     */
    public function apAging()
    {
        if (! Schema::hasTable('purchase_orders') || ! Schema::hasTable('suppliers')) {
            return [];
        }

        $orders = DB::table('purchase_orders as po')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->where('po.status', 'ordered')
            ->where('po.total_cents', '>', 0)
            ->select(
                'po.id',
                'po.number as reference',
                'po.order_date',
                'po.supplier_id',
                's.name as supplier_name',
                'po.total_cents'
            )
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $today = Carbon::today();
        $totalOutstanding = $orders->sum('total_cents');
        $grouped = $orders->groupBy('supplier_id');
        $rows = [];

        foreach ($grouped as $supplierId => $supplierOrders) {
            $bucket0 = 0;
            $bucket31 = 0;
            $bucket61 = 0;
            $bucket90 = 0;
            $invoiceRows = [];

            foreach ($supplierOrders as $order) {
                $outstandingCents = (int) $order->total_cents;
                $outstanding = $outstandingCents / 100;
                $daysOverdue = max(0, (int) Carbon::parse($order->order_date)->startOfDay()->diffInDays($today, false));

                if ($daysOverdue <= 30) {
                    $bucket = '0-30';
                    $bucket0 += $outstandingCents;
                } elseif ($daysOverdue <= 60) {
                    $bucket = '31-60';
                    $bucket31 += $outstandingCents;
                } elseif ($daysOverdue <= 90) {
                    $bucket = '61-90';
                    $bucket61 += $outstandingCents;
                } else {
                    $bucket = '90+';
                    $bucket90 += $outstandingCents;
                }

                $invoiceRows[] = [
                    'id' => $order->id,
                    'reference' => $order->reference,
                    'dueDate' => $order->order_date,
                    'invoiceDate' => $order->order_date,
                    'amount' => $outstanding,
                    'bucket' => $bucket,
                    'daysOverdue' => $daysOverdue,
                ];
            }

            $outstandingCents = $bucket0 + $bucket31 + $bucket61 + $bucket90;

            $rows[] = [
                'id' => (string) $supplierId,
                'supplierName' => $supplierOrders->first()->supplier_name ?? 'Unknown supplier',
                'invoiceCount' => $supplierOrders->count(),
                'outstanding' => $outstandingCents / 100,
                'bucket0_30' => $bucket0 / 100,
                'bucket31_60' => $bucket31 / 100,
                'bucket61_90' => $bucket61 / 100,
                'bucket90_plus' => $bucket90 / 100,
                'percentage' => $totalOutstanding > 0
                    ? (int) round(($outstandingCents / $totalOutstanding) * 100)
                    : 0,
                'invoices' => $invoiceRows,
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['outstanding'] <=> $a['outstanding']);

        return $rows;
    }
}