<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ReorderSetting;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Resources\ProductResource;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockController extends BaseController
{
    public function __construct(protected StockService $stockService)
    {
    }

    /**
     * GET /api/inventory/stock
     * List all products with their stock levels, filterable.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::filter()
            ->with(['category', 'stockLevels.location']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $lowStockProductIds = ReorderSetting::all()
                ->filter(function ($setting) {
                    $available = $this->stockService->getAvailableQty($setting->product_id, $setting->location_id);
                    return $available <= $setting->min_quantity;
                })
                ->pluck('product_id')
                ->unique()
                ->toArray();

            $query->whereIn('id', $lowStockProductIds);
        }

        return $this->paginatedResponse($query, ProductResource::class)->response();
    }

    /**
     * GET /api/inventory/stock/{productId}
     * Get stock levels per location for a single product.
     */
    public function show(string $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $levels = StockLevel::where('product_id', $product->id)
            ->with('location')
            ->get()
            ->map(function ($level) {
                return [
                    'location_id' => $level->location_id,
                    'location_name' => $level->location->name ?? null,
                    'location_code' => $level->location->code ?? null,
                    'quantity_on_hand' => $level->quantity_on_hand,
                    'quantity_committed' => $level->quantity_committed,
                    'quantity_on_order' => $level->quantity_on_order,
                    'available_quantity' => $level->quantity_on_hand - $level->quantity_committed,
                ];
            });

        return response()->json([
            'data' => $levels,
        ]);
    }

    /**
     * POST /api/inventory/stock/adjustments
     * Perform a manual stock adjustment.
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'location_id' => 'required|uuid|exists:stock_locations,id',
            'quantity' => 'required|integer',
            'reason' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'variant_id' => 'nullable|uuid|exists:product_variants,id',
        ]);

        $movement = $this->stockService->adjustStock(
            $validated['product_id'],
            $validated['location_id'],
            $validated['quantity'],
            $validated['reason'] ?? null,
            $validated['notes'] ?? null,
            $validated['variant_id'] ?? null
        );

        return response()->json([
            'message' => 'Stock adjusted successfully.',
            'data' => [
                'id' => $movement->id,
                'product_id' => $movement->product_id,
                'variant_id' => $movement->variant_id,
                'from_location_id' => $movement->from_location_id,
                'to_location_id' => $movement->to_location_id,
                'quantity' => $movement->quantity,
                'type' => $movement->type,
                'reference_type' => $movement->reference_type,
                'notes' => $movement->notes,
                'created_at' => $movement->created_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * GET /api/inventory/stock/movements
     * List historical stock movements, filterable.
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::query()->with(['product', 'fromLocation', 'toLocation', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('location_id')) {
            $locationId = $request->input('location_id');
            $query->where(function ($q) use ($locationId) {
                $q->where('from_location_id', $locationId)
                  ->orWhere('to_location_id', $locationId);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Sort by created_at descending (latest movements first)
        $query->orderBy('created_at', 'desc');

        // Since paginatedResponse returns PaginatedCollection, we can use it or wrap it
        // To be safe, we can manually paginate and return a paginated collection response
        $perPage = (int) $request->input('per_page', 25);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }

    /**
     * GET /api/inventory/stock/low
     * List products at or below their reorder limits.
     */
    public function low(): JsonResponse
    {
        $settings = ReorderSetting::with(['product.stockLevels', 'location'])->get();
        $lowStock = [];

        foreach ($settings as $setting) {
            if (!$setting->product) {
                continue;
            }

            $available = $this->stockService->getAvailableQty($setting->product, $setting->location);

            if ($available <= $setting->min_quantity) {
                $lowStock[] = [
                    'product_id' => $setting->product->id,
                    'product_name' => $setting->product->name,
                    'sku' => $setting->product->sku,
                    'location_id' => $setting->location_id,
                    'location_name' => $setting->location->name ?? 'All Locations',
                    'min_quantity' => $setting->min_quantity,
                    'max_quantity' => $setting->max_quantity,
                    'reorder_quantity' => $setting->reorder_quantity,
                    'available_quantity' => $available,
                ];
            }
        }

        return response()->json([
            'data' => $lowStock,
        ]);
    }

    /**
     * GET /api/inventory/stock/valuation
     * Get stock valuation grouped by product/category.
     */
    public function valuation(): JsonResponse
    {
        $products = Product::with(['stockLevels', 'category'])->get();
        $valuationByProduct = [];
        $valuationByCategory = [];
        $totalValuation = 0;

        foreach ($products as $product) {
            $onHand = $product->stockLevels->sum('quantity_on_hand');
            $valuation = $onHand * $product->cost_price;
            $totalValuation += $valuation;

            $valuationByProduct[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity_on_hand' => $onHand,
                'cost_price' => $product->cost_price,
                'valuation' => $valuation,
            ];

            if ($product->category) {
                $catId = $product->category->id;
                if (!isset($valuationByCategory[$catId])) {
                    $valuationByCategory[$catId] = [
                        'category_id' => $catId,
                        'name' => $product->category->name,
                        'quantity_on_hand' => 0,
                        'valuation' => 0,
                    ];
                }
                $valuationByCategory[$catId]['quantity_on_hand'] += $onHand;
                $valuationByCategory[$catId]['valuation'] += $valuation;
            }
        }

        return response()->json([
            'data' => [
                'products' => $valuationByProduct,
                'categories' => array_values($valuationByCategory),
                'total_valuation' => $totalValuation,
            ],
        ]);
    }
}
