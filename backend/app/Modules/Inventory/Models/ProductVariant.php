<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends InventoryModel
{
    use SoftDeletes;

    protected $table = 'product_variants';

    protected $casts = [
        'attribute_value_ids' => 'array',
        'is_active' => 'boolean',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
