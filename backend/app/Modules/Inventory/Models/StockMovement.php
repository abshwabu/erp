<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StockMovement extends InventoryModel
{
    protected $table = 'stock_movements';

    public const UPDATED_AT = null;

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'integer',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
    ];

    /**
     * Generate a UUID v7 for the primary key.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid7();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
