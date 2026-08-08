<?php

declare(strict_types=1);

namespace App\Modules\POS\Services;

use App\Events\SaleCompleted;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Services\StockService;
use App\Modules\POS\Models\POSPayment;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\POS\Models\POSTransactionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class POSTransactionService
{
    private const TAX_RATE = 0.05;

    public function __construct(protected StockService $stockService)
    {
    }

    /**
     * @param  array<int, array{product_id: string, quantity: float|int, unit_price_cents?: int, discount_cents?: int, variant_id?: string|null}>  $items
     * @param  array<int, array{method: string, amount_cents: int, reference?: string|null, change_cents?: int}>  $payments
     * @param  array<string, mixed>  $discounts
     */
    public function createTransaction(
        string $sessionId,
        array $items,
        array $payments,
        ?string $customerId,
        array $discounts = [],
        ?string $locationId = null
    ): POSTransaction {
        return DB::transaction(function () use ($sessionId, $items, $payments, $customerId, $discounts, $locationId) {
            $session = POSSession::with('terminal')->findOrFail($sessionId);

            if ($session->status !== 'open') {
                throw ValidationException::withMessages([
                    'session_id' => ['POS session is not open.'],
                ]);
            }

            $locationId = $locationId
                ?? $session->terminal?->location_id
                ?? null;

            // Prefer shop stock location when session is bound to a shop
            if ($session->shop_id) {
                $shop = \App\Modules\Shops\Models\Shop::find($session->shop_id);
                if ($shop) {
                    $locationId = $shop->stock_location_id;
                }
            }

            if (!$locationId) {
                $locationId = StockLocation::query()->where('is_active', true)->value('id');
            }

            if (!$locationId) {
                throw ValidationException::withMessages([
                    'location_id' => ['No stock location available for this sale.'],
                ]);
            }

            $normalizedItems = $this->normalizeItems($items);
            $this->validateInventory($normalizedItems, $locationId);
            $totals = $this->calculateTotals($normalizedItems, $discounts);

            $paymentSum = collect($payments)->sum(fn ($p) => (int) ($p['amount_cents'] ?? 0));
            if ($paymentSum < $totals['total']) {
                throw ValidationException::withMessages([
                    'payments' => ['Payment amount is less than the transaction total.'],
                ]);
            }

            $transaction = POSTransaction::create([
                'session_id' => $sessionId,
                'customer_id' => $customerId,
                'subtotal_cents' => $totals['subtotal'],
                'discount_cents' => $totals['discount'],
                'tax_cents' => $totals['tax'],
                'total_cents' => $totals['total'],
                'currency_code' => 'USD',
                'status' => 'completed',
                'receipt_number' => 'REC-' . strtoupper(substr(uniqid(), -8)),
                'created_at' => now(),
            ]);

            foreach ($normalizedItems as $item) {
                POSTransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                    'discount_cents' => $item['discount_cents'],
                    'tax_cents' => $item['tax_cents'],
                    'total_cents' => $item['total_cents'],
                ]);

                $this->stockService->issueStock(
                    $item['product_id'],
                    $locationId,
                    (int) $item['quantity'],
                    [
                        'type' => 'pos_transaction',
                        'id' => $transaction->id,
                    ],
                    null,
                    null,
                    $item['variant_id'] ?? null,
                    true // strict shop/terminal location — no cross-location spill
                );
            }

            foreach ($payments as $payment) {
                POSPayment::create([
                    'transaction_id' => $transaction->id,
                    'method' => $payment['method'],
                    'amount_cents' => (int) $payment['amount_cents'],
                    'reference' => $payment['reference'] ?? null,
                    'change_cents' => (int) ($payment['change_cents'] ?? 0),
                    'processed_at' => now(),
                ]);
            }

            if ($customerId) {
                $this->awardLoyaltyPoints($customerId, $totals['total']);
            }

            event(new SaleCompleted($transaction->load(['items', 'payments'])));

            return $transaction->load(['items', 'payments']);
        });
    }

    /**
     * @param  array<int, array{product_id: string, quantity: float|int, unit_price_cents?: int, discount_cents?: int, variant_id?: string|null}>  $items
     * @return array<int, array{product_id: string, variant_id: string|null, quantity: float, unit_price_cents: int, discount_cents: int, tax_cents: int, total_cents: int, line_subtotal: int}>
     */
    public function normalizeItems(array $items): array
    {
        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'items' => ['At least one item is required.'],
            ]);
        }

        $normalized = [];

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $quantity = (float) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'items' => ["Invalid quantity for product {$product->sku}."],
                ]);
            }

            $unitPrice = isset($item['unit_price_cents'])
                ? (int) $item['unit_price_cents']
                : (int) round(((float) ($product->selling_price ?? 0)) * 100);

            $discount = (int) ($item['discount_cents'] ?? 0);
            $lineSubtotal = (int) round($unitPrice * $quantity) - $discount;
            $lineTax = (int) round($lineSubtotal * self::TAX_RATE);

            $normalized[] = [
                'product_id' => $product->id,
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPrice,
                'discount_cents' => $discount,
                'tax_cents' => $lineTax,
                'total_cents' => $lineSubtotal + $lineTax,
                'line_subtotal' => $lineSubtotal,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{product_id: string, quantity: float, variant_id?: string|null}>  $items
     */
    public function validateInventory(array $items, string $locationId): void
    {
        foreach ($items as $item) {
            $available = $this->stockService->getAvailableQty(
                $item['product_id'],
                $locationId,
                $item['variant_id'] ?? null
            );

            if ($available < (int) $item['quantity']) {
                $product = Product::find($item['product_id']);
                throw ValidationException::withMessages([
                    'items' => [
                        sprintf(
                            'Insufficient stock for %s. Available: %d, requested: %d.',
                            $product?->name ?? $item['product_id'],
                            $available,
                            (int) $item['quantity']
                        ),
                    ],
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{line_subtotal: int, tax_cents: int, discount_cents: int}>  $items
     * @param  array{percent?: float|int, amount_cents?: int}  $discounts
     * @return array{subtotal: int, discount: int, tax: int, total: int}
     */
    public function calculateTotals(array $items, array $discounts = []): array
    {
        $subtotal = (int) collect($items)->sum(fn ($i) => $i['line_subtotal'] + $i['discount_cents']);
        $itemDiscount = (int) collect($items)->sum('discount_cents');

        $cartDiscount = 0;
        if (isset($discounts['amount_cents'])) {
            $cartDiscount = (int) $discounts['amount_cents'];
        } elseif (isset($discounts['percent'])) {
            $cartDiscount = (int) round($subtotal * ((float) $discounts['percent'] / 100));
        }

        $discount = $itemDiscount + $cartDiscount;
        $taxable = max(0, $subtotal - $cartDiscount);
        $tax = (int) round($taxable * self::TAX_RATE);
        $total = $taxable + $tax;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
        ];
    }

    public function awardLoyaltyPoints(string $customerId, int $totalCents): void
    {
        // Loyalty module not implemented yet — no-op for MVP.
    }
}
