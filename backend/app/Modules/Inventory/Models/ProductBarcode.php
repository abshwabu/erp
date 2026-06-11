<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\BarcodeType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBarcode extends InventoryModel
{
    protected $table = 'product_barcodes';

    protected $casts = [
        'type' => BarcodeType::class,
        'is_primary' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
