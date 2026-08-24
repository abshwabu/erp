<?php

declare(strict_types=1);

namespace App\Modules\Ecommerce\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Ecommerce\Models\Storefront;
use App\Modules\Ecommerce\Models\StorefrontPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StorefrontController extends BaseController
{
    public function index(): JsonResponse
    {
        $storefronts = Storefront::with('pages')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($storefronts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'slug'          => ['sometimes', 'string', 'max:100', 'unique:storefronts,slug'],
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'logo_url'      => ['nullable', 'string', 'max:500'],
            'theme_config'  => ['nullable', 'array'],
            'custom_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['name']);
        // Ensure slug is unique
        $originalSlug = $slug;
        $count = 1;
        while (Storefront::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $storefront = Storefront::create([
            'name'          => $data['name'],
            'slug'          => $slug,
            'title'         => $data['title'] ?? $data['name'],
            'description'   => $data['description'] ?? null,
            'logo_url'      => $data['logo_url'] ?? null,
            'theme_config'  => $data['theme_config'] ?? [
                'primary_color'  => '#4f46e5',
                'accent_color'   => '#06b6d4',
                'font_family'    => 'Inter',
                'banner_text'    => '✨ Welcome to our new online store! Free shipping worldwide.',
                'show_banner'    => true,
            ],
            'is_published'  => true,
            'custom_domain' => $data['custom_domain'] ?? null,
        ]);

        // Create default Home Page with starter drag-and-drop sections
        StorefrontPage::create([
            'storefront_id' => $storefront->id,
            'slug'          => 'home',
            'title'         => 'Home',
            'sections'      => StorefrontPage::defaultStarterSections($storefront->name),
            'is_published'  => true,
            'order'         => 0,
        ]);

        return $this->createdResponse($storefront->load('pages'));
    }

    public function show(string $id): JsonResponse
    {
        $storefront = Storefront::with(['pages' => fn ($q) => $q->orderBy('order')])
            ->findOrFail($id);

        return $this->successResponse($storefront);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $storefront = Storefront::findOrFail($id);

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'title'         => ['nullable', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'logo_url'      => ['nullable', 'string', 'max:500'],
            'theme_config'  => ['nullable', 'array'],
            'is_published'  => ['sometimes', 'boolean'],
            'custom_domain' => ['nullable', 'string', 'max:255'],
        ]);

        $storefront->update($data);

        return $this->successResponse($storefront->load('pages'));
    }

    public function updatePageSections(Request $request, string $storefrontId, string $pageId): JsonResponse
    {
        $page = StorefrontPage::where('storefront_id', $storefrontId)->findOrFail($pageId);

        $data = $request->validate([
            'title'        => ['sometimes', 'string', 'max:255'],
            'sections'     => ['required', 'array'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $page->update($data);

        return $this->successResponse($page);
    }

    public function destroy(string $id): JsonResponse
    {
        $storefront = Storefront::findOrFail($id);
        $storefront->delete();

        return $this->noContentResponse();
    }
}
