<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductCategory extends InventoryModel
{
    use SoftDeletes;

    protected $table = 'product_categories';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductCategory $category) {
            if (empty($category->slug)) {
                $category->slug = \Illuminate\Support\Str::slug($category->name);
            }
        });

        static::updating(function (ProductCategory $category) {
            if (empty($category->slug) && !empty($category->name)) {
                $category->slug = \Illuminate\Support\Str::slug($category->name);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
