<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLocation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Product $product,
        public ?StockLocation $location,
        public int $availableQuantity,
        public int $minQuantity
    ) {
    }
}
