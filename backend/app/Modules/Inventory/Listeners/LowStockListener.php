<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Listeners;

use App\Modules\Inventory\Events\LowStockDetected;
use Illuminate\Support\Facades\Log;

class LowStockListener
{
    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $locationName = $event->location ? $event->location->name : 'All locations';
        
        Log::warning(sprintf(
            'Low stock alert: Product "%s" (SKU: %s) at location "%s" has available quantity %d, which is at or below the minimum limit of %d.',
            $event->product->name,
            $event->product->sku,
            $locationName,
            $event->availableQuantity,
            $event->minQuantity
        ));
    }
}
