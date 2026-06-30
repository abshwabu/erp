<?php

declare(strict_types=1);

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WorkSchedule extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hr_work_schedules';

    protected $fillable = [
        'name',
        'type',
        'days_of_week',
        'start_time',
        'end_time',
        'hours_per_day',
        'is_active',
    ];

    protected $casts = [
        'days_of_week' => 'array',
    ];
}
