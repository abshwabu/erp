<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EcommerceChannel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ecommerce_channels';

    protected $fillable = [
        'name',
        'platform',
        'store_url',
        'api_key',
        'api_secret',
        'webhook_secret',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'api_secret',
        'webhook_secret',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(EcommerceOrder::class, 'channel_id');
    }
}
