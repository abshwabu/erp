<?php

declare(strict_types=1);

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Support\Models\SupportKnowledgeArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KnowledgeArticleController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = SupportKnowledgeArticle::with('author:id,name,email');

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('is_published')) {
            $query->where('is_published', filter_var($request->input('is_published'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search)
                  ->orWhere('summary', 'like', $search);
            });
        }

        $articles = $query->orderByDesc('created_at')->get();

        return $this->successResponse($articles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:general,billing,technical,account,faq'],
            'content' => ['required', 'string'],
            'summary' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $validated['author_id'] = $request->user()?->id;

        $article = SupportKnowledgeArticle::create($validated);

        return $this->createdResponse($article->load('author:id,name'));
    }

    public function show(string $idOrSlug): JsonResponse
    {
        $query = SupportKnowledgeArticle::with('author:id,name,email');

        if (Str::isUuid($idOrSlug)) {
            $query->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            });
        } else {
            $query->where('slug', $idOrSlug);
        }

        $article = $query->firstOrFail();

        // Increment views
        $article->increment('views_count');

        return $this->successResponse($article);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $article = SupportKnowledgeArticle::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'in:general,billing,technical,account,faq'],
            'content' => ['sometimes', 'required', 'string'],
            'summary' => ['nullable', 'string', 'max:500'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $article->update($validated);

        return $this->successResponse($article->fresh('author:id,name'));
    }

    public function destroy(string $id): JsonResponse
    {
        $article = SupportKnowledgeArticle::findOrFail($id);
        $article->delete();

        return $this->successResponse(['message' => 'Article deleted successfully.']);
    }
}
