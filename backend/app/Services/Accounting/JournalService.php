<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\Account;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class JournalService
{
    /**
     * Create a new journal entry.
     */
    public function createJournal(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            $journalDate = $data['journal_date'];
            $period = $this->getPeriodForDate($journalDate);

            if (!$period || !$period->isOpen()) {
                throw new Exception("The fiscal period for this date is closed or does not exist.");
            }

            if (!$this->validateBalance($data['lines'])) {
                throw new Exception("The journal entry is not balanced. Total debits must equal total credits.");
            }

            $journal = Journal::create([
                'reference' => $data['reference'],
                'description' => $data['description'],
                'journal_date' => $journalDate,
                'period_id' => $period->id,
                'status' => 'draft',
                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,
            ]);

            foreach ($data['lines'] as $line) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'debit_cents' => $line['debit_cents'] ?? 0,
                    'credit_cents' => $line['credit_cents'] ?? 0,
                    'currency_code' => $line['currency_code'] ?? 'USD',
                    'base_debit_cents' => $line['base_debit_cents'] ?? ($line['debit_cents'] ?? 0),
                    'base_credit_cents' => $line['base_credit_cents'] ?? ($line['credit_cents'] ?? 0),
                    'exchange_rate' => $line['exchange_rate'] ?? 1,
                    'description' => $line['description'] ?? null,
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'project_id' => $line['project_id'] ?? null,
                    'employee_id' => $line['employee_id'] ?? null,
                ]);
            }

            return $journal;
        });
    }

    /**
     * Post a draft journal entry.
     */
    public function postJournal(string $journalId): void
    {
        DB::transaction(function () use ($journalId) {
            $journal = Journal::findOrFail($journalId);

            if ($journal->status !== 'draft') {
                throw new Exception("Only draft journals can be posted.");
            }

            $journal->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by_id' => Auth::id(),
            ]);
        });
    }

    /**
     * Reverse a posted journal entry.
     */
    public function reverseJournal(string $journalId, string $date): Journal
    {
        return DB::transaction(function () use ($journalId, $date) {
            $originalJournal = Journal::with('lines')->findOrFail($journalId);

            if ($originalJournal->status !== 'posted') {
                throw new Exception("Only posted journals can be reversed.");
            }

            $period = $this->getPeriodForDate($date);
            if (!$period || !$period->isOpen()) {
                throw new Exception("The fiscal period for the reversal date is closed or does not exist.");
            }

            $reversalJournal = Journal::create([
                'reference' => "REV-" . $originalJournal->reference,
                'description' => "Reversal of: " . $originalJournal->description,
                'journal_date' => $date,
                'period_id' => $period->id,
                'status' => 'posted',
                'reversal_of_id' => $originalJournal->id,
                'posted_at' => now(),
                'posted_by_id' => Auth::id(),
            ]);

            foreach ($originalJournal->lines as $line) {
                JournalLine::create([
                    'journal_id' => $reversalJournal->id,
                    'account_id' => $line->account_id,
                    'debit_cents' => $line->credit_cents,
                    'credit_cents' => $line->debit_cents,
                    'currency_code' => $line->currency_code,
                    'base_debit_cents' => $line->base_credit_cents,
                    'base_credit_cents' => $line->base_debit_cents,
                    'exchange_rate' => $line->exchange_rate,
                    'description' => "Reverse: " . $line->description,
                    'cost_center_id' => $line->cost_center_id,
                    'project_id' => $line->project_id,
                    'employee_id' => $line->employee_id,
                ]);
            }

            $originalJournal->update(['status' => 'reversed']);

            return $reversalJournal;
        });
    }

    /**
     * Validate that debits equal credits.
     */
    public function validateBalance(array $lines): bool
    {
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            $totalDebits += $line['debit_cents'] ?? 0;
            $totalCredits += $line['credit_cents'] ?? 0;
        }

        return $totalDebits === $totalCredits && $totalDebits > 0;
    }

    /**
     * Get the fiscal period for a given date.
     */
    protected function getPeriodForDate(string $date): ?FiscalPeriod
    {
        $dateTime = new \DateTime($date);
        $year = $dateTime->format('Y');
        $month = $dateTime->format('m');

        return FiscalPeriod::where('year', $year)
            ->where('month', (int)$month)
            ->first();
    }

    /**
     * Create journal entry from a template.
     */
    public function createFromTemplate(string $type, array $data): Journal
    {
        $lines = [];
        
        switch ($type) {
            case 'sale':
                // Dr Accounts Receivable / Cr Revenue / Cr Tax Payable
                $lines[] = ['account_id' => $data['ar_account_id'], 'debit_cents' => $data['total_amount']];
                $lines[] = ['account_id' => $data['revenue_account_id'], 'credit_cents' => $data['net_amount']];
                if ($data['tax_amount'] > 0) {
                    $lines[] = ['account_id' => $data['tax_account_id'], 'credit_cents' => $data['tax_amount']];
                }
                break;

            case 'cash_sale':
                // Dr Cash / Cr Revenue / Cr Tax Payable / Dr COGS / Cr Inventory
                $lines[] = ['account_id' => $data['cash_account_id'], 'debit_cents' => $data['total_amount']];
                $lines[] = ['account_id' => $data['revenue_account_id'], 'credit_cents' => $data['net_amount']];
                if ($data['tax_amount'] > 0) {
                    $lines[] = ['account_id' => $data['tax_account_id'], 'credit_cents' => $data['tax_amount']];
                }
                if ($data['cost_amount'] > 0) {
                    $lines[] = ['account_id' => $data['cogs_account_id'], 'debit_cents' => $data['cost_amount']];
                    $lines[] = ['account_id' => $data['inventory_account_id'], 'credit_cents' => $data['cost_amount']];
                }
                break;

            case 'supplier_invoice':
                // Dr Inventory (or Expense) / Cr Accounts Payable / Dr VAT Receivable
                $lines[] = ['account_id' => $data['asset_or_expense_account_id'], 'debit_cents' => $data['net_amount']];
                if ($data['tax_amount'] > 0) {
                    $lines[] = ['account_id' => $data['vat_receivable_account_id'], 'debit_cents' => $data['tax_amount']];
                }
                $lines[] = ['account_id' => $data['ap_account_id'], 'credit_cents' => $data['total_amount']];
                break;

            case 'supplier_payment':
                // Dr Accounts Payable / Cr Bank
                $lines[] = ['account_id' => $data['ap_account_id'], 'debit_cents' => $data['amount']];
                $lines[] = ['account_id' => $data['bank_account_id'], 'credit_cents' => $data['amount']];
                break;

            case 'customer_payment':
                // Dr Bank / Cr Accounts Receivable
                $lines[] = ['account_id' => $data['bank_account_id'], 'debit_cents' => $data['amount']];
                $lines[] = ['account_id' => $data['ar_account_id'], 'credit_cents' => $data['amount']];
                break;

            case 'payroll':
                // Dr Salary Expense (per dept) / Cr Payroll Payable / Cr Tax Payable / Cr Pension Payable
                foreach ($data['salary_expenses'] as $deptExpense) {
                    $lines[] = [
                        'account_id' => $deptExpense['account_id'], 
                        'debit_cents' => $deptExpense['amount'],
                        'cost_center_id' => $deptExpense['cost_center_id']
                    ];
                }
                $lines[] = ['account_id' => $data['payroll_payable_account_id'], 'credit_cents' => $data['net_salary']];
                $lines[] = ['account_id' => $data['tax_payable_account_id'], 'credit_cents' => $data['tax_amount']];
                $lines[] = ['account_id' => $data['pension_payable_account_id'], 'credit_cents' => $data['pension_amount']];
                break;

            default:
                throw new Exception("Unknown journal template type: {$type}");
        }

        return $this->createJournal([
            'reference' => $data['reference'],
            'description' => $data['description'],
            'journal_date' => $data['journal_date'],
            'source_type' => $data['source_type'] ?? $type,
            'source_id' => $data['source_id'] ?? null,
            'lines' => $lines
        ]);
    }
}
