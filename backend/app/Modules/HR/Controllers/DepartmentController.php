<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['manager', 'parent'])
            ->withCount('employees')
            ->orderBy('name')
            ->get();

        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'head_employee_id' => 'nullable|uuid|exists:hr_employees,id',
            'manager_id' => 'nullable|uuid|exists:hr_employees,id',
            'parent_id' => 'nullable|uuid|exists:hr_departments,id',
            'code' => 'nullable|string|max:50',
            'cost_center_id' => 'nullable|string|max:100',
        ]);

        if (isset($validated['manager_id']) && ! isset($validated['head_employee_id'])) {
            $validated['head_employee_id'] = $validated['manager_id'];
        }
        unset($validated['manager_id']);

        $department = Department::create($validated);

        return response()->json($department->load(['manager', 'parent']), 201);
    }

    public function show($id)
    {
        $department = Department::with(['manager', 'parent', 'children', 'employees.position'])
            ->withCount('employees')
            ->findOrFail($id);

        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'head_employee_id' => 'nullable|uuid|exists:hr_employees,id',
            'manager_id' => 'nullable|uuid|exists:hr_employees,id',
            'parent_id' => 'nullable|uuid|exists:hr_departments,id',
            'code' => 'nullable|string|max:50',
            'cost_center_id' => 'nullable|string|max:100',
        ]);

        if (isset($validated['manager_id']) && ! isset($validated['head_employee_id'])) {
            $validated['head_employee_id'] = $validated['manager_id'];
        }
        unset($validated['manager_id']);

        $department->update($validated);

        return response()->json($department->load(['manager', 'parent'])->loadCount('employees'));
    }

    public function destroy($id)
    {
        $department = Department::withCount('employees')->findOrFail($id);

        if ($department->employees_count > 0) {
            return response()->json([
                'message' => "Cannot delete department \"{$department->name}\" because it has {$department->employees_count} employee(s) assigned.",
            ], 422);
        }

        $department->delete();

        return response()->json(null, 204);
    }
}
