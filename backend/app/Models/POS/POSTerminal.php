<?php

namespace App\Models\POS;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSTerminal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'location_id',
        'receipt_printer_settings',
        'cash_drawer_settings',
        'is_active',
    ];

    protected $casts = [
        'receipt_printer_settings' => 'array',
        'cash_drawer_settings' => 'array',
        'is_active' => 'boolean',
    ];
}
