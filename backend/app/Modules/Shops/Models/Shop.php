<?php

declare(strict_types=1);

namespace App\Modules\Shops\Models;

use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\POS\Models\POSTerminal;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shop extends Model
{
    use HasUuids;

    protected $table = 'shops';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'address' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shop_user', 'shop_id', 'user_id')
            ->withPivot(['id', 'role'])
            ->withTimestamps();
    }

    public function keepers(): BelongsToMany
    {
        return $this->users();
    }

    public function terminals(): HasMany
    {
        return $this->hasMany(POSTerminal::class, 'shop_id');
    }
}
