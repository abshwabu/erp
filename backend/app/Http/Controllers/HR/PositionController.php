<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::with(['department'])->get();
        return response()->json($positions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'department_id' => 'required|uuid',
            'job_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'pay_grade_range' => 'nullable|string|max:100',
        ]);

        $position = Position::create($validated);
        return response()->json($position, 201);
    }

    public function show($id)
    {
        $position = Position::with(['department'])->findOrFail($id);
        return response()->json($position);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'department_id' => 'sometimes|required|uuid',
            'job_description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'pay_grade_range' => 'nullable|string|max:100',
        ]);

        $position->update($validated);
        return response()->json($position);
    }

    public function destroy($id)
    {
        $position = Position::findOrFail($id);
        $position->delete();
        return response()->json(null, 204);
    }
}
