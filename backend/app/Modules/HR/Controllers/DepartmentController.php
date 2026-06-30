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
        $departments = Department::with(['manager', 'parent'])->get();
        return response()->json($departments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'manager_id' => 'nullable|uuid',
            'parent_id' => 'nullable|uuid',
            'cost_center' => 'nullable|string|max:100',
            'headcount_budget' => 'nullable|integer|min:0',
        ]);

        $department = Department::create($validated);
        return response()->json($department, 201);
    }

    public function show($id)
    {
        $department = Department::with(['manager', 'parent', 'children'])->findOrFail($id);
        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'manager_id' => 'nullable|uuid',
            'parent_id' => 'nullable|uuid',
            'cost_center' => 'nullable|string|max:100',
            'headcount_budget' => 'nullable|integer|min:0',
        ]);

        $department->update($validated);
        return response()->json($department);
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        return response()->json(null, 204);
    }
}
