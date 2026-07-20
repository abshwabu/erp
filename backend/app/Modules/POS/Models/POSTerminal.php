<?php

declare(strict_types=1);

namespace App\Modules\POS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class POSTerminal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pos_terminals';

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
