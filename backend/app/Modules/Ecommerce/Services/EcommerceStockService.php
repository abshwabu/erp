<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Services;

use App\Modules\Core\Models\User;
use App\Modules\Ecommerce\Models\EcommerceOrder;
use App\Modules\Inventory\Enums\ProductType;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcommerceStockService
{
    /**
     * Resolve default or active stock location for ecommerce operations.
     */
    public function resolveLocation(): StockLocation
    {
        return StockLocation::where('is_active', true)->first()
            ?? StockLocation::firstOrCreate(
                ['code' => 'WH-MAIN'],
                ['name' => 'Main Warehouse', 'type' => 'internal', 'is_active' => true]
            );
    }

    /**
     * Deduct inventory stock for an ecommerce order's items.
     */
    public function deductOrderStock(EcommerceOrder $order): void
    {
        if (empty($order->items) || !is_array($order->items)) {
            return;
        }

        $location = $this->resolveLocation();
        $userId = auth()->id() ?? User::first()?->id;

        DB::transaction(function () use ($order, $location, $userId) {
            foreach ($order->items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                if ($qty <= 0) {
                    continue;
                }

                $product = null;
                if (!empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                }

                if (!$product && !empty($item['sku'])) {
                    $product = Product::where('sku', $item['sku'])->first();
                }

                if (!$product && !empty($item['name'])) {
                    $product = Product::where('name', $item['name'])->first();
                }

                if (!$product) {
                    Log::warning("EcommerceStockService: Could not resolve product for order item", [
                        'order_id' => $order->id,
                        'item' => $item,
                    ]);
                    continue;
                }

                // Skip non-physical / service products
                if ($product->type === ProductType::Service || $product->type === 'service') {
                    continue;
                }

                $variantId = $item['variant_id'] ?? null;

                // Check if stock deduction already logged for this product & order
                $existingMovement = StockMovement::where('reference_type', 'ecommerce_order')
                    ->where('reference_id', (string) $order->id)
                    ->where('product_id', $product->id)
                    ->where('variant_id', $variantId)
                    ->where('type', 'sale')
                    ->first();

                if ($existingMovement) {
                    continue; // Prevent double deduction
                }

                StockMovement::create([
                    'product_id'       => $product->id,
                    'variant_id'       => $variantId,
                    'from_location_id' => $location->id,
                    'to_location_id'   => null,
                    'quantity'         => $qty,
                    'type'             => 'sale',
                    'reference_type'   => 'ecommerce_order',
                    'reference_id'     => (string) $order->id,
                    'unit_cost'        => (int) ($product->cost_price ?? 0),
                    'currency_code'    => $order->currency ?? 'USD',
                    'user_id'          => $userId,
                ]);
            }
        });
    }

    /**
     * Restore stock if an ecommerce order is cancelled or refunded.
     */
    public function restoreOrderStock(EcommerceOrder $order): void
    {
        $userId = auth()->id() ?? User::first()?->id;

        DB::transaction(function () use ($order, $userId) {
            // Find all sales movements made for this order
            $saleMovements = StockMovement::where('reference_type', 'ecommerce_order')
                ->where('reference_id', (string) $order->id)
                ->where('type', 'sale')
                ->get();

            foreach ($saleMovements as $movement) {
                // Check if return was already logged
                $existingReturn = StockMovement::where('reference_type', 'ecommerce_order_cancel')
                    ->where('reference_id', (string) $order->id)
                    ->where('product_id', $movement->product_id)
                    ->where('variant_id', $movement->variant_id)
                    ->where('type', 'return')
                    ->first();

                if ($existingReturn) {
                    continue;
                }

                StockMovement::create([
                    'product_id'       => $movement->product_id,
                    'variant_id'       => $movement->variant_id,
                    'from_location_id' => null,
                    'to_location_id'   => $movement->from_location_id ?? $this->resolveLocation()->id,
                    'quantity'         => $movement->quantity,
                    'type'             => 'return',
                    'reference_type'   => 'ecommerce_order_cancel',
                    'reference_id'     => (string) $order->id,
                    'unit_cost'        => $movement->unit_cost,
                    'currency_code'    => $movement->currency_code,
                    'user_id'          => $userId,
                ]);
            }
        });
    }
}
