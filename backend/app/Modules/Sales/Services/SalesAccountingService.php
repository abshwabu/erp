<?php

declare(strict_types=1);

namespace App\Modules\Sales\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountType;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\Journal;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Core\Models\User;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoicePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesAccountingService
{
    /**
     * Resolve or create a safe FiscalPeriod for the given date.
     */
    public function getOrCreatePeriod(string|\DateTimeInterface $date): FiscalPeriod
    {
        $dt = $date instanceof \DateTimeInterface ? $date : new \DateTime((string) $date);
        $year = (int) $dt->format('Y');
        $month = (int) $dt->format('n');

        $period = FiscalPeriod::where('year', $year)->where('month', $month)->first();

        if (!$period) {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = Carbon::create($year, $month, 1)->endOfMonth();

            $period = FiscalPeriod::create([
                'year' => $year,
                'month' => $month,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => 'open',
            ]);
        }

        return $period;
    }

    /**
     * Resolve standard accounts by code or fallback.
     */
    public function getAccount(string $code, string $fallbackType, string $fallbackName): Account
    {
        $account = Account::where('code', $code)->first();

        if ($account) {
            return $account;
        }

        // Try finding by name or type
        $type = AccountType::where('name', $fallbackType)->first()
            ?? AccountType::firstOrCreate(
                ['name' => $fallbackType],
                [
                    'normal_balance' => in_array($fallbackType, ['Asset', 'Expense', 'COGS'], true) ? 'debit' : 'credit',
                    'report_section' => $fallbackType . 's',
                    'sort_order' => 1,
                ]
            );

        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => $fallbackName,
                'account_type_id' => $type->id,
                'currency_code' => 'USD',
                'is_active' => true,
                'is_system_account' => true,
            ]
        );
    }

    /**
     * Post a Journal Entry when an Invoice is issued/sent or created as sent.
     * Dr Accounts Receivable (1200) / Cr Sales Revenue (4100) / Cr Tax Payable (2200)
     */
    public function postInvoiceJournal(Invoice $invoice): ?Journal
    {
        if (in_array($invoice->status, ['draft', 'void'], true)) {
            return null;
        }

        if ((int) $invoice->total_cents <= 0) {
            return null;
        }

        return DB::transaction(function () use ($invoice) {
            // Check if journal entry already exists for this invoice
            $existing = Journal::where('source_type', 'sales_invoice')
                ->where('source_id', (string) $invoice->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $customer = $invoice->customer;
            $customerName = $customer?->name ?? 'Customer';

            $arAccount = $this->getAccount('1200', 'Asset', 'Accounts Receivable');
            $salesAccount = $this->getAccount('4100', 'Revenue', 'Sales');
            $taxAccount = $this->getAccount('2200', 'Liability', 'Tax Payable');

            $issueDate = $invoice->issue_date instanceof \DateTimeInterface
                ? $invoice->issue_date->format('Y-m-d')
                : (string) $invoice->issue_date;

            $period = $this->getOrCreatePeriod($issueDate);
            $userId = Auth::id() ?? User::first()?->id;

            $journal = Journal::create([
                'reference' => 'JE-' . $invoice->number,
                'description' => "Sales Invoice #{$invoice->number} - {$customerName}",
                'journal_date' => $issueDate,
                'period_id' => $period->id,
                'status' => 'posted',
                'source_type' => 'sales_invoice',
                'source_id' => (string) $invoice->id,
                'posted_at' => now(),
                'posted_by_id' => $userId,
            ]);

            $subtotalCents = (int) $invoice->subtotal_cents;
            $taxCents = (int) $invoice->tax_cents;
            $totalCents = (int) $invoice->total_cents;

            // 1. Dr Accounts Receivable (Total)
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $arAccount->id,
                'debit_cents' => $totalCents,
                'credit_cents' => 0,
                'currency_code' => 'USD',
                'base_debit_cents' => $totalCents,
                'base_credit_cents' => 0,
                'exchange_rate' => 1,
                'description' => "Accounts Receivable - #{$invoice->number}",
            ]);

            // 2. Cr Sales Revenue (Subtotal)
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $salesAccount->id,
                'debit_cents' => 0,
                'credit_cents' => $subtotalCents,
                'currency_code' => 'USD',
                'base_debit_cents' => 0,
                'base_credit_cents' => $subtotalCents,
                'exchange_rate' => 1,
                'description' => "Sales Revenue - #{$invoice->number}",
            ]);

            // 3. Cr Tax Payable (Tax, if any)
            if ($taxCents > 0) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $taxAccount->id,
                    'debit_cents' => 0,
                    'credit_cents' => $taxCents,
                    'currency_code' => 'USD',
                    'base_debit_cents' => 0,
                    'base_credit_cents' => $taxCents,
                    'exchange_rate' => 1,
                    'description' => "Tax Payable - #{$invoice->number}",
                ]);
            }

            return $journal;
        });
    }

    /**
     * Post a Journal Entry when a Payment is recorded on an Invoice.
     * Dr Bank (1020) or Cash (1010) / Cr Accounts Receivable (1200)
     */
    public function postPaymentJournal(InvoicePayment $payment, Invoice $invoice): ?Journal
    {
        $amountCents = (int) $payment->amount_cents;
        if ($amountCents <= 0) {
            return null;
        }

        return DB::transaction(function () use ($payment, $invoice, $amountCents) {
            $existing = Journal::where('source_type', 'sales_payment')
                ->where('source_id', (string) $payment->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $isCash = strtolower((string) $payment->method) === 'cash';
            $cashOrBankAcc = $isCash
                ? $this->getAccount('1010', 'Asset', 'Cash')
                : $this->getAccount('1020', 'Asset', 'Bank');

            $arAccount = $this->getAccount('1200', 'Asset', 'Accounts Receivable');

            $paidAt = $payment->paid_at ? Carbon::parse($payment->paid_at) : now();
            $period = $this->getOrCreatePeriod($paidAt->toDateString());
            $userId = Auth::id() ?? User::first()?->id;

            $referenceId = substr((string) $payment->id, 0, 8);
            $journal = Journal::create([
                'reference' => 'PAY-' . $invoice->number . '-' . $referenceId,
                'description' => "Payment for Invoice #{$invoice->number} via " . ucfirst((string) $payment->method),
                'journal_date' => $paidAt->toDateString(),
                'period_id' => $period->id,
                'status' => 'posted',
                'source_type' => 'sales_payment',
                'source_id' => (string) $payment->id,
                'posted_at' => now(),
                'posted_by_id' => $userId,
            ]);

            // 1. Dr Cash or Bank
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $cashOrBankAcc->id,
                'debit_cents' => $amountCents,
                'credit_cents' => 0,
                'currency_code' => 'USD',
                'base_debit_cents' => $amountCents,
                'base_credit_cents' => 0,
                'exchange_rate' => 1,
                'description' => "Payment received for #{$invoice->number} (" . ucfirst((string) $payment->method) . ")",
            ]);

            // 2. Cr Accounts Receivable
            JournalLine::create([
                'journal_id' => $journal->id,
                'account_id' => $arAccount->id,
                'debit_cents' => 0,
                'credit_cents' => $amountCents,
                'currency_code' => 'USD',
                'base_debit_cents' => 0,
                'base_credit_cents' => $amountCents,
                'exchange_rate' => 1,
                'description' => "AR Settled for #{$invoice->number}",
            ]);

            return $journal;
        });
    }

    /**
     * Reverse or void journal entries if an Invoice is marked void.
     */
    public function voidInvoiceJournal(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $journals = Journal::where('source_type', 'sales_invoice')
                ->where('source_id', (string) $invoice->id)
                ->get();

            foreach ($journals as $journal) {
                if ($journal->status === 'posted') {
                    $journal->update(['status' => 'reversed']);
                }
            }
        });
    }

    /**
     * Retroactively sync all invoices and payments into accounting journals.
     */
    public function syncAllInvoices(): void
    {
        $invoices = Invoice::with(['payments', 'customer'])->get();

        foreach ($invoices as $invoice) {
            if (in_array($invoice->status, ['sent', 'paid'], true)) {
                $this->postInvoiceJournal($invoice);
            }

            foreach ($invoice->payments as $payment) {
                $this->postPaymentJournal($payment, $invoice);
            }
        }
    }
}
