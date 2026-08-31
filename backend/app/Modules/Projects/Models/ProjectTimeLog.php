<?php

declare(strict_types=1);

namespace App\Modules\Projects\Models;

use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTimeLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'project_time_logs';

    protected $fillable = [
        'project_id',
        'task_id',
        'user_id',
        'hours',
        'log_date',
        'description',
        'is_billable',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'log_date' => 'date',
        'is_billable' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
