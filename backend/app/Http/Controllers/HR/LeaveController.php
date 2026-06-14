<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\LeaveType;
use App\Models\HR\LeaveRequest;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function getTypes()
    {
        return response()->json(LeaveType::all());
    }

    public function getRequests()
    {
        return response()->json(LeaveRequest::all());
    }

    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $leaveRequest = LeaveRequest::create($validated);
        return response()->json($leaveRequest, 201);
    }

    public function approve($id)
    {
        $request = LeaveRequest::findOrFail($id);
        $request->update(['status' => 'approved']);
        return response()->json($request);
    }

    public function reject($id)
    {
        $request = LeaveRequest::findOrFail($id);
        $request->update(['status' => 'rejected']);
        return response()->json($request);
    }

    public function cancel($id)
    {
        $request = LeaveRequest::findOrFail($id);
        $request->update(['status' => 'cancelled']);
        return response()->json($request);
    }
}
