<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\CRM\Models\Deal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Deal::with(['customer', 'lead', 'assignedUser']);

        if ($request->filled('stage') && $request->input('stage') !== 'all') {
            $query->where('stage', $request->input('stage'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', $search)->orWhere('company', 'like', $search);
                  });
            });
        }

        $deals = $query->orderByDesc('created_at')->get();

        return $this->successResponse($deals);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'lead_id' => ['nullable', 'uuid', 'exists:crm_leads,id'],
            'stage' => ['nullable', 'string', 'in:qualification,proposal,negotiation,won,lost'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
        ]);

        $deal = Deal::create($validated);

        return $this->createdResponse($deal->load(['customer', 'assignedUser']));
    }

    public function show(string $id): JsonResponse
    {
        $deal = Deal::with(['customer', 'lead', 'assignedUser', 'activities.assignedUser'])
            ->findOrFail($id);

        return $this->successResponse($deal);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $deal = Deal::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'lead_id' => ['nullable', 'uuid', 'exists:crm_leads,id'],
            'stage' => ['sometimes', 'required', 'string', 'in:qualification,proposal,negotiation,won,lost'],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'actual_close_date' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
            'lost_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if (isset($validated['stage'])) {
            if ($validated['stage'] === 'won' && empty($deal->actual_close_date)) {
                $validated['actual_close_date'] = now()->toDateString();
                $validated['probability'] = 100;
            } elseif ($validated['stage'] === 'lost') {
                $validated['probability'] = 0;
            }
        }

        $deal->update($validated);

        return $this->successResponse($deal->fresh(['customer', 'lead', 'assignedUser']));
    }

    public function updateStage(Request $request, string $id): JsonResponse
    {
        $deal = Deal::findOrFail($id);

        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:qualification,proposal,negotiation,won,lost'],
            'lost_reason' => ['nullable', 'string'],
        ]);

        $updateData = ['stage' => $validated['stage']];
        if ($validated['stage'] === 'won') {
            $updateData['actual_close_date'] = now()->toDateString();
            $updateData['probability'] = 100;
        } elseif ($validated['stage'] === 'lost') {
            $updateData['lost_reason'] = $validated['lost_reason'] ?? null;
            $updateData['probability'] = 0;
        }

        $deal->update($updateData);

        return $this->successResponse($deal->fresh(['customer', 'assignedUser']));
    }

    public function destroy(string $id): JsonResponse
    {
        $deal = Deal::findOrFail($id);
        $deal->delete();

        return $this->successResponse(['message' => 'Deal deleted successfully.']);
    }
}
