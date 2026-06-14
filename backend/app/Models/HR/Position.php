<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Position extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_positions';

    protected $fillable = [
        'department_id',
        'title',
        'job_grade',
        'min_salary_cents',
        'max_salary_cents',
        'description',
        'is_active',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
