<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends InventoryModel
{
    protected $table = 'product_images';

    protected $guarded = [];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (! $this->path) {
            return null;
        }

        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://') || str_starts_with($this->path, 'data:image')) {
            return $this->path;
        }

        if (config('filesystems.default') === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->path, now()->addMinutes(60));
        }

        return Storage::disk('public')->url($this->path);
    }
}
