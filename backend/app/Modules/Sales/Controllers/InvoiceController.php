<?php

declare(strict_types=1);

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Sales\Models\Invoice;
use App\Modules\Sales\Models\InvoicePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()
            ->with(['customer', 'lines', 'payments'])
            ->orderByDesc('issue_date')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return $this->successResponse($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'number' => ['nullable', 'string', 'max:50', 'unique:invoices,number'],
            'status' => ['nullable', Rule::in(['draft', 'sent'])],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'tax_cents' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price_cents' => ['required', 'integer', 'min:0'],
        ]);

        $invoice = DB::transaction(function () use ($validated) {
            $subtotal = 0;
            $lineRows = [];

            foreach ($validated['lines'] as $line) {
                $lineTotal = (int) round((float) $line['quantity'] * (int) $line['unit_price_cents']);
                $subtotal += $lineTotal;
                $lineRows[] = [
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price_cents' => (int) $line['unit_price_cents'],
                    'line_total_cents' => $lineTotal,
                ];
            }

            $taxCents = (int) ($validated['tax_cents'] ?? 0);
            $totalCents = $subtotal + $taxCents;

            $invoice = Invoice::create([
                'customer_id' => $validated['customer_id'],
                'number' => $validated['number'] ?? $this->nextInvoiceNumber(),
                'status' => $validated['status'] ?? 'draft',
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'subtotal_cents' => $subtotal,
                'tax_cents' => $taxCents,
                'total_cents' => $totalCents,
                'amount_paid_cents' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lineRows as $lineRow) {
                $invoice->lines()->create($lineRow);
            }

            // Post to Accounting if issued/sent
            if ($invoice->status === 'sent') {
                app(\App\Modules\Sales\Services\SalesAccountingService::class)->postInvoiceJournal($invoice);
            }

            return $invoice->load(['customer', 'lines', 'payments']);
        });

        return $this->createdResponse($invoice);
    }

    public function show(string $id): JsonResponse
    {
        $invoice = Invoice::with(['customer', 'lines', 'payments'])->findOrFail($id);

        return $this->successResponse($invoice);
    }

    public function markSent(string $id): JsonResponse
    {
        $invoice = Invoice::with(['customer', 'lines', 'payments'])->findOrFail($id);

        if ($invoice->status === 'void') {
            return $this->errorResponse('Cannot send a void invoice.', 422);
        }

        if ($invoice->status === 'paid') {
            return $this->errorResponse('Invoice is already paid.', 422);
        }

        $invoice->update(['status' => 'sent']);

        // Post to Accounting General Ledger & Financial Reports
        app(\App\Modules\Sales\Services\SalesAccountingService::class)->postInvoiceJournal($invoice);

        return $this->successResponse($invoice->fresh(['customer', 'lines', 'payments']));
    }

    public function recordPayment(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'amount_cents' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', 'max:50'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $invoice = Invoice::with(['customer', 'lines', 'payments'])->findOrFail($id);

        if (in_array($invoice->status, ['draft', 'void'], true)) {
            return $this->errorResponse('Payments can only be recorded on sent invoices.', 422);
        }

        if ($invoice->status === 'paid') {
            return $this->errorResponse('Invoice is already fully paid.', 422);
        }

        $outstanding = $invoice->outstandingCents();
        if ((int) $validated['amount_cents'] > $outstanding) {
            return $this->errorResponse('Payment exceeds outstanding balance.', 422);
        }

        $invoice = DB::transaction(function () use ($invoice, $validated) {
            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount_cents' => (int) $validated['amount_cents'],
                'method' => $validated['method'],
                'paid_at' => $validated['paid_at'] ?? now(),
                'reference' => $validated['reference'] ?? null,
            ]);

            $amountPaid = (int) $invoice->amount_paid_cents + (int) $validated['amount_cents'];
            $updates = ['amount_paid_cents' => $amountPaid];

            if ($amountPaid >= (int) $invoice->total_cents) {
                $updates['status'] = 'paid';
            }

            $invoice->update($updates);

            // Post to Accounting Cash/Bank & Settle Accounts Receivable
            app(\App\Modules\Sales\Services\SalesAccountingService::class)->postPaymentJournal($payment, $invoice);

            return $invoice->fresh(['customer', 'lines', 'payments']);
        });

        return $this->successResponse($invoice);
    }

    private function nextInvoiceNumber(): string
    {
        $latest = Invoice::query()
            ->where('number', 'like', 'INV-%')
            ->orderByDesc('number')
            ->value('number');

        $seq = 1;
        if ($latest && preg_match('/INV-(\d+)/', $latest, $matches)) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf('INV-%05d', $seq);
    }
}
