<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\AttendanceLog;
use App\Models\HR\AttendanceSummary;
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
        $query = AttendanceLog::query();
        
        // Example filter
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        return response()->json($query->get());
    }

    public function summary()
    {
        $summary = AttendanceSummary::all();
        return response()->json($summary);
    }
}
