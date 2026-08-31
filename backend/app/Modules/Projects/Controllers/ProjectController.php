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
    public function dashboardStats(): JsonResponse
    {
        $totalProjects = Project::count();
        $activeProjects = Project::whereIn('status', ['planned', 'in_progress'])->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $totalBudget = (float) Project::sum('budget');

        $totalTasks = ProjectTask::count();
        $doneTasks = ProjectTask::where('status', 'done')->count();
        $taskCompletionRate = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

        $totalLoggedHours = (float) ProjectTask::sum('logged_hours');
        $totalEstimatedHours = (float) ProjectTask::sum('estimated_hours');

        $statusBreakdown = [
            'planned' => Project::where('status', 'planned')->count(),
            'in_progress' => Project::where('status', 'in_progress')->count(),
            'on_hold' => Project::where('status', 'on_hold')->count(),
            'completed' => Project::where('status', 'completed')->count(),
            'cancelled' => Project::where('status', 'cancelled')->count(),
        ];

        $recentProjects = Project::with(['manager:id,name,email', 'customer:id,name,company'])
            ->withCount('tasks')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentTasks = ProjectTask::with(['project:id,code,name', 'assignee:id,name'])
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return $this->successResponse([
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'total_budget' => $totalBudget,
            'task_completion_rate' => $taskCompletionRate,
            'total_logged_hours' => $totalLoggedHours,
            'total_estimated_hours' => $totalEstimatedHours,
            'status_breakdown' => $statusBreakdown,
            'recent_projects' => $recentProjects,
            'recent_tasks' => $recentTasks,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Project::with(['manager:id,name,email', 'customer:id,name,company'])
            ->withCount('tasks');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->input('manager_id'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('code', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $projects = $query->orderByDesc('created_at')->get();

        return $this->successResponse($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:projects,code'],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'status' => ['nullable', 'string', 'in:planned,in_progress,on_hold,completed,cancelled'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'color' => ['nullable', 'string', 'max:30'],
        ]);

        $project = Project::create($validated);

        return $this->createdResponse($project->load(['manager:id,name,email', 'customer:id,name,company']));
    }

    public function show(string $id): JsonResponse
    {
        $project = Project::with([
            'manager:id,name,email',
            'customer:id,name,company',
            'milestones' => function ($q) {
                $q->orderBy('due_date');
            },
            'tasks' => function ($q) {
                $q->with(['assignee:id,name', 'milestone:id,title'])->orderBy('order')->orderBy('created_at');
            },
            'timeLogs' => function ($q) {
                $q->with(['user:id,name', 'task:id,title'])->orderByDesc('log_date')->limit(30);
            },
        ])
        ->withCount('tasks')
        ->findOrFail($id);

        return $this->successResponse($project);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:50', 'unique:projects,code,' . $id],
            'description' => ['nullable', 'string'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'status' => ['sometimes', 'required', 'string', 'in:planned,in_progress,on_hold,completed,cancelled'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string', 'max:30'],
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'completed' && empty($project->completed_at)) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] !== 'completed') {
                $validated['completed_at'] = null;
            }
        }

        $project->update($validated);

        return $this->successResponse($project->fresh(['manager:id,name,email', 'customer:id,name,company']));
    }

    public function destroy(string $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return $this->successResponse(['message' => 'Project deleted successfully.']);
    }
}
