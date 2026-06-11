<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Observers;

use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockMovement;

class StockLevelObserver
{
    /**
     * Handle the StockMovement "created" event.
     */
    public function created(StockMovement $movement): void
    {
        // 1. Decrement from source location
        if ($movement->from_location_id !== null) {
            $fromLevel = StockLevel::where('product_id', $movement->product_id)
                ->where('variant_id', $movement->variant_id)
                ->where('location_id', $movement->from_location_id)
                ->lockForUpdate()
                ->first();

            if (!$fromLevel) {
                $fromLevel = StockLevel::create([
                    'product_id' => $movement->product_id,
                    'variant_id' => $movement->variant_id,
                    'location_id' => $movement->from_location_id,
                    'quantity_on_hand' => 0,
                ]);
            }

            $fromLevel->decrement('quantity_on_hand', $movement->quantity);
        }

        // 2. Increment to destination location
        if ($movement->to_location_id !== null) {
            $toLevel = StockLevel::where('product_id', $movement->product_id)
                ->where('variant_id', $movement->variant_id)
                ->where('location_id', $movement->to_location_id)
                ->lockForUpdate()
                ->first();

            if (!$toLevel) {
                $toLevel = StockLevel::create([
                    'product_id' => $movement->product_id,
                    'variant_id' => $movement->variant_id,
                    'location_id' => $movement->to_location_id,
                    'quantity_on_hand' => 0,
                ]);
            }

            $toLevel->increment('quantity_on_hand', $movement->quantity);
        }
    }
}
