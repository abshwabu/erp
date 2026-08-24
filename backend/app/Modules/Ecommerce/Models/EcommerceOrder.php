<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ecommerce_orders';

    protected $fillable = [
        'channel_id',
        'external_order_id',
        'order_number',
        'customer_name',
        'customer_email',
        'total_cents',
        'currency',
        'payment_status',
        'fulfillment_status',
        'items',
    ];

    protected $casts = [
        'total_cents' => 'integer',
        'items'       => 'array',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(EcommerceChannel::class, 'channel_id');
    }
}
