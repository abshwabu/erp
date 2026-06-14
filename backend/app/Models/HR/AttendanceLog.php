<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AttendanceLog extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_attendance_logs';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'clock_type',
        'logged_at',
        'method',
        'location_coords',
        'notes',
        'created_by_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
