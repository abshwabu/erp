<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\AttendanceLog;
use App\Modules\HR\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $employeeId = $request->input('employee_id');
        if (! $employeeId) {
            $employee = Employee::where('email', $request->user()->email)->first();
            $employeeId = $employee?->id;
        }

        if (! $employeeId) {
            return response()->json(['message' => 'Employee ID is required.'], 422);
        }

        $log = AttendanceLog::create([
            'employee_id' => $employeeId,
            'clock_type' => 'in',
            'logged_at' => $request->input('logged_at', now()),
            'method' => $request->input('method', 'web'),
            'location_coords' => $request->input('location'),
            'notes' => $request->input('notes'),
            'created_by_id' => $request->user()?->id,
        ]);

        return response()->json($log->load('employee.department'), 201);
    }

    public function clockOut(Request $request)
    {
        $employeeId = $request->input('employee_id');
        if (! $employeeId) {
            $employee = Employee::where('email', $request->user()->email)->first();
            $employeeId = $employee?->id;
        }

        if (! $employeeId) {
            return response()->json(['message' => 'Employee ID is required.'], 422);
        }

        $log = AttendanceLog::create([
            'employee_id' => $employeeId,
            'clock_type' => 'out',
            'logged_at' => $request->input('logged_at', now()),
            'method' => $request->input('method', 'web'),
            'location_coords' => $request->input('location'),
            'notes' => $request->input('notes'),
            'created_by_id' => $request->user()?->id,
        ]);

        return response()->json($log->load('employee.department'), 201);
    }

    public function index(Request $request)
    {
        $query = AttendanceLog::with(['employee.department', 'employee.position']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->input('department_id')));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('logged_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('logged_at', '<=', $request->input('end_date'));
        }

        $logs = $query->orderBy('logged_at', 'desc')->get();

        // Group by employee and date to build paired attendance records
        $grouped = [];
        foreach ($logs as $log) {
            $date = Carbon::parse($log->logged_at)->toDateString();
            $key = "{$log->employee_id}_{$date}";

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'id' => $log->id,
                    'employee_id' => $log->employee_id,
                    'employee' => $log->employee,
                    'date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                    'status' => 'present',
                    'late_minutes' => 0,
                    'total_hours' => 0.0,
                    'method' => $log->method,
                    'notes' => $log->notes,
                ];
            }

            if ($log->clock_type === 'in' && ! $grouped[$key]['clock_in']) {
                $grouped[$key]['clock_in'] = $log->logged_at;
                // Standard 9:00 AM shift start check
                $clockInTime = Carbon::parse($log->logged_at);
                $expectedStart = Carbon::parse($date . ' 09:00:00');
                if ($clockInTime->greaterThan($expectedStart)) {
                    $grouped[$key]['late_minutes'] = $expectedStart->diffInMinutes($clockInTime);
                    $grouped[$key]['status'] = 'late';
                }
            } elseif ($log->clock_type === 'out') {
                $grouped[$key]['clock_out'] = $log->logged_at;
            }

            if ($grouped[$key]['clock_in'] && $grouped[$key]['clock_out']) {
                $in = Carbon::parse($grouped[$key]['clock_in']);
                $out = Carbon::parse($grouped[$key]['clock_out']);
                $grouped[$key]['total_hours'] = round($in->diffInMinutes($out) / 60, 2);
            }
        }

        return response()->json(array_values($grouped));
    }

    public function summary()
    {
        $today = Carbon::today()->toDateString();
        $totalEmployees = Employee::where('status', 'active')->count();

        $presentTodayCount = AttendanceLog::whereDate('logged_at', $today)
            ->where('clock_type', 'in')
            ->distinct('employee_id')
            ->count('employee_id');

        $onLeaveTodayCount = Employee::where('status', 'on-leave')->count();
        $absentTodayCount = max(0, $totalEmployees - $presentTodayCount - $onLeaveTodayCount);

        return response()->json([
            'total_employees' => $totalEmployees,
            'present_today' => $presentTodayCount,
            'on_leave_today' => $onLeaveTodayCount,
            'absent_today' => $absentTodayCount,
        ]);
    }
}
