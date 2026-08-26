<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Models;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductVariant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'purchase_order_lines';

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'variant_id',
        'description',
        'quantity',
        'unit_cost_cents',
        'received_quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'unit_cost_cents' => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
