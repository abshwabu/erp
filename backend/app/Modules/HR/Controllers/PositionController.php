<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::with(['department'])
            ->withCount('employees')
            ->orderBy('title')
            ->get();

        return response()->json($positions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|uuid|exists:hr_departments,id',
            'job_grade' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'min_salary_cents' => 'nullable|integer|min:0',
            'max_salary_cents' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $position = Position::create($validated);

        return response()->json($position->load('department'), 201);
    }

    public function show($id)
    {
        $position = Position::with(['department', 'employees'])->withCount('employees')->findOrFail($id);

        return response()->json($position);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'department_id' => 'sometimes|required|uuid|exists:hr_departments,id',
            'job_grade' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'min_salary_cents' => 'nullable|integer|min:0',
            'max_salary_cents' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $position->update($validated);

        return response()->json($position->load('department')->loadCount('employees'));
    }

    public function destroy($id)
    {
        $position = Position::withCount('employees')->findOrFail($id);

        if ($position->employees_count > 0) {
            return response()->json([
                'message' => "Cannot delete position \"{$position->title}\" because {$position->employees_count} employee(s) hold this position.",
            ], 422);
        }

        $position->delete();

        return response()->json(null, 204);
    }
}
