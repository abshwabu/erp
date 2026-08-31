<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\CRM\Models\Deal;
use App\Modules\CRM\Models\Lead;
use App\Modules\Sales\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Lead::with(['customer', 'deal', 'assignedUser']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('priority') && $request->input('priority') !== 'all') {
            $query->where('priority', $request->input('priority'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('company', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        $leads = $query->orderByDesc('created_at')->get();

        return $this->successResponse($leads);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:new,contacted,qualified,unqualified,converted'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead = Lead::create($validated);

        return $this->createdResponse($lead->load(['assignedUser']));
    }

    public function show(string $id): JsonResponse
    {
        $lead = Lead::with(['customer', 'deal', 'assignedUser', 'activities.assignedUser'])
            ->findOrFail($id);

        return $this->successResponse($lead);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:new,contacted,qualified,unqualified,converted'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->update($validated);

        return $this->successResponse($lead->fresh(['customer', 'deal', 'assignedUser']));
    }

    public function destroy(string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();

        return $this->successResponse(null, 'Lead deleted successfully.');
    }

    public function convert(Request $request, string $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);

        $validated = $request->validate([
            'deal_title' => ['nullable', 'string', 'max:255'],
            'deal_amount' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'string', 'in:qualification,proposal,negotiation,won'],
        ]);

        $result = DB::transaction(function () use ($lead, $validated) {
            // 1. Create or resolve Customer
            $customer = Customer::create([
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'job_title' => $lead->title,
                'status' => 'customer',
                'source' => $lead->source,
                'notes' => "Converted from lead. Original notes: {$lead->notes}",
            ]);

            // 2. Create initial Deal
            $dealTitle = !empty($validated['deal_title'])
                ? $validated['deal_title']
                : ($lead->company ? "{$lead->company} - New Deal" : "{$lead->name} - Deal");

            $dealAmount = !empty($validated['deal_amount'])
                ? (float) $validated['deal_amount']
                : (float) ($lead->estimated_value ?? 0);

            $deal = Deal::create([
                'title' => $dealTitle,
                'customer_id' => $customer->id,
                'lead_id' => $lead->id,
                'stage' => $validated['stage'] ?? 'qualification',
                'amount' => $dealAmount,
                'currency' => $lead->currency ?? 'USD',
                'probability' => 30,
                'assigned_to_user_id' => $lead->assigned_to_user_id,
            ]);

            // 3. Mark Lead as Converted
            $lead->update([
                'status' => 'converted',
                'converted_customer_id' => $customer->id,
                'converted_deal_id' => $deal->id,
            ]);

            return [
                'lead' => $lead->fresh(['customer', 'deal']),
                'customer' => $customer,
                'deal' => $deal,
            ];
        });

        return $this->successResponse($result, 'Lead converted to Customer and Deal successfully.');
    }
}
