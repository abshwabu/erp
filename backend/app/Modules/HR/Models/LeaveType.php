<?php

declare(strict_types=1);

namespace App\Modules\HR\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LeaveType extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hr_leave_types';

    protected $fillable = [
        'name',
        'code',
        'is_paid',
        'max_days_per_year',
        'carry_over_days',
        'requires_approval',
        'requires_document',
        'is_active',
    ];
}
