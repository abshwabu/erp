<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSTransactionItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_transaction_items';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
        'lot_number',
        'serial_number',
    ];

    public $timestamps = false;

    public function transaction()
    {
        return $this->belongsTo(POSTransaction::class, 'transaction_id');
    }
}
