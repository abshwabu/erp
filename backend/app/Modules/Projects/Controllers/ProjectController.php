<?php

declare(strict_types=1);

namespace App\Modules\Projects\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends BaseController
{
    public function index(): JsonResponse
    {
        $projects = Project::with('manager:id,name,email')
            ->withCount('tasks')
            ->orderByDesc('created_at')
            ->paginate(25);

        return $this->successResponse($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'manager_id'   => ['nullable', 'uuid', 'exists:users,id'],
            'customer_id'  => ['nullable', 'uuid'],
            'status'       => ['sometimes', 'in:planned,in_progress,on_hold,completed,cancelled'],
            'budget_cents' => ['sometimes', 'integer', 'min:0'],
            'start_date'   => ['nullable', 'date'],
            'due_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project = Project::create([
            'code'         => Project::nextCode(),
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'manager_id'   => $data['manager_id'] ?? null,
            'customer_id'  => $data['customer_id'] ?? null,
            'status'       => $data['status'] ?? 'planned',
            'budget_cents' => $data['budget_cents'] ?? 0,
            'start_date'   => $data['start_date'] ?? null,
            'due_date'     => $data['due_date'] ?? null,
        ]);

        return $this->createdResponse($project->load('manager:id,name'));
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with(['manager:id,name,email', 'tasks.assignee:id,name,email'])
            ->findOrFail($id);

        return $this->successResponse($project);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $data = $request->validate([
            'name'         => ['sometimes', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'manager_id'   => ['nullable', 'uuid', 'exists:users,id'],
            'status'       => ['sometimes', 'in:planned,in_progress,on_hold,completed,cancelled'],
            'budget_cents' => ['sometimes', 'integer', 'min:0'],
            'start_date'   => ['nullable', 'date'],
            'due_date'     => ['nullable', 'date'],
        ]);

        $project->update($data);

        return $this->successResponse($project);
    }

    public function addTask(Request $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $data = $request->validate([
            'title'           => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'assigned_to'     => ['nullable', 'uuid', 'exists:users,id'],
            'status'          => ['sometimes', 'in:todo,in_progress,review,done'],
            'priority'        => ['sometimes', 'in:low,normal,high,urgent'],
            'due_date'        => ['nullable', 'date'],
            'estimated_hours' => ['sometimes', 'integer', 'min:0'],
        ]);

        $task = ProjectTask::create([
            'project_id'      => $project->id,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'assigned_to'     => $data['assigned_to'] ?? null,
            'status'          => $data['status'] ?? 'todo',
            'priority'        => $data['priority'] ?? 'normal',
            'due_date'        => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? 0,
            'logged_hours'    => 0,
        ]);

        return $this->createdResponse($task->load('assignee:id,name'));
    }

    public function updateTask(Request $request, string $projectId, string $taskId): JsonResponse
    {
        $task = ProjectTask::where('project_id', $projectId)->findOrFail($taskId);

        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'status'       => ['sometimes', 'in:todo,in_progress,review,done'],
            'priority'     => ['sometimes', 'in:low,normal,high,urgent'],
            'logged_hours' => ['sometimes', 'integer', 'min:0'],
        ]);

        $task->update($data);

        return $this->successResponse($task);
    }
}
