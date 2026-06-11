<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotTracking extends InventoryModel
{
    protected $table = 'lot_tracking';

    protected $casts = [
        'expiry_date' => 'date',
        'received_date' => 'date',
        'quantity_remaining' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }
}
