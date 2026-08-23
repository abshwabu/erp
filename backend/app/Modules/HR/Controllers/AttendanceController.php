<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\AttendanceLog;
use App\Modules\HR\Models\AttendanceSummary;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function clockIn(Request $request)
    {
        $log = AttendanceLog::create([
            'employee_id' => $request->user()->id, // Assuming authenticated user
            'clock_in' => now(),
        ]);
        return response()->json($log, 201);
    }

    public function clockOut(Request $request)
    {
        $log = AttendanceLog::where('employee_id', $request->user()->id)
            ->whereNull('clock_out')
            ->latest()
            ->firstOrFail();
            
        $log->update(['clock_out' => now()]);

        // Logic to trigger summary calculation would go here
        
        return response()->json($log);
    }

    public function index(Request $request)
    {
        $query = AttendanceLog::with('employee.department');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $request->input('department_id')));
        }

        if ($request->filled('clock_type')) {
            $query->where('clock_type', $request->input('clock_type'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('logged_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('logged_at', '<=', $request->input('end_date'));
        }

        return response()->json($query->orderBy('logged_at', 'desc')->get());
    }

    public function summary()
    {
        $summary = AttendanceSummary::all();
        return response()->json($summary);
    }
}
