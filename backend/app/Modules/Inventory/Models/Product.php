<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\ProductStatus;
use App\Modules\Inventory\Enums\ProductType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends InventoryModel
{
    use SoftDeletes;

    protected $table = 'products';

    protected $guarded = [];

    protected $casts = [
        'type' => ProductType::class,
        'status' => ProductStatus::class,
        'has_variants' => 'boolean',
        'track_serial_numbers' => 'boolean',
        'track_lots' => 'boolean',
        'cost_price' => 'integer',
        'selling_price' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ProductBarcode::class, 'product_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    public function uoms(): HasMany
    {
        return $this->hasMany(ProductUom::class, 'product_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'product_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    /**
     * Get the primary image URL.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryImage = $this->relationLoaded('images')
            ? ($this->images->where('is_primary', true)->first() ?? $this->images->first())
            : $this->images()->orderByDesc('is_primary')->first();

        return $primaryImage?->url;
    }
}
