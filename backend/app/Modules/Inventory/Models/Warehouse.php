<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends InventoryModel
{
    protected $table = 'warehouses';

    protected $casts = [
        'address' => 'array',
        'is_active' => 'boolean',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(StockLocation::class, 'warehouse_id');
    }
}
