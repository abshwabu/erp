<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_transactions';

    protected $fillable = [
        'session_id',
        'customer_id',
        'customer_tin',
        'customer_name',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
        'currency_code',
        'status',
        'receipt_number',
        'synced_at',
        'offline_uuid',
        'notes',
        'created_at',
    ];

    public $timestamps = false; // Using created_at

    public function session()
    {
        return $this->belongsTo(POSSession::class, 'session_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Modules\Sales\Models\Customer::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(POSTransactionItem::class, 'transaction_id');
    }

    public function payments()
    {
        return $this->hasMany(POSPayment::class, 'transaction_id');
    }
}
