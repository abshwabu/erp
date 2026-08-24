<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'integrations';

    protected $fillable = [
        'provider',
        'name',
        'status',
        'api_key',
        'webhook_url',
        'settings',
        'last_tested_at',
    ];

    protected $casts = [
        'settings'       => 'array',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class, 'integration_id');
    }
}
