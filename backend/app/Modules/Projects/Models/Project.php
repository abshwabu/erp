<?php

declare(strict_types=1);

namespace App\Modules\Projects\Models;

use App\Modules\Core\Models\User;
use App\Modules\Sales\Models\Customer;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'code',
        'name',
        'description',
        'manager_id',
        'customer_id',
        'status',
        'priority',
        'budget',
        'currency',
        'start_date',
        'due_date',
        'completed_at',
        'color',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'progress_percent',
        'total_logged_hours',
        'total_estimated_hours',
    ];

    public static function nextCode(): string
    {
        $count = static::withTrashed()->count() + 1;
        return sprintf('PRJ-%03d', $count);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = static::nextCode();
            }
        });
    }

    public function getProgressPercentAttribute(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $done = $this->tasks()->where('status', 'done')->count();
        return (int) round(($done / $total) * 100);
    }

    public function getTotalLoggedHoursAttribute(): float
    {
        return (float) $this->tasks()->sum('logged_hours');
    }

    public function getTotalEstimatedHoursAttribute(): float
    {
        return (float) $this->tasks()->sum('estimated_hours');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class, 'project_id');
    }
}
