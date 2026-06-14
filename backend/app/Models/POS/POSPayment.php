<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'transaction_id',
        'method',
        'amount_cents',
        'reference',
        'change_cents',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public $timestamps = false;

    public function transaction()
    {
        return $this->belongsTo(POSTransaction::class, 'transaction_id');
    }
}
