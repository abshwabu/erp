<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'work_orders';

    protected $fillable = [
        'number',
        'bom_id',
        'quantity',
        'status',
        'priority',
        'planned_start',
        'planned_end',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_end'   => 'date',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Generate the next sequential work order number.
     */
    public static function nextNumber(): string
    {
        $last = static::query()->orderByDesc('number')->value('number');
        $seq  = $last ? ((int) str_replace('WO-', '', $last)) + 1 : 1;

        return 'WO-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
