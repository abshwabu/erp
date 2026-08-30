<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Employee;
use App\Modules\HR\Models\LeaveEntitlement;
use App\Modules\HR\Models\LeaveType;
use App\Modules\HR\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'position', 'manager']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('employee_number', 'ilike', "%{$search}%");
            });
        }

        if ($deptId = $request->query('department_id')) {
            $query->where('department_id', $deptId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('employment_type')) {
            $query->where('employment_type', $type);
        }

        $employees = $query->orderBy('first_name')->get();

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        foreach (['department_id', 'position_id', 'manager_id', 'date_of_birth', 'phone', 'gender'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }
        $request->merge($data);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:hr_employees,email',
            'employee_number' => 'nullable|string|max:50|unique:hr_employees,employee_number',
            'phone' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:50',
            'department_id' => 'nullable|uuid|exists:hr_departments,id',
            'position_id' => 'nullable|uuid|exists:hr_positions,id',
            'manager_id' => 'nullable|uuid|exists:hr_employees,id',
            'employment_type' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'start_date' => 'required|date',
            'emergency_contacts' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        if (empty($validated['employee_number'])) {
            $validated['employee_number'] = $this->generateEmployeeNumber();
        }

        $employee = DB::transaction(function () use ($validated) {
            $emp = Employee::create($validated);

            // Auto-create Leave Entitlements for the current year
            $leaveTypes = LeaveType::where('is_active', true)->get();
            if ($leaveTypes->isEmpty()) {
                $leaveTypes = $this->seedDefaultLeaveTypes();
            }

            $currentYear = Carbon::parse($validated['start_date'])->year;
            foreach ($leaveTypes as $type) {
                LeaveEntitlement::firstOrCreate([
                    'employee_id' => $emp->id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                ], [
                    'entitled_days' => $type->max_days_per_year,
                    'accrued_days' => $type->max_days_per_year,
                    'taken_days' => 0,
                    'carried_over_days' => 0,
                ]);
            }

            return $emp;
        });

        return response()->json($employee->load(['department', 'position', 'manager']), 201);
    }

    public function show($id)
    {
        $employee = Employee::with([
            'department',
            'position',
            'manager',
        ])->findOrFail($id);

        return response()->json($employee);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $data = $request->all();
        foreach (['department_id', 'position_id', 'manager_id', 'date_of_birth', 'phone', 'gender', 'preferred_name'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }
        $request->merge($data);

        $validated = $request->validate([
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('hr_employees', 'email')->ignore($employee->id)],
            'employee_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('hr_employees', 'employee_number')->ignore($employee->id)],
            'phone' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:50',
            'department_id' => 'nullable|uuid|exists:hr_departments,id',
            'position_id' => 'nullable|uuid|exists:hr_positions,id',
            'manager_id' => ['nullable', 'uuid', 'exists:hr_employees,id', Rule::notIn([$employee->id])],
            'employment_type' => 'sometimes|required|string|max:50',
            'status' => 'sometimes|required|string|max:50',
            'start_date' => 'sometimes|required|date',
            'emergency_contacts' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        $employee->update($validated);

        return response()->json($employee->fresh(['department', 'position', 'manager']));
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json(null, 204);
    }

    public function leaveBalances($id)
    {
        $currentYear = Carbon::now()->year;
        $balances = LeaveEntitlement::with('leaveType')
            ->where('employee_id', $id)
            ->where('year', $currentYear)
            ->get();

        if ($balances->isEmpty()) {
            $leaveTypes = LeaveType::where('is_active', true)->get();
            if ($leaveTypes->isEmpty()) {
                $leaveTypes = $this->seedDefaultLeaveTypes();
            }

            foreach ($leaveTypes as $type) {
                LeaveEntitlement::create([
                    'employee_id' => $id,
                    'leave_type_id' => $type->id,
                    'year' => $currentYear,
                    'entitled_days' => $type->max_days_per_year,
                    'accrued_days' => $type->max_days_per_year,
                    'taken_days' => 0,
                    'carried_over_days' => 0,
                ]);
            }

            $balances = LeaveEntitlement::with('leaveType')
                ->where('employee_id', $id)
                ->where('year', $currentYear)
                ->get();
        }

        return response()->json($balances);
    }

    public function attendance($id)
    {
        $attendance = AttendanceLog::where('employee_id', $id)
            ->orderBy('logged_at', 'desc')
            ->get();

        return response()->json($attendance);
    }

    public function resetPassword(Request $request, string $id)
    {
        $employee = Employee::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user = null;
        if ($employee->user_id) {
            $user = \App\Modules\Core\Models\User::find($employee->user_id);
        }

        if (!$user) {
            $user = \App\Modules\Core\Models\User::where('email', $employee->email)->first();
        }

        if (!$user) {
            // Create user login account for employee
            $user = \App\Modules\Core\Models\User::create([
                'name' => trim("{$employee->first_name} {$employee->last_name}"),
                'email' => $employee->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'is_active' => true,
            ]);

            $employee->user_id = $user->id;
            $employee->save();
        } else {
            $user->update([
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            ]);

            if (!$employee->user_id) {
                $employee->user_id = $user->id;
                $employee->save();
            }
        }

        return response()->json([
            'message' => "Password for {$employee->first_name} {$employee->last_name} ({$employee->email}) has been successfully updated.",
            'user_id' => $user->id,
            'email' => $user->email,
        ]);
    }

    private function generateEmployeeNumber(): string
    {
        $latest = Employee::withTrashed()
            ->where('employee_number', 'like', 'EMP-%')
            ->orderByDesc('employee_number')
            ->value('employee_number');

        $seq = 1;
        if ($latest && preg_match('/EMP-(\d+)/', $latest, $matches)) {
            $seq = (int) $matches[1] + 1;
        }

        return sprintf('EMP-%04d', $seq);
    }

    private function seedDefaultLeaveTypes()
    {
        $defaultTypes = [
            ['name' => 'Annual Leave', 'code' => 'ANNUAL', 'is_paid' => true, 'max_days_per_year' => 20, 'carry_over_days' => 5, 'requires_approval' => true, 'is_active' => true],
            ['name' => 'Sick Leave', 'code' => 'SICK', 'is_paid' => true, 'max_days_per_year' => 10, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
            ['name' => 'Casual / Personal Leave', 'code' => 'CASUAL', 'is_paid' => true, 'max_days_per_year' => 5, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
            ['name' => 'Maternity / Parental Leave', 'code' => 'PARENTAL', 'is_paid' => true, 'max_days_per_year' => 90, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
            ['name' => 'Unpaid Leave', 'code' => 'UNPAID', 'is_paid' => false, 'max_days_per_year' => 30, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
        ];

        $created = [];
        foreach ($defaultTypes as $dt) {
            $created[] = LeaveType::create($dt);
        }

        return collect($created);
    }
}
