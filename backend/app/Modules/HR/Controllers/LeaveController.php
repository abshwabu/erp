<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\LeaveEntitlement;
use App\Modules\HR\Models\LeaveType;
use App\Modules\HR\Models\LeaveRequest;
use App\Modules\HR\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(protected LeaveService $leaveService)
    {
    }

    public function types()
    {
        $types = LeaveType::all();
        if ($types->isEmpty()) {
            $defaultTypes = [
                ['name' => 'Annual Leave', 'code' => 'ANNUAL', 'is_paid' => true, 'max_days_per_year' => 20, 'carry_over_days' => 5, 'requires_approval' => true, 'is_active' => true],
                ['name' => 'Sick Leave', 'code' => 'SICK', 'is_paid' => true, 'max_days_per_year' => 10, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
                ['name' => 'Casual / Personal Leave', 'code' => 'CASUAL', 'is_paid' => true, 'max_days_per_year' => 5, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
                ['name' => 'Maternity / Parental Leave', 'code' => 'PARENTAL', 'is_paid' => true, 'max_days_per_year' => 90, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
                ['name' => 'Unpaid Leave', 'code' => 'UNPAID', 'is_paid' => false, 'max_days_per_year' => 30, 'carry_over_days' => 0, 'requires_approval' => true, 'is_active' => true],
            ];
            foreach ($defaultTypes as $dt) {
                LeaveType::create($dt);
            }
            $types = LeaveType::all();
        }

        return response()->json($types);
    }

    public function index(Request $request)
    {
        $query = LeaveRequest::with(['employee.department', 'leaveType', 'approver']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->input('department_id')));
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'leave_type_id' => 'required|exists:hr_leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'half_day' => 'nullable|boolean',
            'half_day_period' => 'nullable|string|in:morning,afternoon',
        ]);

        $days = $this->leaveService->calculateWorkingDays($validated['start_date'], $validated['end_date']);
        if (! empty($validated['half_day'])) {
            $days = 0.5;
        }

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'days_taken' => $days,
            'half_day' => $validated['half_day'] ?? false,
            'half_day_period' => $validated['half_day_period'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json($leaveRequest->load(['employee.department', 'leaveType']), 201);
    }

    public function approve(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $leaveRequest->update([
            'status' => 'approved',
            'approver_notes' => $request->input('notes'),
            'decided_at' => now(),
        ]);

        $year = Carbon::parse($leaveRequest->start_date)->year;
        $entitlement = LeaveEntitlement::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($entitlement) {
            $entitlement->taken_days = (float) $entitlement->taken_days + (float) $leaveRequest->days_taken;
            $entitlement->save();
        }

        return response()->json($leaveRequest->load(['employee.department', 'leaveType']));
    }

    public function reject(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $leaveRequest->update([
            'status' => 'rejected',
            'approver_notes' => $request->input('notes'),
            'decided_at' => now(),
        ]);

        return response()->json($leaveRequest->load(['employee.department', 'leaveType']));
    }

    public function cancel($id)
    {
        $leaveRequest = LeaveRequest::findOrFail($id);
        $leaveRequest->update(['status' => 'cancelled']);

        return response()->json($leaveRequest->load(['employee.department', 'leaveType']));
    }
}
