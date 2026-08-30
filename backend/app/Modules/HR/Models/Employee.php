<?php

declare(strict_types=1);

namespace App\Modules\HR\Models;

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
        'base_salary',
        'salary_currency',
        'salary_type',
        'payment_method',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'start_date',
        'probation_end_date',
        'contract_end_date',
        'work_location_id',
        'emergency_contacts',
        'custom_fields',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
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

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id')->orderBy('created_at', 'desc');
    }
}
