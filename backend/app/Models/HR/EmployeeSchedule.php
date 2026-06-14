<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $table = 'hr_employee_schedules';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'schedule_id',
        'effective_from',
        'effective_to',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function schedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'schedule_id');
    }
}
