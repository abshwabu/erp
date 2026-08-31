<?php

declare(strict_types=1);

namespace App\Modules\Projects\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectTask;
use App\Modules\Projects\Models\ProjectTimeLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectTimeLogController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectTimeLog::with([
            'project:id,code,name,status,color',
            'task:id,title,status,priority',
            'user:id,name,email',
        ]);

        if ($request->filled('project_id') && $request->input('project_id') !== 'all') {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('task_id') && $request->input('task_id') !== 'all') {
            $query->where('task_id', $request->input('task_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $logs = $query->orderByDesc('log_date')->orderByDesc('created_at')->get();

        return $this->successResponse($logs);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'task_id' => ['nullable', 'uuid', 'exists:project_tasks,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'hours' => ['required', 'numeric', 'min:0.1', 'max:24'],
            'log_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'is_billable' => ['nullable', 'boolean'],
        ]);

        $validated['user_id'] = $validated['user_id'] ?? $request->user()?->id;

        $log = DB::transaction(function () use ($validated) {
            $timeLog = ProjectTimeLog::create($validated);

            // If attached to a task, recalculate total logged hours on task
            if (!empty($validated['task_id'])) {
                $task = ProjectTask::find($validated['task_id']);
                if ($task) {
                    $total = (float) ProjectTimeLog::where('task_id', $task->id)->sum('hours');
                    $task->update(['logged_hours' => $total]);
                }
            }

            return $timeLog;
        });

        return $this->createdResponse($log->load(['project:id,code,name', 'task:id,title', 'user:id,name']));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $log = ProjectTimeLog::findOrFail($id);

        $validated = $request->validate([
            'hours' => ['sometimes', 'required', 'numeric', 'min:0.1', 'max:24'],
            'log_date' => ['sometimes', 'required', 'date'],
            'description' => ['nullable', 'string'],
            'is_billable' => ['nullable', 'boolean'],
        ]);

        $log = DB::transaction(function () use ($log, $validated) {
            $log->update($validated);

            if ($log->task_id) {
                $total = (float) ProjectTimeLog::where('task_id', $log->task_id)->sum('hours');
                $task = ProjectTask::find($log->task_id);
                $task?->update(['logged_hours' => $total]);
            }

            return $log;
        });

        return $this->successResponse($log->fresh(['project:id,code,name', 'task:id,title', 'user:id,name']));
    }

    public function destroy(string $id): JsonResponse
    {
        $log = ProjectTimeLog::findOrFail($id);
        $taskId = $log->task_id;

        DB::transaction(function () use ($log, $taskId) {
            $log->delete();

            if ($taskId) {
                $total = (float) ProjectTimeLog::where('task_id', $taskId)->sum('hours');
                $task = ProjectTask::find($taskId);
                $task?->update(['logged_hours' => $total]);
            }
        });

        return $this->successResponse(['message' => 'Time log removed successfully.']);
    }
}
