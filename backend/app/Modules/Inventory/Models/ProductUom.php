<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUom extends InventoryModel
{
    protected $table = 'product_uom';

    protected $casts = [
        'purchase_to_stock_factor' => 'decimal:4',
        'sales_to_stock_factor' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchaseUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'purchase_uom_id');
    }

    public function stockUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'stock_uom_id');
    }

    public function salesUom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'sales_uom_id');
    }
}
