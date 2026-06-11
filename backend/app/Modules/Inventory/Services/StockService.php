<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Events\StockMovementCreated;
use App\Modules\Inventory\Models\LotTracking;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\SerialNumber;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Resolve a product instance or find by ID.
     */
    protected function resolveProduct(Product|string $product): Product
    {
        return $product instanceof Product ? $product : Product::findOrFail($product);
    }

    /**
     * Resolve a location instance or find by ID.
     */
    protected function resolveLocation(StockLocation|string|null $location): ?StockLocation
    {
        if ($location === null) {
            return null;
        }
        return $location instanceof StockLocation ? $location : StockLocation::findOrFail($location);
    }

    /**
     * Receive stock into a location.
     */
    public function receiveStock(
        Product|string $product,
        StockLocation|string $location,
        int $qty,
        int $unitCost = 0,
        ?array $ref = null,
        ?string $lot = null,
        ?string $serial = null,
        mixed $expiry = null,
        ?string $variantId = null
    ): StockMovement {
        $product = $this->resolveProduct($product);
        $location = $this->resolveLocation($location);

        return DB::transaction(function () use ($product, $location, $qty, $unitCost, $ref, $lot, $serial, $expiry, $variantId) {
            // Lock the stock level row for update
            StockLevel::where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();

            $userId = auth()->id() ?? User::first()?->id;

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'from_location_id' => null,
                'to_location_id' => $location->id,
                'quantity' => $qty,
                'type' => 'receive',
                'reference_type' => $ref['type'] ?? null,
                'reference_id' => $ref['id'] ?? null,
                'lot_number' => $lot,
                'serial_number' => $serial,
                'expiry_date' => $expiry ? Carbon::parse($expiry) : null,
                'unit_cost' => (int) ($unitCost ?: ($product->cost_price ?? 0)),
                'currency_code' => $product->currency_code ?: 'USD',
                'user_id' => $userId,
            ]);

            // Lot Tracking
            if ($product->track_lots && $lot) {
                $lotTracking = LotTracking::where('product_id', $product->id)
                    ->where('lot_number', $lot)
                    ->where('location_id', $location->id)
                    ->lockForUpdate()
                    ->first();

                if ($lotTracking) {
                    $lotTracking->increment('quantity_remaining', $qty);
                } else {
                    LotTracking::create([
                        'product_id' => $product->id,
                        'lot_number' => $lot,
                        'expiry_date' => $expiry ? Carbon::parse($expiry) : null,
                        'received_date' => Carbon::today(),
                        'quantity_remaining' => $qty,
                        'location_id' => $location->id,
                    ]);
                }
            }

            // Serial Number Tracking
            if ($product->track_serial_numbers && $serial) {
                SerialNumber::create([
                    'product_id' => $product->id,
                    'serial_number' => $serial,
                    'status' => 'in_stock',
                    'location_id' => $location->id,
                ]);
            }

            event(new StockMovementCreated($movement));

            return $movement;
        });
    }

    /**
     * Issue stock out of a location.
     */
    public function issueStock(
        Product|string $product,
        StockLocation|string $location,
        int $qty,
        ?array $ref = null,
        ?string $lot = null,
        ?string $serial = null,
        ?string $variantId = null
    ): StockMovement {
        $product = $this->resolveProduct($product);
        $location = $this->resolveLocation($location);

        return DB::transaction(function () use ($product, $location, $qty, $ref, $lot, $serial, $variantId) {
            $level = StockLevel::where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();

            $onHand = $level ? $level->quantity_on_hand : 0;
            if ($onHand < $qty) {
                throw new InsufficientStockException("Insufficient stock to issue {$qty} units. On hand: {$onHand}.");
            }

            $userId = auth()->id() ?? User::first()?->id;

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'from_location_id' => $location->id,
                'to_location_id' => null,
                'quantity' => $qty,
                'type' => $ref['movement_type'] ?? 'sale',
                'reference_type' => $ref['type'] ?? null,
                'reference_id' => $ref['id'] ?? null,
                'lot_number' => $lot,
                'serial_number' => $serial,
                'unit_cost' => (int) ($product->cost_price ?? 0),
                'currency_code' => $product->currency_code ?: 'USD',
                'user_id' => $userId,
            ]);

            // Lot Tracking
            if ($product->track_lots && $lot) {
                $lotTracking = LotTracking::where('product_id', $product->id)
                    ->where('lot_number', $lot)
                    ->where('location_id', $location->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lotTracking || $lotTracking->quantity_remaining < $qty) {
                    throw new InsufficientStockException("Insufficient quantity in lot {$lot} at this location.");
                }

                $lotTracking->decrement('quantity_remaining', $qty);
            }

            // Serial Number Tracking
            if ($product->track_serial_numbers && $serial) {
                $serialNum = SerialNumber::where('product_id', $product->id)
                    ->where('serial_number', $serial)
                    ->where('location_id', $location->id)
                    ->where('status', 'in_stock')
                    ->lockForUpdate()
                    ->first();

                if (!$serialNum) {
                    throw new InsufficientStockException("Serial number {$serial} is not in stock at this location.");
                }

                $serialNum->update([
                    'status' => 'sold',
                    'sold_at' => Carbon::now(),
                    'location_id' => null,
                ]);
            }

            event(new StockMovementCreated($movement));

            return $movement;
        });
    }

    /**
     * Transfer stock between locations atomically.
     */
    public function transferStock(
        Product|string $product,
        StockLocation|string $fromLocation,
        StockLocation|string $toLocation,
        int $qty,
        ?array $ref = null,
        ?string $variantId = null
    ): array {
        $product = $this->resolveProduct($product);
        $fromLocation = $this->resolveLocation($fromLocation);
        $toLocation = $this->resolveLocation($toLocation);

        return DB::transaction(function () use ($product, $fromLocation, $toLocation, $qty, $ref, $variantId) {
            $fromLevel = StockLevel::where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->where('location_id', $fromLocation->id)
                ->lockForUpdate()
                ->first();

            StockLevel::where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->where('location_id', $toLocation->id)
                ->lockForUpdate()
                ->first();

            $onHand = $fromLevel ? $fromLevel->quantity_on_hand : 0;
            if ($onHand < $qty) {
                throw new InsufficientStockException("Insufficient stock to transfer {$qty} units. On hand: {$onHand}.");
            }

            $userId = auth()->id() ?? User::first()?->id;

            $movement = StockMovement::create([
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'from_location_id' => $fromLocation->id,
                'to_location_id' => $toLocation->id,
                'quantity' => $qty,
                'type' => 'transfer',
                'reference_type' => $ref['type'] ?? null,
                'reference_id' => $ref['id'] ?? null,
                'unit_cost' => (int) ($product->cost_price ?? 0),
                'currency_code' => $product->currency_code ?: 'USD',
                'user_id' => $userId,
            ]);

            $lot = $ref['lot_number'] ?? null;
            $serial = $ref['serial_number'] ?? null;

            // Lot Tracking
            if ($product->track_lots && $lot) {
                $fromLot = LotTracking::where('product_id', $product->id)
                    ->where('lot_number', $lot)
                    ->where('location_id', $fromLocation->id)
                    ->lockForUpdate()
                    ->first();

                if (!$fromLot || $fromLot->quantity_remaining < $qty) {
                    throw new InsufficientStockException("Insufficient quantity in lot {$lot} at source location.");
                }

                $fromLot->decrement('quantity_remaining', $qty);

                $toLot = LotTracking::where('product_id', $product->id)
                    ->where('lot_number', $lot)
                    ->where('location_id', $toLocation->id)
                    ->lockForUpdate()
                    ->first();

                if ($toLot) {
                    $toLot->increment('quantity_remaining', $qty);
                } else {
                    LotTracking::create([
                        'product_id' => $product->id,
                        'lot_number' => $lot,
                        'expiry_date' => $fromLot->expiry_date,
                        'received_date' => Carbon::today(),
                        'quantity_remaining' => $qty,
                        'location_id' => $toLocation->id,
                    ]);
                }
            }

            // Serial Number Tracking
            if ($product->track_serial_numbers && $serial) {
                $serialNum = SerialNumber::where('product_id', $product->id)
                    ->where('serial_number', $serial)
                    ->where('location_id', $fromLocation->id)
                    ->where('status', 'in_stock')
                    ->lockForUpdate()
                    ->first();

                if (!$serialNum) {
                    throw new InsufficientStockException("Serial number {$serial} is not in stock at source location.");
                }

                $serialNum->update([
                    'location_id' => $toLocation->id,
                ]);
            }

            event(new StockMovementCreated($movement));

            return [$movement];
        });
    }

    /**
     * Adjust stock at a location to a specific target quantity.
     */
    public function adjustStock(
        Product|string $product,
        StockLocation|string $location,
        int $qty,
        ?string $reason = null,
        ?string $notes = null,
        ?string $variantId = null
    ): StockMovement {
        $product = $this->resolveProduct($product);
        $location = $this->resolveLocation($location);

        return DB::transaction(function () use ($product, $location, $qty, $reason, $notes, $variantId) {
            $level = StockLevel::where('product_id', $product->id)
                ->where('variant_id', $variantId)
                ->where('location_id', $location->id)
                ->lockForUpdate()
                ->first();

            $currentQty = $level ? $level->quantity_on_hand : 0;
            $diff = $qty - $currentQty;

            $userId = auth()->id() ?? User::first()?->id;

            $movementData = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'quantity' => abs($diff),
                'type' => 'adjustment',
                'reference_type' => $reason,
                'notes' => $notes,
                'unit_cost' => (int) ($product->cost_price ?? 0),
                'currency_code' => $product->currency_code ?: 'USD',
                'user_id' => $userId,
            ];

            if ($diff >= 0) {
                $movementData['to_location_id'] = $location->id;
            } else {
                $movementData['from_location_id'] = $location->id;
            }

            $movement = StockMovement::create($movementData);

            event(new StockMovementCreated($movement));

            return $movement;
        });
    }

    /**
     * Get available quantity of a product, optionally filtered by location.
     */
    public function getAvailableQty(
        Product|string $product,
        StockLocation|string|null $location = null,
        ?string $variantId = null
    ): int {
        $product = $this->resolveProduct($product);
        $location = $this->resolveLocation($location);

        $query = StockLevel::where('product_id', $product->id)
            ->where('variant_id', $variantId);

        if ($location !== null) {
            $query->where('location_id', $location->id);
        }

        $levels = $query->get();

        $onHand = $levels->sum('quantity_on_hand');
        $committed = $levels->sum('quantity_committed');

        return (int) ($onHand - $committed);
    }
}
