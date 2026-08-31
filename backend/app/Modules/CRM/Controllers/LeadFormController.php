<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\CRM\Models\Lead;
use App\Modules\CRM\Models\LeadForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeadFormController extends BaseController
{
    // ── Public Endpoints (Accessible by external leads & website embeds) ────────

    public function publicShow(string $idOrSlug): JsonResponse
    {
        $query = LeadForm::query();

        if (Str::isUuid($idOrSlug)) {
            $query->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            });
        } else {
            $query->where('slug', $idOrSlug);
        }

        $form = $query->where('is_active', true)->firstOrFail();

        // Increment views count
        $form->increment('views_count');

        return response()->json([
            'data' => $form,
            'company' => [
                'name' => config('app.name', 'ERP System'),
            ],
        ]);
    }

    public function publicSubmit(Request $request, string $idOrSlug): JsonResponse
    {
        $query = LeadForm::query();

        if (Str::isUuid($idOrSlug)) {
            $query->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            });
        } else {
            $query->where('slug', $idOrSlug);
        }

        $form = $query->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'custom_responses' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);

        $customResponses = $validated['custom_responses'] ?? [];

        // Also check raw string JSON if submitted from multipart/form-data
        if (is_string($customResponses)) {
            $customResponses = json_decode($customResponses, true) ?: [];
        }

        $lead = Lead::create([
            'lead_form_id' => $form->id,
            'name' => $validated['name'],
            'company' => $validated['company'] ?? null,
            'title' => $validated['title'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'source' => $form->source ?: 'website',
            'status' => 'new',
            'priority' => $form->default_priority ?: 'medium',
            'estimated_value' => $form->default_estimated_value,
            'assigned_to_user_id' => $form->assigned_to_user_id,
            'notes' => $validated['notes'] ?? null,
            'custom_form_responses' => $customResponses,
        ]);

        // Increment submissions count
        $form->increment('submissions_count');

        return response()->json([
            'message' => $form->thank_you_message ?: 'Thank you! Your inquiry has been received. Our team will get in touch shortly.',
            'thank_you_title' => $form->thank_you_title ?: 'Thank You!',
            'redirect_url' => $form->redirect_url,
            'lead_id' => $lead->id,
        ], 201);
    }

    // ── Authenticated Internal Endpoints ─────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = LeadForm::with(['assignedUser'])
            ->withCount('leads');

        if ($request->filled('source') && $request->input('source') !== 'all') {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('form_type') && $request->input('form_type') !== 'all') {
            $query->where('form_type', $request->input('form_type'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('headline', 'like', $search);
            });
        }

        $forms = $query->orderByDesc('created_at')->get();

        return $this->successResponse($forms);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:50'],
            'form_type' => ['required', 'string', 'in:wizard,classic_embed'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'custom_questions' => ['nullable', 'array'],
            'thank_you_title' => ['nullable', 'string', 'max:255'],
            'thank_you_message' => ['nullable', 'string'],
            'redirect_url' => ['nullable', 'url', 'max:255'],
            'default_priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'default_estimated_value' => ['nullable', 'numeric', 'min:0'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
            'is_active' => ['nullable', 'boolean'],
            'theme_color' => ['nullable', 'string', 'max:30'],
        ]);

        $validated['created_by_user_id'] = $request->user()?->id;

        $form = LeadForm::create($validated);

        return $this->createdResponse($form->load(['assignedUser']));
    }

    public function show(string $id): JsonResponse
    {
        $form = LeadForm::with([
            'assignedUser',
            'leads' => function ($q) {
                $q->orderByDesc('created_at')->limit(20);
            },
        ])
        ->withCount('leads')
        ->findOrFail($id);

        return $this->successResponse($form);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $form = LeadForm::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'source' => ['sometimes', 'required', 'string', 'max:50'],
            'form_type' => ['sometimes', 'required', 'string', 'in:wizard,classic_embed'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'custom_questions' => ['nullable', 'array'],
            'thank_you_title' => ['nullable', 'string', 'max:255'],
            'thank_you_message' => ['nullable', 'string'],
            'redirect_url' => ['nullable', 'url', 'max:255'],
            'default_priority' => ['nullable', 'string', 'in:low,medium,high,urgent'],
            'default_estimated_value' => ['nullable', 'numeric', 'min:0'],
            'assigned_to_user_id' => ['nullable', 'uuid'],
            'is_active' => ['nullable', 'boolean'],
            'theme_color' => ['nullable', 'string', 'max:30'],
        ]);

        $form->update($validated);

        return $this->successResponse($form->fresh(['assignedUser']));
    }

    public function destroy(string $id): JsonResponse
    {
        $form = LeadForm::findOrFail($id);
        $form->delete();

        return $this->successResponse(null, 'Lead form deleted successfully.');
    }
}
