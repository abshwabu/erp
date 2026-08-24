<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'integration_logs';

    protected $fillable = [
        'integration_id',
        'event',
        'direction',
        'status_code',
        'payload',
        'response',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'payload'     => 'array',
        'response'    => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class, 'integration_id');
    }
}
