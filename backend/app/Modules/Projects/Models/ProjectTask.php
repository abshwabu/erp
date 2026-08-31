<?php

declare(strict_types=1);

namespace App\Modules\Projects\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'project_tasks';

    protected $fillable = [
        'project_id',
        'milestone_id',
        'title',
        'description',
        'assigned_to_user_id',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'logged_hours',
        'order',
        'completed_at',
        'created_by_user_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'estimated_hours' => 'decimal:2',
        'logged_hours' => 'decimal:2',
        'order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProjectTimeLog::class, 'task_id');
    }
}
