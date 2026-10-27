<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\FiscalPeriod;
use App\Modules\Accounting\Models\Journal;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Inventory\Services\PurePdfReport;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class FinanceService
{
    /**
     * Standard default categories for auto-initialization.
     */
    public const DEFAULT_CATEGORIES = [
        // Income
        [
            'name' => 'Sales & Product Revenue',
            'type' => 'income',
            'color' => '#10B981',
            'description' => 'Direct sales revenue from products and merchandise',
            'account_code' => '4100',
        ],
        [
            'name' => 'Services & Consulting',
            'type' => 'income',
            'color' => '#059669',
            'description' => 'Professional fees, consulting, and service income',
            'account_code' => '4000',
        ],
        [
            'name' => 'Customer Payments',
            'type' => 'income',
            'color' => '#3B82F6',
            'description' => 'Direct payments and customer account settlements',
            'account_code' => '4000',
        ],
        [
            'name' => 'Interest & Other Income',
            'type' => 'income',
            'color' => '#8B5CF6',
            'description' => 'Bank interest, refunds, and miscellaneous receipts',
            'account_code' => '4000',
        ],
        // Expense
        [
            'name' => 'Rent & Premises',
            'type' => 'expense',
            'color' => '#EF4444',
            'description' => 'Office, shop, or warehouse rent and lease fees',
            'account_code' => '6200',
        ],
        [
            'name' => 'Salaries & Contractors',
            'type' => 'expense',
            'color' => '#F59E0B',
            'description' => 'Staff payroll, commissions, and contract labor',
            'account_code' => '6100',
        ],
        [
            'name' => 'Utilities & Internet',
            'type' => 'expense',
            'color' => '#EC4899',
            'description' => 'Electricity, water, gas, telecom, and internet',
            'account_code' => '6000',
        ],
        [
            'name' => 'Office Supplies & Software',
            'type' => 'expense',
            'color' => '#F97316',
            'description' => 'Stationery, software subscriptions, and computer equipment',
            'account_code' => '6000',
        ],
        [
            'name' => 'Marketing & Advertising',
            'type' => 'expense',
            'color' => '#06B6D4',
            'description' => 'Online ads, promotional events, and marketing collateral',
            'account_code' => '6000',
        ],
        [
            'name' => 'Travel & Meals',
            'type' => 'expense',
            'color' => '#84CC16',
            'description' => 'Transportation, lodging, fuel, and business meals',
            'account_code' => '6000',
        ],
        [
            'name' => 'Repairs & Maintenance',
            'type' => 'expense',
            'color' => '#14B8A6',
            'description' => 'Equipment servicing, vehicle upkeep, and facility maintenance',
            'account_code' => '6000',
        ],
        [
            'name' => 'Bank & Processing Fees',
            'type' => 'expense',
            'color' => '#64748B',
            'description' => 'Bank service charges, merchant gateway fees, and transaction fees',
            'account_code' => '6000',
        ],
        [
            'name' => 'Miscellaneous Expense',
            'type' => 'expense',
            'color' => '#94A3B8',
            'description' => 'Incidental operational expenditures',
            'account_code' => '6000',
        ],
    ];

    /**
     * Ensure default categories exist.
     */
    public function ensureDefaultCategories(): void
    {
        if (FinanceCategory::count() > 0) {
            return;
        }

        foreach (self::DEFAULT_CATEGORIES as $cat) {
            $account = null;
            if (isset($cat['account_code'])) {
                $account = Account::where('code', $cat['account_code'])->first();
            }

            FinanceCategory::create([
                'name' => $cat['name'],
                'type' => $cat['type'],
                'color' => $cat['color'],
                'description' => $cat['description'],
                'account_id' => $account?->id,
                'is_system' => true,
            ]);
        }
    }

    /**
     * Record a POS Sale as an Income transaction with balanced double-entry Accounting Journal.
     */
    public function recordPosSale(\App\Modules\POS\Models\POSTransaction $posTransaction): ?FinanceTransaction
    {
        $this->ensureDefaultCategories();

        if ($posTransaction->status !== 'completed' || (int) $posTransaction->total_cents <= 0) {
            return null;
        }

        return DB::transaction(function () use ($posTransaction) {
            // Check if already recorded by source_id or reference
            $existing = FinanceTransaction::where('source_type', 'pos_transaction')
                ->where('source_id', (string) $posTransaction->id)
                ->first();

            if (!$existing && $posTransaction->receipt_number) {
                $existing = FinanceTransaction::where('reference', $posTransaction->receipt_number)
                    ->where('type', 'income')
                    ->first();
            }

            if ($existing) {
                return $existing;
            }

            // Category: "Sales & Product Revenue"
            $category = FinanceCategory::where('name', 'Sales & Product Revenue')->first()
                ?: FinanceCategory::where('type', 'income')->first();

            $posTransaction->loadMissing(['payments', 'customer', 'session.terminal']);
            $firstPayment = $posTransaction->payments->first();
            $rawMethod = strtolower((string) ($firstPayment?->method ?? 'cash'));
            if ($rawMethod === 'bank' || $rawMethod === 'card') {
                $paymentMethod = 'bank_transfer';
            } elseif ($rawMethod === 'wallet' || $rawMethod === 'mobile') {
                $paymentMethod = 'mobile_money';
            } elseif (in_array($rawMethod, ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'], true)) {
                $paymentMethod = $rawMethod;
            } else {
                $paymentMethod = 'cash';
            }

            $isBank = in_array($paymentMethod, ['bank_transfer', 'check', 'credit_card'], true);
            $paymentAccount = $isBank
                ? (Account::where('code', '1020')->first() ?: Account::where('code', '1010')->first())
                : (Account::where('code', '1010')->first() ?: Account::where('code', '1020')->first());

            if (!$paymentAccount) {
                $paymentAccount = Account::whereHas('accountType', fn ($q) => $q->where('name', 'Asset'))->first();
            }

            $salesAccount = Account::where('code', '4100')->first()
                ?: Account::where('code', '4000')->first()
                ?: Account::whereHas('accountType', fn ($q) => $q->where('name', 'Revenue'))->first();

            $taxAccount = Account::where('code', '2200')->first()
                ?: Account::whereHas('accountType', fn ($q) => $q->where('name', 'Liability'))->first();

            $date = $posTransaction->created_at ? Carbon::parse($posTransaction->created_at)->toDateString() : now()->toDateString();
            $customerName = $posTransaction->customer_name ?: ($posTransaction->customer?->name ?: 'POS Walk-in Customer');
            $ref = $posTransaction->receipt_number ?: ('REC-' . substr((string) $posTransaction->id, 0, 8));
            $txnNumber = 'POS-' . $ref;

            // Guarantee unique transaction_number
            if (FinanceTransaction::where('transaction_number', $txnNumber)->exists()) {
                $txnNumber = 'POS-' . $ref . '-' . substr((string) $posTransaction->id, 0, 4);
            }

            $totalCents = (int) $posTransaction->total_cents;
            $taxCents = (int) ($posTransaction->tax_cents ?? 0);
            $salesCents = max(0, $totalCents - $taxCents);

            // Create Journal in Accounting if accounts are available
            $journal = null;
            if ($paymentAccount && $salesAccount) {
                $dateObj = Carbon::parse($date);
                $period = FiscalPeriod::firstOrCreate(
                    ['year' => $dateObj->year, 'month' => $dateObj->month],
                    [
                        'start_date' => $dateObj->copy()->startOfMonth()->toDateString(),
                        'end_date'   => $dateObj->copy()->endOfMonth()->toDateString(),
                        'status'     => 'open',
                    ]
                );
                if ($period->status !== 'open') {
                    $period->update(['status' => 'open']);
                }

                $journal = Journal::create([
                    'reference'    => 'JE-POS-' . $ref,
                    'description'  => "POS Sale #{$ref} - {$customerName}",
                    'journal_date' => $date,
                    'period_id'    => $period->id,
                    'status'       => 'posted',
                    'source_type'  => 'pos_transaction',
                    'source_id'    => (string) $posTransaction->id,
                    'posted_at'    => now(),
                    'posted_by_id' => $posTransaction->session?->cashier_id ?? Auth::id(),
                ]);

                // 1. Dr Cash or Bank (Total)
                JournalLine::create([
                    'journal_id'        => $journal->id,
                    'account_id'        => $paymentAccount->id,
                    'debit_cents'       => $totalCents,
                    'credit_cents'      => 0,
                    'currency_code'     => $posTransaction->currency_code ?: 'USD',
                    'base_debit_cents'  => $totalCents,
                    'base_credit_cents' => 0,
                    'exchange_rate'     => 1,
                    'description'       => "POS Receipt #{$ref} Payment",
                ]);

                // 2. Cr Sales Revenue
                JournalLine::create([
                    'journal_id'        => $journal->id,
                    'account_id'        => $salesAccount->id,
                    'debit_cents'       => 0,
                    'credit_cents'      => $salesCents,
                    'currency_code'     => $posTransaction->currency_code ?: 'USD',
                    'base_debit_cents'  => 0,
                    'base_credit_cents' => $salesCents,
                    'exchange_rate'     => 1,
                    'description'       => "POS Sales Revenue #{$ref}",
                ]);

                // 3. Cr Tax Payable if tax exists
                if ($taxCents > 0 && $taxAccount) {
                    JournalLine::create([
                        'journal_id'        => $journal->id,
                        'account_id'        => $taxAccount->id,
                        'debit_cents'       => 0,
                        'credit_cents'      => $taxCents,
                        'currency_code'     => $posTransaction->currency_code ?: 'USD',
                        'base_debit_cents'  => 0,
                        'base_credit_cents' => $taxCents,
                        'exchange_rate'     => 1,
                        'description'       => "Tax collected on POS #{$ref}",
                    ]);
                }
            }

            $financeTxn = FinanceTransaction::create([
                'transaction_number' => $txnNumber,
                'type'               => 'income',
                'category_id'        => $category?->id,
                'account_id'         => $salesAccount?->id,
                'payment_account_id' => $paymentAccount?->id,
                'journal_id'         => $journal?->id,
                'amount_cents'       => $totalCents,
                'currency_code'      => $posTransaction->currency_code ?: 'USD',
                'transaction_date'   => $date,
                'payment_method'     => $paymentMethod,
                'reference'          => $ref,
                'payee_payer'        => $customerName,
                'description'        => "POS Sale #{$ref}" . ($posTransaction->session?->terminal?->name ? " ({$posTransaction->session->terminal->name})" : ''),
                'status'             => 'completed',
                'source_type'        => 'pos_transaction',
                'source_id'          => (string) $posTransaction->id,
                'created_by_id'      => $posTransaction->session?->cashier_id ?? Auth::id(),
            ]);

            return $financeTxn;
        });
    }

    /**
     * Record an Invoice Payment as an Income transaction.
     */
    public function recordInvoicePayment(\App\Modules\Sales\Models\InvoicePayment $payment, \App\Modules\Sales\Models\Invoice $invoice): ?FinanceTransaction
    {
        $this->ensureDefaultCategories();

        if ((int) $payment->amount_cents <= 0) {
            return null;
        }

        return DB::transaction(function () use ($payment, $invoice) {
            $existing = FinanceTransaction::where('source_type', 'sales_payment')
                ->where('source_id', (string) $payment->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $category = FinanceCategory::where('name', 'Customer Payments')->first()
                ?: FinanceCategory::where('name', 'Sales & Product Revenue')->first()
                ?: FinanceCategory::where('type', 'income')->first();

            $method = strtolower((string) ($payment->method ?? 'bank_transfer'));
            $isCash = $method === 'cash';
            $paymentAccount = $isCash
                ? (Account::where('code', '1010')->first() ?: Account::where('code', '1020')->first())
                : (Account::where('code', '1020')->first() ?: Account::where('code', '1010')->first());

            $salesAccount = Account::where('code', '1200')->first()
                ?: Account::where('code', '4100')->first()
                ?: Account::where('code', '4000')->first();

            $date = $payment->paid_at ? Carbon::parse($payment->paid_at)->toDateString() : now()->toDateString();
            $customerName = $invoice->customer?->name ?? 'Customer';
            $ref = $invoice->number;
            $payRef = substr((string) $payment->id, 0, 8);
            $txnNumber = "PAY-{$ref}-{$payRef}";

            $journal = Journal::where('source_type', 'sales_payment')
                ->where('source_id', (string) $payment->id)
                ->first();

            $financeTxn = FinanceTransaction::create([
                'transaction_number' => $txnNumber,
                'type'               => 'income',
                'category_id'        => $category?->id,
                'account_id'         => $salesAccount?->id,
                'payment_account_id' => $paymentAccount?->id,
                'journal_id'         => $journal?->id,
                'amount_cents'       => (int) $payment->amount_cents,
                'currency_code'      => $invoice->currency_code ?: 'USD',
                'transaction_date'   => $date,
                'payment_method'     => in_array($method, ['cash', 'bank_transfer', 'check', 'credit_card', 'mobile_money'], true) ? $method : 'bank_transfer',
                'reference'          => $ref,
                'payee_payer'        => $customerName,
                'description'        => "Payment for Invoice #{$ref}",
                'status'             => 'completed',
                'source_type'        => 'sales_payment',
                'source_id'          => (string) $payment->id,
                'created_by_id'      => Auth::id(),
            ]);

            return $financeTxn;
        });
    }

    /**
     * Retroactively sync existing POS sales and invoice payments into finance income transactions.
     */
    public function syncExistingIncomes(): array
    {
        $this->ensureDefaultCategories();

        $syncedPos = 0;
        $syncedInvoices = 0;

        // 1. Sync POS Transactions
        if (class_exists(\App\Modules\POS\Models\POSTransaction::class)) {
            $alreadySyncedIds = FinanceTransaction::where('source_type', 'pos_transaction')
                ->whereNotNull('source_id')
                ->pluck('source_id')
                ->all();

            $posQuery = \App\Modules\POS\Models\POSTransaction::with(['payments', 'customer', 'session.terminal'])
                ->where('status', 'completed');

            if (!empty($alreadySyncedIds)) {
                $posQuery->whereNotIn('id', $alreadySyncedIds);
            }

            $unSyncedPos = $posQuery->get();

            foreach ($unSyncedPos as $pos) {
                try {
                    $this->recordPosSale($pos);
                    $syncedPos++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to sync POS {$pos->id}: " . $e->getMessage());
                }
            }
        }

        // 2. Sync Invoice Payments
        if (class_exists(\App\Modules\Sales\Models\InvoicePayment::class)) {
            $alreadySyncedPayIds = FinanceTransaction::where('source_type', 'sales_payment')
                ->whereNotNull('source_id')
                ->pluck('source_id')
                ->all();

            $payQuery = \App\Modules\Sales\Models\InvoicePayment::with('invoice.customer');

            if (!empty($alreadySyncedPayIds)) {
                $payQuery->whereNotIn('id', $alreadySyncedPayIds);
            }

            $unSyncedPayments = $payQuery->get();

            foreach ($unSyncedPayments as $payment) {
                if ($payment->invoice) {
                    try {
                        $this->recordInvoicePayment($payment, $payment->invoice);
                        $syncedInvoices++;
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Failed to sync payment {$payment->id}: " . $e->getMessage());
                    }
                }
            }
        }

        return [
            'pos_synced'      => $syncedPos,
            'invoices_synced' => $syncedInvoices,
            'total_synced'    => $syncedPos + $syncedInvoices,
        ];
    }

    /**
     * List transactions with dynamic filtering & eager relationships.
     */
    public function getTransactions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->ensureDefaultCategories();
        $this->syncExistingIncomes();

        $query = FinanceTransaction::query()
            ->with(['category', 'account', 'paymentAccount', 'journal.lines.account', 'createdBy']);

        if (!empty($filters['type']) && in_array($filters['type'], ['income', 'expense'], true)) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['completed', 'pending', 'cancelled'], true)) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('transaction_number', 'ilike', "%{$term}%")
                    ->orWhere('payee_payer', 'ilike', "%{$term}%")
                    ->orWhere('description', 'ilike', "%{$term}%")
                    ->orWhere('reference', 'ilike', "%{$term}%")
                    ->orWhereHas('category', function ($cq) use ($term) {
                        $cq->where('name', 'ilike', "%{$term}%");
                    });
            });
        }

        $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create a new Income or Expense transaction and automatically post its Journal Entry.
     */
    public function createTransaction(array $data, ?string $userId = null): FinanceTransaction
    {
        $this->ensureDefaultCategories();

        return DB::transaction(function () use ($data, $userId) {
            $type = strtolower(trim($data['type'] ?? 'expense'));
            if (!in_array($type, ['income', 'expense'], true)) {
                $type = 'expense';
            }

            $date = !empty($data['transaction_date']) ? Carbon::parse($data['transaction_date'])->toDateString() : now()->toDateString();
            $number = $data['transaction_number'] ?? $this->generateTransactionNumber($type, $date);

            // Amount conversion (accepts amount in dollars/floats or amount_cents)
            $amountCents = 0;
            if (isset($data['amount_cents'])) {
                $amountCents = (int) $data['amount_cents'];
            } elseif (isset($data['amount'])) {
                $amountCents = (int) round(((float) $data['amount']) * 100);
            }

            if ($amountCents <= 0) {
                throw new Exception("Transaction amount must be greater than zero.");
            }

            $transaction = FinanceTransaction::create([
                'transaction_number' => $number,
                'type'               => $type,
                'category_id'        => $data['category_id'] ?? null,
                'account_id'         => $data['account_id'] ?? null,
                'payment_account_id' => $data['payment_account_id'] ?? null,
                'amount_cents'       => $amountCents,
                'currency_code'      => $data['currency_code'] ?? 'USD',
                'transaction_date'   => $date,
                'payment_method'     => $data['payment_method'] ?? 'cash',
                'reference'          => $data['reference'] ?? null,
                'payee_payer'        => $data['payee_payer'] ?? null,
                'description'        => $data['description'] ?? null,
                'status'             => $data['status'] ?? 'completed',
                'receipt_url'        => $data['receipt_url'] ?? null,
                'created_by_id'      => $userId ?: Auth::id(),
            ]);

            // Sync to Accounting module
            $this->syncJournalEntry($transaction, $userId);

            return $transaction->fresh(['category', 'account', 'paymentAccount', 'journal.lines.account']);
        });
    }

    /**
     * Update an existing transaction and synchronize its journal entry.
     */
    public function updateTransaction(FinanceTransaction $transaction, array $data, ?string $userId = null): FinanceTransaction
    {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            $amountCents = $transaction->amount_cents;
            if (isset($data['amount_cents'])) {
                $amountCents = (int) $data['amount_cents'];
            } elseif (isset($data['amount'])) {
                $amountCents = (int) round(((float) $data['amount']) * 100);
            }

            $transaction->update(array_filter([
                'category_id'        => $data['category_id'] ?? $transaction->category_id,
                'account_id'         => array_key_exists('account_id', $data) ? $data['account_id'] : $transaction->account_id,
                'payment_account_id' => array_key_exists('payment_account_id', $data) ? $data['payment_account_id'] : $transaction->payment_account_id,
                'amount_cents'       => $amountCents,
                'currency_code'      => $data['currency_code'] ?? $transaction->currency_code,
                'transaction_date'   => !empty($data['transaction_date']) ? Carbon::parse($data['transaction_date'])->toDateString() : $transaction->transaction_date,
                'payment_method'     => $data['payment_method'] ?? $transaction->payment_method,
                'reference'          => array_key_exists('reference', $data) ? $data['reference'] : $transaction->reference,
                'payee_payer'        => array_key_exists('payee_payer', $data) ? $data['payee_payer'] : $transaction->payee_payer,
                'description'        => array_key_exists('description', $data) ? $data['description'] : $transaction->description,
                'status'             => $data['status'] ?? $transaction->status,
                'receipt_url'        => array_key_exists('receipt_url', $data) ? $data['receipt_url'] : $transaction->receipt_url,
            ], fn ($v) => $v !== null));

            // Re-sync with Accounting module
            if ($transaction->status === 'completed') {
                $this->syncJournalEntry($transaction, $userId);
            } elseif ($transaction->journal_id) {
                // If status changed to cancelled or pending, delete or reverse journal
                $this->removeJournalEntry($transaction);
            }

            return $transaction->fresh(['category', 'account', 'paymentAccount', 'journal.lines.account']);
        });
    }

    /**
     * Delete a transaction and clean up its corresponding journal entry.
     */
    public function deleteTransaction(FinanceTransaction $transaction): bool
    {
        return DB::transaction(function () use ($transaction) {
            $this->removeJournalEntry($transaction);
            return (bool) $transaction->delete();
        });
    }

    /**
     * Create or update balanced double-entry Journal entry in the Accounting module.
     */
    public function syncJournalEntry(FinanceTransaction $transaction, ?string $userId = null): ?Journal
    {
        if ($transaction->status !== 'completed' || $transaction->amount_cents <= 0) {
            return null;
        }

        return DB::transaction(function () use ($transaction, $userId) {
            // 1. Ensure fiscal period is open
            $date = Carbon::parse($transaction->transaction_date);
            $period = FiscalPeriod::firstOrCreate(
                ['year' => $date->year, 'month' => $date->month],
                [
                    'start_date' => $date->copy()->startOfMonth()->toDateString(),
                    'end_date'   => $date->copy()->endOfMonth()->toDateString(),
                    'status'     => 'open',
                ]
            );
            if ($period->status !== 'open') {
                $period->update(['status' => 'open']);
            }

            // 2. Resolve Payment Account (Asset, e.g. Cash 1010 or Bank 1020)
            $paymentAccountId = $transaction->payment_account_id;
            if (!$paymentAccountId) {
                $isBank = in_array($transaction->payment_method, ['bank_transfer', 'check', 'credit_card'], true);
                $paymentAccount = $isBank
                    ? (Account::where('code', '1020')->first() ?: Account::where('code', '1010')->first())
                    : (Account::where('code', '1010')->first() ?: Account::where('code', '1020')->first());

                if (!$paymentAccount) {
                    $paymentAccount = Account::whereHas('accountType', fn($q) => $q->where('name', 'Asset'))->first();
                }

                if ($paymentAccount) {
                    $paymentAccountId = $paymentAccount->id;
                    $transaction->updateQuietly(['payment_account_id' => $paymentAccountId]);
                }
            }

            // 3. Resolve Target Ledger Account (Revenue for Income, Expense for Expense)
            $targetAccountId = $transaction->account_id;
            if (!$targetAccountId && $transaction->category_id) {
                $cat = $transaction->category ?: FinanceCategory::find($transaction->category_id);
                $targetAccountId = $cat?->account_id;
            }

            if (!$targetAccountId) {
                if ($transaction->type === 'income') {
                    $targetAccount = Account::where('code', '4100')->first()
                        ?: Account::where('code', '4000')->first()
                        ?: Account::whereHas('accountType', fn($q) => $q->where('name', 'Revenue'))->first();
                } else {
                    $targetAccount = Account::where('code', '6000')->first()
                        ?: Account::where('code', '6100')->first()
                        ?: Account::where('code', '6200')->first()
                        ?: Account::whereHas('accountType', fn($q) => $q->where('name', 'Expense'))->first();
                }

                if ($targetAccount) {
                    $targetAccountId = $targetAccount->id;
                    $transaction->updateQuietly(['account_id' => $targetAccountId]);
                }
            }

            // Fallback safety: If accounts table is empty, we cannot post double-entry lines
            if (!$paymentAccountId || !$targetAccountId) {
                return null;
            }

            $amount = (int) $transaction->amount_cents;
            $ref = $transaction->transaction_number;
            $memo = ($transaction->type === 'income' ? 'Income' : 'Expense')
                . ': ' . ($transaction->payee_payer ?: 'General')
                . ($transaction->description ? " - {$transaction->description}" : '');

            // 4. Create or Update Journal Header
            $journal = null;
            if ($transaction->journal_id) {
                $journal = Journal::find($transaction->journal_id);
            }

            if ($journal) {
                $journal->update([
                    'reference'    => $ref,
                    'description'  => $memo,
                    'journal_date' => $transaction->transaction_date,
                    'period_id'    => $period->id,
                    'status'       => 'posted',
                ]);
                $journal->lines()->delete();
            } else {
                $journal = Journal::create([
                    'reference'    => $ref,
                    'description'  => $memo,
                    'journal_date' => $transaction->transaction_date,
                    'period_id'    => $period->id,
                    'status'       => 'posted',
                    'source_type'  => 'finance_transaction',
                    'source_id'    => $transaction->id,
                    'posted_at'    => now(),
                    'posted_by_id' => $userId ?: Auth::id(),
                ]);
                $transaction->updateQuietly(['journal_id' => $journal->id]);
            }

            // 5. Create Double-Entry Lines
            if ($transaction->type === 'income') {
                // Income: Debit Cash/Bank (Asset +), Credit Revenue (Revenue +)
                JournalLine::create([
                    'journal_id'        => $journal->id,
                    'account_id'        => $paymentAccountId,
                    'debit_cents'       => $amount,
                    'credit_cents'      => 0,
                    'currency_code'     => $transaction->currency_code ?: 'USD',
                    'base_debit_cents'  => $amount,
                    'base_credit_cents' => 0,
                    'exchange_rate'     => 1,
                    'description'       => "Received: " . ($transaction->payee_payer ?: 'Income receipt'),
                ]);

                JournalLine::create([
                    'journal_id'        => $journal->id,
                    'account_id'        => $targetAccountId,
                    'debit_cents'       => 0,
                    'credit_cents'      => $amount,
                    'currency_code'     => $transaction->currency_code ?: 'USD',
                    'base_debit_cents'  => 0,
                    'base_credit_cents' => $amount,
                    'exchange_rate'     => 1,
                    'description'       => "Revenue: " . ($transaction->description ?: 'Operating income'),
                ]);
            } else {
                // Expense: Debit Expense (Expense +), Credit Cash/Bank (Asset -)
                JournalLine::create([
                    'journal_id'        => $journal->id,
                    'account_id'        => $targetAccountId,
                    'debit_cents'       => $amount,
                    'credit_cents'      => 0,
                    'currency_code'     => $transaction->currency_code ?: 'USD',
                    'base_debit_cents'  => $amount,
                    'base_credit_cents' => 0,
                    'exchange_rate'     => 1,
                    'description'       => "Expense: " . ($transaction->description ?: 'Operating expense'),
                ]);

                JournalLine::create([
                    'journal_id'        => $journal->id,
                    'account_id'        => $paymentAccountId,
                    'debit_cents'       => 0,
                    'credit_cents'      => $amount,
                    'currency_code'     => $transaction->currency_code ?: 'USD',
                    'base_debit_cents'  => 0,
                    'base_credit_cents' => $amount,
                    'exchange_rate'     => 1,
                    'description'       => "Payment to: " . ($transaction->payee_payer ?: 'Vendor / Payee'),
                ]);
            }

            return $journal;
        });
    }

    /**
     * Remove journal entry and unlink from transaction.
     */
    public function removeJournalEntry(FinanceTransaction $transaction): void
    {
        if (!$transaction->journal_id) {
            return;
        }

        $journal = Journal::find($transaction->journal_id);
        if ($journal) {
            $journal->lines()->delete();
            $journal->delete();
        }

        $transaction->updateQuietly(['journal_id' => null]);
    }

    /**
     * Compute comprehensive financial statistics.
     */
    public function getSummary(array $filters = []): array
    {
        $this->ensureDefaultCategories();
        $this->syncExistingIncomes();

        $base = FinanceTransaction::query();
        if (!empty($filters['start_date'])) {
            $base->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $base->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        $incomeQuery = (clone $base)->where('type', 'income')->where('status', 'completed');
        $expenseQuery = (clone $base)->where('type', 'expense')->where('status', 'completed');
        $pendingQuery = (clone $base)->where('status', 'pending');

        $totalIncomeCents = (int) $incomeQuery->sum('amount_cents');
        $totalExpenseCents = (int) $expenseQuery->sum('amount_cents');
        $netCents = $totalIncomeCents - $totalExpenseCents;

        $incomeCount = (int) $incomeQuery->count();
        $expenseCount = (int) $expenseQuery->count();
        $pendingCount = (int) $pendingQuery->count();
        $pendingCents = (int) $pendingQuery->sum('amount_cents');

        // Breakdown by Category
        $byCategory = FinanceCategory::withCount('transactions')
            ->get()
            ->map(function ($cat) {
                $totalCents = (int) FinanceTransaction::where('category_id', $cat->id)
                    ->where('status', 'completed')
                    ->sum('amount_cents');

                return [
                    'id'          => $cat->id,
                    'name'        => $cat->name,
                    'type'        => $cat->type,
                    'color'       => $cat->color,
                    'total_cents' => $totalCents,
                    'total'       => round($totalCents / 100, 2),
                ];
            })
            ->filter(fn ($c) => $c['total_cents'] > 0)
            ->values();

        // 6-Month Trend
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $year = $monthDate->year;
            $month = $monthDate->month;
            $label = $monthDate->format('M Y');

            $mIncome = (int) FinanceTransaction::whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->where('type', 'income')
                ->where('status', 'completed')
                ->sum('amount_cents');

            $mExpense = (int) FinanceTransaction::whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month)
                ->where('type', 'expense')
                ->where('status', 'completed')
                ->sum('amount_cents');

            $monthlyTrend[] = [
                'label'         => $label,
                'year'          => $year,
                'month'         => $month,
                'income'        => round($mIncome / 100, 2),
                'expense'       => round($mExpense / 100, 2),
                'net'           => round(($mIncome - $mExpense) / 100, 2),
                'income_cents'  => $mIncome,
                'expense_cents' => $mExpense,
            ];
        }

        return [
            'total_income_cents'  => $totalIncomeCents,
            'total_expense_cents' => $totalExpenseCents,
            'net_cents'           => $netCents,
            'total_income'        => round($totalIncomeCents / 100, 2),
            'total_expense'       => round($totalExpenseCents / 100, 2),
            'net'                 => round($netCents / 100, 2),
            'income_count'        => $incomeCount,
            'expense_count'       => $expenseCount,
            'total_transactions'  => $incomeCount + $expenseCount,
            'pending_count'       => $pendingCount,
            'pending_cents'       => $pendingCents,
            'pending_amount'      => round($pendingCents / 100, 2),
            'by_category'         => $byCategory,
            'monthly_trend'       => $monthlyTrend,
        ];
    }

    /**
     * Get accounts grouped into Cash/Bank, Revenue, and Expense for dropdown selectors.
     */
    public function getSelectableAccounts(): array
    {
        $assetAccounts = Account::whereHas('accountType', fn ($q) => $q->where('name', 'Asset'))
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'currency_code']);

        $revenueAccounts = Account::whereHas('accountType', fn ($q) => $q->where('name', 'Revenue'))
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'currency_code']);

        $expenseAccounts = Account::whereHas('accountType', fn ($q) => $q->whereIn('name', ['Expense', 'COGS']))
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'currency_code']);

        return [
            'payment_accounts' => $assetAccounts, // Cash & Bank accounts
            'revenue_accounts' => $revenueAccounts,
            'expense_accounts' => $expenseAccounts,
        ];
    }

    /**
     * Export transactions to XLSX, CSV, or PDF.
     */
    public function exportTransactions(array $filters = [], string $format = 'xlsx'): array
    {
        $this->ensureDefaultCategories();
        $this->syncExistingIncomes();

        $query = FinanceTransaction::query()
            ->with(['category', 'account', 'paymentAccount', 'createdBy']);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        $query->orderBy('transaction_date', 'desc');
        $transactions = $query->get();

        $headers = [
            'transaction_number' => 'Txn #',
            'transaction_date'   => 'Date',
            'type'               => 'Type',
            'category'           => 'Category',
            'payee_payer'        => 'Payee / Payer',
            'amount'             => 'Amount ($)',
            'payment_method'     => 'Method',
            'status'             => 'Status',
            'reference'          => 'Reference',
            'description'        => 'Description',
        ];

        $rows = [];
        foreach ($transactions as $t) {
            $rows[] = [
                'transaction_number' => $t->transaction_number,
                'transaction_date'   => $t->transaction_date ? Carbon::parse($t->transaction_date)->format('Y-m-d') : '',
                'type'               => ucfirst($t->type),
                'category'           => $t->category?->name ?? 'Uncategorized',
                'payee_payer'        => $t->payee_payer ?? '',
                'amount'             => number_format($t->amount, 2, '.', ''),
                'payment_method'     => str_replace('_', ' ', ucfirst($t->payment_method ?? 'cash')),
                'status'             => ucfirst($t->status ?? 'completed'),
                'reference'          => $t->reference ?? '',
                'description'        => $t->description ?? '',
            ];
        }

        return $this->generateExport($headers, $rows, $format, 'finance_transactions');
    }

    /**
     * Generate export file in CSV, PDF, or XLSX format.
     */
    public function generateExport(array $headers, array $rows, string $format = 'xlsx', string $prefix = 'finance_transactions'): array
    {
        $format = strtolower(trim($format));
        $timestamp = date('Ymd_His');

        if ($format === 'csv') {
            $output = fopen('php://memory', 'r+');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_values($headers), ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($output, array_values($row), ',', '"', '\\');
            }
            rewind($output);
            $content = stream_get_contents($output);
            fclose($output);

            return [
                'filename' => "{$prefix}_{$timestamp}.csv",
                'content'  => (string) $content,
                'mime'     => 'text/csv; charset=UTF-8',
            ];
        }

        if ($format === 'pdf') {
            $pdf = new PurePdfReport(true, count($headers));
            $pdfContent = $pdf->build('Finance Transactions Report', $headers, $rows);

            return [
                'filename' => "{$prefix}_{$timestamp}.pdf",
                'content'  => $pdfContent,
                'mime'     => 'application/pdf',
            ];
        }

        // Default XLSX
        return $this->generateXlsx($headers, $rows, "{$prefix}_{$timestamp}.xlsx");
    }

    /**
     * Generate standard XLSX using pure PHP ZipArchive.
     */
    private function generateXlsx(array $headers, array $rows, string $filename): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'fin_') . '.xlsx';
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $keys = array_keys($headers);
        $labels = array_values($headers);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Finance Transactions" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><name val="Calibri"/><sz val="10"/><color rgb="FF0F172A"/></font>'
            . '<font><b/><name val="Calibri"/><sz val="10"/><color rgb="FFFFFFFF"/></font>'
            . '</fonts>'
            . '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF0F172A"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="2" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"><alignment horizontal="right"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        $colWidths = [18, 14, 12, 22, 25, 16, 16, 14, 18, 35];
        $colsXml = '<cols>';
        foreach ($colWidths as $idx => $w) {
            $colsXml .= sprintf('<col min="%d" max="%d" width="%d" customWidth="1"/>', $idx + 1, $idx + 1, $w);
        }
        $colsXml .= '</cols>';

        $sheetDataXml = '<sheetData>';
        $sheetDataXml .= '<row r="1" ht="26" customHeight="1">';
        foreach ($labels as $colIdx => $colLabel) {
            $cellRef = chr(65 + $colIdx) . '1';
            $sheetDataXml .= sprintf('<c r="%s" t="inlineStr" s="1"><is><t>%s</t></is></c>', $cellRef, htmlspecialchars($colLabel, ENT_XML1, 'UTF-8'));
        }
        $sheetDataXml .= '</row>';

        $rowNum = 2;
        foreach ($rows as $r) {
            $isZebra = ($rowNum % 2 === 1);
            $sheetDataXml .= sprintf('<row r="%d" ht="20" customHeight="1">', $rowNum);
            foreach ($keys as $colIdx => $k) {
                $cellRef = chr(65 + $colIdx) . $rowNum;
                $val = (string) ($r[$k] ?? '');
                if ($k === 'amount' && is_numeric($val)) {
                    $sheetDataXml .= sprintf('<c r="%s" t="n" s="3"><v>%s</v></c>', $cellRef, $val);
                } else {
                    $style = $isZebra ? 2 : 0;
                    $sheetDataXml .= sprintf('<c r="%s" t="inlineStr" s="%d"><is><t>%s</t></is></c>', $cellRef, $style, htmlspecialchars($val, ENT_XML1, 'UTF-8'));
                }
            }
            $sheetDataXml .= '</row>';
            $rowNum++;
        }
        $sheetDataXml .= '</sheetData>';

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . $colsXml
            . $sheetDataXml
            . '</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);
        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return [
            'filename' => $filename,
            'content'  => (string) $content,
            'mime'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    /**
     * Generate unique transaction number.
     */
    protected function generateTransactionNumber(string $type, string $date): string
    {
        $prefix = $type === 'income' ? 'INC' : 'EXP';
        $ym = Carbon::parse($date)->format('Ym');
        $random = strtoupper(substr(uniqid(), -4));

        return "{$prefix}-{$ym}-{$random}";
    }
}
