<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\Finance\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class FinanceTransactionController extends Controller
{
    public function __construct(
        protected FinanceService $financeService
    ) {}

    /**
     * GET /api/finance/transactions
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'type'           => $request->query('type'),
            'category_id'    => $request->query('category_id'),
            'status'         => $request->query('status'),
            'payment_method' => $request->query('payment_method'),
            'start_date'     => $request->query('start_date'),
            'end_date'       => $request->query('end_date'),
            'search'         => $request->query('search'),
        ];

        $perPage = (int) $request->query('per_page', 15);
        $transactions = $this->financeService->getTransactions($filters, $perPage);

        return response()->json($transactions);
    }

    /**
     * POST /api/finance/transactions
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'               => 'required|in:income,expense',
            'amount'             => 'required_without:amount_cents|numeric|gt:0',
            'amount_cents'       => 'nullable|integer|gt:0',
            'transaction_date'   => 'required|date',
            'category_id'        => 'nullable|uuid|exists:finance_categories,id',
            'account_id'         => 'nullable|uuid|exists:accounting_accounts,id',
            'payment_account_id' => 'nullable|uuid|exists:accounting_accounts,id',
            'payment_method'     => 'nullable|string|max:50',
            'reference'          => 'nullable|string|max:100',
            'payee_payer'        => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'status'             => 'nullable|in:completed,pending,cancelled',
            'receipt_url'        => 'nullable|string',
        ]);

        try {
            $transaction = $this->financeService->createTransaction($validated, $request->user()?->id);
            return response()->json([
                'message' => ucfirst($validated['type']) . ' recorded successfully and posted to accounting ledger.',
                'data'    => $transaction,
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to record transaction: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/finance/transactions/{id}
     */
    public function show(string $id): JsonResponse
    {
        $transaction = FinanceTransaction::with([
            'category',
            'account',
            'paymentAccount',
            'journal.lines.account',
            'createdBy',
        ])->findOrFail($id);

        return response()->json($transaction);
    }

    /**
     * PUT /api/finance/transactions/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $transaction = FinanceTransaction::findOrFail($id);

        $validated = $request->validate([
            'amount'             => 'nullable|numeric|gt:0',
            'amount_cents'       => 'nullable|integer|gt:0',
            'transaction_date'   => 'nullable|date',
            'category_id'        => 'nullable|uuid|exists:finance_categories,id',
            'account_id'         => 'nullable|uuid|exists:accounting_accounts,id',
            'payment_account_id' => 'nullable|uuid|exists:accounting_accounts,id',
            'payment_method'     => 'nullable|string|max:50',
            'reference'          => 'nullable|string|max:100',
            'payee_payer'        => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'status'             => 'nullable|in:completed,pending,cancelled',
            'receipt_url'        => 'nullable|string',
        ]);

        try {
            $updated = $this->financeService->updateTransaction($transaction, $validated, $request->user()?->id);
            return response()->json([
                'message' => 'Transaction updated successfully.',
                'data'    => $updated,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update transaction: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * DELETE /api/finance/transactions/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $transaction = FinanceTransaction::findOrFail($id);

        try {
            $this->financeService->deleteTransaction($transaction);
            return response()->json(['message' => 'Transaction deleted and corresponding ledger entry reversed.']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete transaction: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/finance/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $filters = [
            'start_date' => $request->query('start_date'),
            'end_date'   => $request->query('end_date'),
        ];

        $summary = $this->financeService->getSummary($filters);
        return response()->json($summary);
    }

    /**
     * GET /api/finance/accounts
     */
    public function accounts(): JsonResponse
    {
        $accounts = $this->financeService->getSelectableAccounts();
        return response()->json($accounts);
    }

    /**
     * GET/POST /api/finance/export
     */
    public function export(Request $request): SymfonyResponse
    {
        $format = strtolower((string) $request->input('format', $request->query('format', 'xlsx')));
        $filters = [
            'type'        => $request->input('type', $request->query('type')),
            'category_id' => $request->input('category_id', $request->query('category_id')),
            'status'      => $request->input('status', $request->query('status')),
            'start_date'  => $request->input('start_date', $request->query('start_date')),
            'end_date'    => $request->input('end_date', $request->query('end_date')),
        ];

        $export = $this->financeService->exportTransactions($filters, $format);

        $isInline = ($request->boolean('inline') || $request->query('inline') === 'true') && $format === 'pdf';
        $disposition = $isInline ? 'inline' : 'attachment';

        return response($export['content'], 200, [
            'Content-Type'        => $export['mime'],
            'Content-Disposition' => "{$disposition}; filename=\"{$export['filename']}\"",
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
