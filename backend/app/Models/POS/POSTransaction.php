<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'session_id',
        'customer_id',
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

    public function items()
    {
        return $this->hasMany(POSTransactionItem::class, 'transaction_id');
    }

    public function payments()
    {
        return $this->hasMany(POSPayment::class, 'transaction_id');
    }
}
