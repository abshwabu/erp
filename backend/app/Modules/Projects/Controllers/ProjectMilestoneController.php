<?php

declare(strict_types=1);

namespace App\Modules\Projects\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Projects\Models\ProjectMilestone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectMilestoneController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectMilestone::with(['project:id,code,name,status,color'])
            ->withCount('tasks');

        if ($request->filled('project_id') && $request->input('project_id') !== 'all') {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $milestones = $query->orderBy('due_date')->orderByDesc('created_at')->get();

        return $this->successResponse($milestones);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,achieved,delayed'],
        ]);

        if (($validated['status'] ?? 'pending') === 'achieved') {
            $validated['completed_at'] = now();
        }

        $milestone = ProjectMilestone::create($validated);

        return $this->createdResponse($milestone->load('project:id,code,name'));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $milestone = ProjectMilestone::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:pending,in_progress,achieved,delayed'],
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'achieved' && empty($milestone->completed_at)) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== 'achieved') {
                $validated['completed_at'] = null;
            }
        }

        $milestone->update($validated);

        return $this->successResponse($milestone->fresh(['project:id,code,name']));
    }

    public function destroy(string $id): JsonResponse
    {
        $milestone = ProjectMilestone::findOrFail($id);
        $milestone->delete();

        return $this->successResponse(['message' => 'Milestone deleted successfully.']);
    }
}
