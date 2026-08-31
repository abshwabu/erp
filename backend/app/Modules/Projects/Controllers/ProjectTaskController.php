<?php

declare(strict_types=1);

namespace App\Modules\Projects\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTaskController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectTask::with([
            'project:id,code,name,status,color',
            'assignee:id,name,email',
            'milestone:id,title',
        ]);

        if ($request->filled('project_id') && $request->input('project_id') !== 'all') {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_user_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $tasks = $query->orderBy('order')->orderByDesc('created_at')->get();

        return $this->successResponse($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'milestone_id' => ['nullable', 'uuid', 'exists:project_milestones,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'status' => ['nullable', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'order' => ['nullable', 'integer'],
        ]);

        $validated['created_by_user_id'] = $request->user()?->id;
        if (($validated['status'] ?? 'todo') === 'done') {
            $validated['completed_at'] = now();
        }

        $task = ProjectTask::create($validated);

        return $this->createdResponse($task->load(['project:id,code,name', 'assignee:id,name,email', 'milestone:id,title']));
    }

    public function show(string $id): JsonResponse
    {
        $task = ProjectTask::with([
            'project:id,code,name,status,color',
            'assignee:id,name,email',
            'milestone:id,title',
            'timeLogs.user:id,name',
        ])->findOrFail($id);

        return $this->successResponse($task);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = ProjectTask::findOrFail($id);

        $validated = $request->validate([
            'project_id' => ['sometimes', 'required', 'uuid', 'exists:projects,id'],
            'milestone_id' => ['nullable', 'uuid', 'exists:project_milestones,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'status' => ['sometimes', 'required', 'string', 'in:todo,in_progress,review,done'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'due_date' => ['nullable', 'date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'logged_hours' => ['nullable', 'numeric', 'min:0'],
            'order' => ['nullable', 'integer'],
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'done' && empty($task->completed_at)) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== 'done') {
                $validated['completed_at'] = null;
            }
        }

        $task->update($validated);

        return $this->successResponse($task->fresh(['project:id,code,name', 'assignee:id,name,email', 'milestone:id,title']));
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $task = ProjectTask::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:todo,in_progress,review,done'],
        ]);

        $status = $validated['status'];
        $completedAt = $status === 'done' ? now() : null;

        $task->update([
            'status' => $status,
            'completed_at' => $completedAt,
        ]);

        return $this->successResponse($task->fresh(['project:id,code,name', 'assignee:id,name,email', 'milestone:id,title']));
    }

    public function destroy(string $id): JsonResponse
    {
        $task = ProjectTask::findOrFail($id);
        $task->delete();

        return $this->successResponse(['message' => 'Task deleted successfully.']);
    }
}
