<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Requests\StoreCategoryRequest;
use App\Modules\Inventory\Resources\ProductCategoryResource;
use Illuminate\Http\JsonResponse;

class ProductCategoryController extends BaseController
{
    /**
     * GET /api/inventory/categories
     * List all categories (filterable via QueryFilter).
     */
    public function index(): JsonResponse
    {
        $query = ProductCategory::filter()->with('children');

        return $this->paginatedResponse($query, ProductCategoryResource::class)->response();
    }

    /**
     * GET /api/inventory/categories/tree
     * Return the full tree of root categories (with nested children).
     */
    public function tree(): JsonResponse
    {
        $roots = ProductCategory::whereNull('parent_id')
            ->with('children.children') // 3 levels deep by default
            ->get();

        return $this->successResponse(ProductCategoryResource::collection($roots));
    }

    /**
     * POST /api/inventory/categories
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = ProductCategory::create($request->validated());

        return $this->createdResponse(new ProductCategoryResource($category));
    }

    /**
     * GET /api/inventory/categories/{category}
     */
    public function show(ProductCategory $category): JsonResponse
    {
        $category->load('children', 'parent');

        return $this->successResponse(new ProductCategoryResource($category));
    }

    /**
     * PUT /api/inventory/categories/{category}
     */
    public function update(StoreCategoryRequest $request, ProductCategory $category): JsonResponse
    {
        $category->update($request->validated());

        return $this->successResponse(new ProductCategoryResource($category->fresh('children')));
    }

    /**
     * DELETE /api/inventory/categories/{category}
     */
    public function destroy(ProductCategory $category): JsonResponse
    {
        $category->delete();

        return $this->noContentResponse();
    }
}
