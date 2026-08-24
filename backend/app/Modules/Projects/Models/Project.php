<?php

declare(strict_types=1);

namespace App\Modules\Projects\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'projects';

    protected $fillable = [
        'code',
        'name',
        'description',
        'manager_id',
        'customer_id',
        'status',
        'budget_cents',
        'start_date',
        'due_date',
    ];

    protected $casts = [
        'budget_cents' => 'integer',
        'start_date'   => 'date',
        'due_date'     => 'date',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }

    public static function nextCode(): string
    {
        $last = static::query()->orderByDesc('code')->value('code');
        $seq  = $last ? ((int) str_replace('PRJ-', '', $last)) + 1 : 1;

        return 'PRJ-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
