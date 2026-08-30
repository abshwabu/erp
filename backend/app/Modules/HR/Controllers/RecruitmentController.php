<?php

declare(strict_types=1);

namespace App\Modules\HR\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\HR\Models\JobPosting;
use App\Modules\HR\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RecruitmentController extends Controller
{
    // ── Public Endpoints ─────────────────────────────────────────────────────────

    public function publicShow(string $idOrSlug): JsonResponse
    {
        $job = JobPosting::with(['department', 'position'])
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)
                  ->orWhere('slug', $idOrSlug);
            })
            ->where('status', '!=', 'draft')
            ->firstOrFail();

        // Increment view count
        $job->increment('views_count');

        return response()->json([
            'data' => $job,
            'company' => [
                'name' => config('app.name', 'ERP System'),
            ],
        ]);
    }

    public function publicSubmit(Request $request, string $idOrSlug): JsonResponse
    {
        $job = JobPosting::where(function ($q) use ($idOrSlug) {
            $q->where('id', $idOrSlug)
              ->orWhere('slug', $idOrSlug);
        })
        ->where('status', 'published')
        ->firstOrFail();

        $request->validate([
            'applicant_name' => ['required', 'string', 'max:255'],
            'applicant_email' => ['required', 'email', 'max:255'],
            'applicant_phone' => ['nullable', 'string', 'max:50'],
            'cover_letter' => ['nullable', 'string'],
            'resume' => ['nullable', 'file', 'max:20480'], // max 20MB
            'photo' => ['nullable', 'image', 'max:10240'], // max 10MB
        ]);

        $resumeUrl = null;
        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $path = $file->store("hr/recruitment/{$job->id}/resumes", 'public');
            $resumeUrl = Storage::disk('public')->url($path);
        }

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $path = $file->store("hr/recruitment/{$job->id}/photos", 'public');
            $photoUrl = Storage::disk('public')->url($path);
        }

        // Parse custom form responses
        $customResponses = [];
        $rawResponses = $request->input('custom_responses', []);
        if (is_string($rawResponses)) {
            $rawResponses = json_decode($rawResponses, true) ?: [];
        }

        foreach ($rawResponses as $key => $val) {
            $customResponses[$key] = $val;
        }

        // Handle any additional dynamic uploaded files in the custom schema
        foreach ($request->allFiles() as $fieldKey => $file) {
            if (in_array($fieldKey, ['resume', 'photo'])) {
                continue;
            }
            if (is_array($file)) {
                $urls = [];
                foreach ($file as $f) {
                    $p = $f->store("hr/recruitment/{$job->id}/custom_files", 'public');
                    $urls[] = Storage::disk('public')->url($p);
                }
                $customResponses[$fieldKey] = $urls;
            } else {
                $p = $file->store("hr/recruitment/{$job->id}/custom_files", 'public');
                $customResponses[$fieldKey] = Storage::disk('public')->url($p);
            }
        }

        $application = JobApplication::create([
            'job_posting_id' => $job->id,
            'applicant_name' => $request->input('applicant_name'),
            'applicant_email' => $request->input('applicant_email'),
            'applicant_phone' => $request->input('applicant_phone'),
            'resume_url' => $resumeUrl,
            'photo_url' => $photoUrl,
            'cover_letter' => $request->input('cover_letter'),
            'custom_form_responses' => $customResponses,
            'status' => 'new',
            'submitted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Thank you! Your application has been successfully submitted.',
            'application_id' => $application->id,
        ], 201);
    }

    // ── Internal Authenticated Endpoints ─────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = JobPosting::with(['department', 'position'])
            ->withCount('applications');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('location', 'like', $search);
            });
        }

        $jobs = $query->orderByDesc('created_at')->get();

        return response()->json($jobs);
    }

    public function stats(): JsonResponse
    {
        $activeJobsCount = JobPosting::where('status', 'published')->count();
        $totalApplications = JobApplication::count();
        $interviewingCount = JobApplication::whereIn('status', ['shortlisted', 'interviewing'])->count();
        $hiredCount = JobApplication::where('status', 'hired')->count();

        return response()->json([
            'active_jobs' => $activeJobsCount,
            'total_applications' => $totalApplications,
            'in_pipeline' => $interviewingCount,
            'hired_candidates' => $hiredCount,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'uuid', 'exists:hr_departments,id'],
            'position_id' => ['nullable', 'uuid', 'exists:hr_positions,id'],
            'location' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', 'max:50'],
            'experience_level' => ['nullable', 'string', 'max:50'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'max:10'],
            'description' => ['required', 'string'],
            'requirements' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:published,draft,closed'],
            'custom_form_schema' => ['nullable', 'array'],
        ]);

        $validated['created_by_user_id'] = $request->user()?->id;

        $job = JobPosting::create($validated);

        return response()->json($job->load(['department', 'position']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $job = JobPosting::with(['department', 'position', 'applications'])
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json($job);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $job = JobPosting::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'department_id' => ['nullable', 'uuid', 'exists:hr_departments,id'],
            'position_id' => ['nullable', 'uuid', 'exists:hr_positions,id'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'required', 'string', 'max:50'],
            'experience_level' => ['nullable', 'string', 'max:50'],
            'min_salary' => ['nullable', 'numeric', 'min:0'],
            'max_salary' => ['nullable', 'numeric', 'min:0'],
            'salary_currency' => ['nullable', 'string', 'max:10'],
            'description' => ['sometimes', 'required', 'string'],
            'requirements' => ['nullable', 'string'],
            'benefits' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', 'in:published,draft,closed'],
            'custom_form_schema' => ['nullable', 'array'],
        ]);

        $job->update($validated);

        return response()->json($job->fresh(['department', 'position']));
    }

    public function destroy(string $id): JsonResponse
    {
        $job = JobPosting::findOrFail($id);
        $job->delete();

        return response()->json(null, 204);
    }

    public function applications(Request $request, string $jobId): JsonResponse
    {
        $job = JobPosting::findOrFail($jobId);

        $query = JobApplication::where('job_posting_id', $job->id);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('applicant_name', 'like', $search)
                  ->orWhere('applicant_email', 'like', $search)
                  ->orWhere('applicant_phone', 'like', $search);
            });
        }

        $applications = $query->orderByDesc('submitted_at')->get();

        return response()->json($applications);
    }

    public function updateApplication(Request $request, string $jobId, string $applicationId): JsonResponse
    {
        $application = JobApplication::where('job_posting_id', $jobId)
            ->findOrFail($applicationId);

        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:new,reviewed,shortlisted,interviewing,offered,hired,rejected'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'notes' => ['nullable', 'string'],
        ]);

        $application->update($validated);

        return response()->json($application);
    }

    public function deleteApplication(string $jobId, string $applicationId): JsonResponse
    {
        $application = JobApplication::where('job_posting_id', $jobId)
            ->findOrFail($applicationId);

        $application->delete();

        return response()->json(null, 204);
    }
}
