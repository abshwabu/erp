<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceSummary extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_attendance_summaries';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'date',
        'scheduled_hours',
        'actual_hours',
        'overtime_hours',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
