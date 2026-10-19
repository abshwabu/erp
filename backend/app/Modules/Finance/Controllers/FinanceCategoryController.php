<?php

declare(strict_types=1);

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceCategoryController extends Controller
{
    public function __construct(
        protected FinanceService $financeService
    ) {}

    /**
     * GET /api/finance/categories
     */
    public function index(Request $request): JsonResponse
    {
        $this->financeService->ensureDefaultCategories();

        $query = FinanceCategory::with('account')->orderBy('name');

        if ($type = $request->query('type')) {
            $query->where(function ($q) use ($type) {
                $q->where('type', $type)->orWhere('type', 'both');
            });
        }

        return response()->json($query->get());
    }

    /**
     * POST /api/finance/categories
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'type'        => 'required|in:income,expense,both',
            'color'       => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'account_id'  => 'nullable|uuid|exists:accounting_accounts,id',
        ]);

        $category = FinanceCategory::create($validated);
        return response()->json($category->load('account'), 201);
    }

    /**
     * PUT /api/finance/categories/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = FinanceCategory::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:100',
            'type'        => 'sometimes|required|in:income,expense,both',
            'color'       => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'account_id'  => 'nullable|uuid|exists:accounting_accounts,id',
        ]);

        $category->update($validated);
        return response()->json($category->load('account'));
    }

    /**
     * DELETE /api/finance/categories/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $category = FinanceCategory::findOrFail($id);

        if ($category->is_system) {
            return response()->json(['message' => 'Default system categories cannot be deleted.'], 403);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.']);
    }
}
