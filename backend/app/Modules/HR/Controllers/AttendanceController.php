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
    public function myStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['is_linked' => false, 'status' => 'not_clocked_in'], 200);
        }

        $employee = Employee::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->first();

        if (!$employee) {
            return response()->json([
                'is_linked' => false,
                'employee_id' => null,
                'status' => 'not_clocked_in',
            ], 200);
        }

        $today = Carbon::today()->toDateString();
        $logs = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('logged_at', $today)
            ->orderBy('logged_at', 'asc')
            ->get();

        $latestLog = $logs->last();
        $clockIn = $logs->where('clock_type', 'in')->first();
        $clockOut = $logs->where('clock_type', 'out')->last();

        $status = 'not_clocked_in';
        if ($latestLog) {
            if ($latestLog->clock_type === 'in') {
                $status = 'clocked_in';
            } elseif ($latestLog->clock_type === 'out') {
                $status = 'clocked_out';
            }
        }

        return response()->json([
            'is_linked' => true,
            'employee_id' => $employee->id,
            'employee_name' => "{$employee->first_name} {$employee->last_name}",
            'status' => $status,
            'clock_in_time' => $clockIn?->logged_at ? Carbon::parse($clockIn->logged_at)->toIso8601String() : null,
            'clock_out_time' => $clockOut?->logged_at ? Carbon::parse($clockOut->logged_at)->toIso8601String() : null,
            'latest_action_at' => $latestLog?->logged_at ? Carbon::parse($latestLog->logged_at)->toIso8601String() : null,
        ]);
    }

    public function clockIn(Request $request)
    {
        $employeeId = $request->input('employee_id');
        if (! $employeeId) {
            $user = $request->user();
            $employee = Employee::where('user_id', $user?->id)
                ->orWhere('email', $user?->email)
                ->first();

            if (!$employee && $user) {
                $names = explode(' ', $user->name, 2);
                $employee = Employee::create([
                    'user_id' => $user->id,
                    'first_name' => $names[0] ?? 'User',
                    'last_name' => $names[1] ?? '',
                    'email' => $user->email,
                    'employee_number' => 'EMP-' . rand(1000, 9999),
                    'employment_type' => 'full-time',
                    'status' => 'active',
                    'start_date' => now()->toDateString(),
                ]);
            }
            $employeeId = $employee?->id;
        }

        if (! $employeeId) {
            return response()->json(['message' => 'Employee record could not be resolved.'], 422);
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
            $user = $request->user();
            $employee = Employee::where('user_id', $user?->id)
                ->orWhere('email', $user?->email)
                ->first();

            $employeeId = $employee?->id;
        }

        if (! $employeeId) {
            return response()->json(['message' => 'Employee record could not be resolved.'], 422);
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
