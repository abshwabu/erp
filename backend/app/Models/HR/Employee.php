<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'hr_employees';

    protected $fillable = [
        'employee_number',
        'user_id',
        'department_id',
        'position_id',
        'manager_id',
        'first_name',
        'last_name',
        'preferred_name',
        'email',
        'phone',
        'date_of_birth',
        'national_id_encrypted',
        'gender',
        'employment_type',
        'status',
        'start_date',
        'probation_end_date',
        'contract_end_date',
        'work_location_id',
        'emergency_contacts',
        'custom_fields',
    ];

    protected $casts = [
        'emergency_contacts' => 'array',
        'custom_fields' => 'array',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }
}
