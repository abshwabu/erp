<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\LeaveEntitlement;
use App\Models\HR\AttendanceLog;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(Employee::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:employees,email',
        ]);

        $employee = Employee::create($validated);
        return response()->json($employee, 201);
    }

    public function show($id)
    {
        return response()->json(Employee::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update($request->all());
        return response()->json($employee);
    }

    public function destroy($id)
    {
        Employee::destroy($id);
        return response()->json(null, 204);
    }

    public function leaveBalances($id)
    {
        $balances = LeaveEntitlement::where('employee_id', $id)->get();
        return response()->json($balances);
    }

    public function attendance($id)
    {
        $attendance = AttendanceLog::where('employee_id', $id)->get();
        return response()->json($attendance);
    }
}
