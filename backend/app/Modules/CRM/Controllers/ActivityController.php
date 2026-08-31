<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\CRM\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['customer', 'lead', 'deal', 'assignedUser', 'creator']);

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->input('deal_id'));
        }

        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->input('lead_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $activities = $query->orderBy('due_date')->orderByDesc('created_at')->get();

        return $this->successResponse($activities);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:call,meeting,email,task,follow_up,note'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,completed,cancelled'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'lead_id' => ['nullable', 'uuid', 'exists:crm_leads,id'],
            'deal_id' => ['nullable', 'uuid', 'exists:crm_deals,id'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
        ]);

        $validated['created_by_user_id'] = $request->user()?->id;

        $activity = Activity::create($validated);

        return $this->createdResponse($activity->load(['customer', 'deal', 'assignedUser']));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);

        $validated = $request->validate([
            'type' => ['sometimes', 'required', 'string', 'in:call,meeting,email,task,follow_up,note'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:pending,completed,cancelled'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'lead_id' => ['nullable', 'uuid', 'exists:crm_leads,id'],
            'deal_id' => ['nullable', 'uuid', 'exists:crm_deals,id'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'completed' && empty($activity->completed_at)) {
                $validated['completed_at'] = now();
            } elseif ($validated['status'] === 'pending') {
                $validated['completed_at'] = null;
            }
        }

        $activity->update($validated);

        return $this->successResponse($activity->fresh(['customer', 'deal', 'assignedUser']));
    }

    public function toggleComplete(string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);

        $newStatus = $activity->status === 'completed' ? 'pending' : 'completed';
        $completedAt = $newStatus === 'completed' ? now() : null;

        $activity->update([
            'status' => $newStatus,
            'completed_at' => $completedAt,
        ]);

        return $this->successResponse($activity->fresh(['customer', 'deal', 'assignedUser']));
    }

    public function destroy(string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $activity->delete();

        return $this->successResponse(['message' => 'Activity deleted successfully.']);
    }
}
