<?php

declare(strict_types=1);

namespace App\Modules\POS\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\POS\Services\POSTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class POSTransactionController extends BaseController
{
    public function __construct(protected POSTransactionService $transactionService)
    {
    }

    /**
     * POST /api/pos/checkout
     */
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'uuid', 'exists:pos_sessions,id'],
            'customer_id' => ['nullable', 'uuid'],
            'location_id' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price_cents' => ['nullable', 'integer', 'min:0'],
            'items.*.discount_cents' => ['nullable', 'integer', 'min:0'],
            'items.*.variant_id' => ['nullable', 'uuid'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:cash,card,mobile'],
            'payments.*.amount_cents' => ['required', 'integer', 'min:1'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
            'payments.*.change_cents' => ['nullable', 'integer', 'min:0'],
            'discounts' => ['nullable', 'array'],
            'discounts.percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discounts.amount_cents' => ['nullable', 'integer', 'min:0'],
        ]);

        $transaction = $this->transactionService->createTransaction(
            $data['session_id'],
            $data['items'],
            $data['payments'],
            $data['customer_id'] ?? null,
            $data['discounts'] ?? [],
            $data['location_id'] ?? null
        );

        $tenant = tenant();
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];

        $companyInfo = [
            'name' => $settings['display_name'] ?? ($tenant?->name ?? 'Bina ERP'),
            'tin' => $settings['tax_id'] ?? '',
            'tax_id' => $settings['tax_id'] ?? '',
            'address' => $settings['company_address'] ?? '',
            'phone' => $settings['company_phone'] ?? '',
            'email' => $settings['company_email'] ?? '',
        ];

        $response = $transaction->toArray();
        $response['company'] = $companyInfo;

        return $this->createdResponse($response);
    }

    /**
     * GET /api/pos/receipts/{receiptNumber}
     */
    public function receipt(string $receiptNumber): JsonResponse
    {
        $transaction = POSTransaction::with(['items', 'payments', 'session.terminal'])
            ->where('receipt_number', $receiptNumber)
            ->firstOrFail();

        $tenant = tenant();
        $settings = is_array($tenant?->settings) ? $tenant->settings : [];

        $companyInfo = [
            'name' => $settings['display_name'] ?? ($tenant?->name ?? 'Bina ERP'),
            'tin' => $settings['tax_id'] ?? '',
            'tax_id' => $settings['tax_id'] ?? '',
            'address' => $settings['company_address'] ?? '',
            'phone' => $settings['company_phone'] ?? '',
            'email' => $settings['company_email'] ?? '',
        ];

        $response = $transaction->toArray();
        $response['company'] = $companyInfo;

        return $this->successResponse($response);
    }
}
