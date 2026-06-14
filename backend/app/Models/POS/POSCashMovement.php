<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class POSCashMovement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'session_id',
        'type',
        'amount_cents',
        'reason',
        'user_id',
        'created_at',
    ];

    public $timestamps = false; // Using created_at directly

    public function session()
    {
        return $this->belongsTo(POSSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
