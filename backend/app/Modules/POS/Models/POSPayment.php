<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_payments';

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
