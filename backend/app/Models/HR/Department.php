<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Department extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'hr_departments';

    protected $fillable = [
        'parent_id',
        'name',
        'code',
        'head_employee_id',
        'cost_center_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    public function headEmployee()
    {
        return $this->belongsTo(Employee::class, 'head_employee_id');
    }
}
