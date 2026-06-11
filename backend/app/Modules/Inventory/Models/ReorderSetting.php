<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderSetting extends InventoryModel
{
    protected $table = 'reorder_settings';

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'reorder_quantity' => 'integer',
        'is_auto_reorder' => 'boolean',
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
