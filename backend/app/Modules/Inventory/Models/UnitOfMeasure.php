<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Modules\Inventory\Enums\UomType;

class UnitOfMeasure extends InventoryModel
{
    protected $table = 'units_of_measure';

    protected $casts = [
        'type' => UomType::class,
        'is_base' => 'boolean',
    ];
}
