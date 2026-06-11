<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Models;

use App\Traits\QueryFilter;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class InventoryModel extends Model
{
    use HasUuids;
    use QueryFilter;

    protected $guarded = [];
}
