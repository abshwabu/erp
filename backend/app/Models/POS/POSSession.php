<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class POSSession extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'terminal_id',
        'cashier_id',
        'opened_at',
        'closed_at',
        'opening_cash_cents',
        'closing_cash_cents',
        'expected_cash_cents',
        'cash_variance_cents',
        'status',
        'z_report_data',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'z_report_data' => 'array',
    ];

    public function terminal()
    {
        return $this->belongsTo(POSTerminal::class, 'terminal_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
