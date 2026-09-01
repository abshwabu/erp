<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ReorderSetting;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Warehouse;
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
        $locationId = $request->input('location_id') ?: $request->input('locationId');
        $search     = trim((string) $request->input('search'));
        $lowStock   = $request->boolean('low_stock') || $request->boolean('lowStockOnly');

        $query = Product::query()
            ->with(['category', 'stockLevels' => function ($q) use ($locationId) {
                if ($locationId) {
                    $q->where('location_id', $locationId);
                }
            }, 'stockLevels.location']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        if ($lowStock) {
            $lowStockProductIds = ReorderSetting::all()
                ->filter(function ($setting) use ($locationId) {
                    $available = $this->stockService->getAvailableQty($setting->product_id, $locationId ?: $setting->location_id);
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
                    'variant_id' => $level->variant_id,
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
            'product_id'  => 'required|uuid|exists:products,id',
            'location_id' => 'required|uuid|exists:stock_locations,id',
            'quantity'    => 'required|integer',
            'type'        => 'nullable|string|in:add,remove,set',
            'reason'      => 'nullable|string|max:100',
            'notes'       => 'nullable|string|max:500',
            'variant_id'  => 'nullable|uuid|exists:product_variants,id',
        ]);

        $type = $validated['type'] ?? null;
        $qty  = (int) $validated['quantity'];

        try {
            if ($type === 'add') {
                $movement = $this->stockService->receiveStock(
                    $validated['product_id'],
                    $validated['location_id'],
                    abs($qty),
                    0,
                    ['type' => $validated['reason'] ?? 'manual_adjustment'],
                    null,
                    null,
                    null,
                    $validated['variant_id'] ?? null
                );
            } elseif ($type === 'remove') {
                $movement = $this->stockService->issueStock(
                    $validated['product_id'],
                    $validated['location_id'],
                    abs($qty),
                    ['type' => $validated['reason'] ?? 'manual_adjustment', 'movement_type' => 'adjustment'],
                    null,
                    null,
                    $validated['variant_id'] ?? null
                );
            } else {
                $movement = $this->stockService->adjustStock(
                    $validated['product_id'],
                    $validated['location_id'],
                    $qty,
                    $validated['reason'] ?? null,
                    $validated['notes'] ?? null,
                    $validated['variant_id'] ?? null
                );
            }
        } catch (\App\Modules\Inventory\Exceptions\InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }

        \App\Modules\Integrations\Services\IntegrationDispatcherService::dispatch('inventory.adjusted', [
            'product_id'  => $movement->product_id,
            'quantity'    => $movement->quantity,
            'type'        => $movement->type,
            'location_id' => $movement->to_location_id ?? $movement->from_location_id,
            'notes'       => $movement->notes,
            'adjusted_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'Stock adjusted successfully.',
            'data' => [
                'id'               => $movement->id,
                'product_id'       => $movement->product_id,
                'variant_id'       => $movement->variant_id,
                'from_location_id' => $movement->from_location_id,
                'to_location_id'   => $movement->to_location_id,
                'quantity'         => $movement->quantity,
                'type'             => $movement->type,
                'reference_type'   => $movement->reference_type,
                'notes'            => $movement->notes,
                'created_at'       => $movement->created_at?->toIso8601String(),
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

        $productId = $request->input('product_id', $request->input('productId'));
        if (! empty($productId)) {
            $query->where('product_id', $productId);
        }

        $locationId = $request->input('location_id', $request->input('locationId'));
        if (! empty($locationId)) {
            $query->where(function ($q) use ($locationId) {
                $q->where('from_location_id', $locationId)
                  ->orWhere('to_location_id', $locationId);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $startDate = $request->input('start_date', $request->input('startDate'));
        if (! empty($startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        $endDate = $request->input('end_date', $request->input('endDate'));
        if (! empty($endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($search = trim((string) $request->input('search', ''))) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->input('per_page', 25);
        $paginator = $query->paginate($perPage);

        $items = collect($paginator->items())->map(function (StockMovement $movement) {
            $direction = 'in';
            if ($movement->from_location_id && ! $movement->to_location_id) {
                $direction = 'out';
            } elseif ($movement->from_location_id && $movement->to_location_id) {
                $direction = 'transfer';
            }

            return [
                'id' => $movement->id,
                'product_id' => $movement->product_id,
                'product_name' => $movement->product?->name,
                'product_sku' => $movement->product?->sku,
                'variant_id' => $movement->variant_id,
                'type' => $movement->type,
                'direction' => $direction,
                'quantity' => (int) $movement->quantity,
                'from_location_id' => $movement->from_location_id,
                'from_location_name' => $movement->fromLocation?->name,
                'to_location_id' => $movement->to_location_id,
                'to_location_name' => $movement->toLocation?->name,
                'reference_type' => $movement->reference_type,
                'reference_id' => $movement->reference_id,
                'unit_cost' => (int) $movement->unit_cost,
                'notes' => $movement->notes,
                'user_id' => $movement->user_id,
                'user_name' => $movement->user?->name,
                'created_at' => $movement->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $items,
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
            ],
        ]);
    }

    /**
     * GET /api/inventory/stock/low
     * List products at or below their reorder limits.
     */
    public function low(Request $request): JsonResponse
    {
        $locationId = $request->query('location_id');
        $warehouseId = $request->query('warehouse_id');

        $locationsQuery = StockLocation::query()->where('is_active', '!=', false)->with('warehouse');
        if ($locationId) {
            $locationsQuery->where('id', $locationId);
        }
        if ($warehouseId) {
            $locationsQuery->where('warehouse_id', $warehouseId);
        }
        $locations = $locationsQuery->orderBy('name')->get();

        $settings = ReorderSetting::with(['product.stockLevels', 'location'])->get();
        $products = Product::where('status', 'active')->with(['stockLevels', 'variants'])->get();

        $lowStockItems = [];

        // 1. Process explicit reorder settings
        foreach ($settings as $setting) {
            $statusVal = $setting->product?->status instanceof \BackedEnum
                ? $setting->product->status->value
                : ($setting->product?->status ?? '');

            if (! $setting->product || $statusVal !== 'active') {
                continue;
            }

            // If setting is tied to a specific location
            if ($setting->location_id) {
                $loc = $setting->location ?? $locations->first(fn ($l) => (string) $l->id === (string) $setting->location_id);
                if (! $loc) {
                    continue;
                }

                $available = (int) $this->stockService->getAvailableQty($setting->product, $loc);
                if ($available <= (int) $setting->min_quantity) {
                    $key = $setting->product_id . '_' . $loc->id;
                    $lowStockItems[$key] = [
                        'product_id' => $setting->product->id,
                        'product_name' => $setting->product->name,
                        'sku' => $setting->product->sku,
                        'warehouse_id' => $loc->warehouse_id,
                        'warehouse_name' => $loc->warehouse?->name ?? $loc->name,
                        'location_id' => $loc->id,
                        'location_name' => $loc->name,
                        'min_quantity' => (int) $setting->min_quantity,
                        'max_quantity' => $setting->max_quantity ? (int) $setting->max_quantity : null,
                        'reorder_quantity' => $setting->reorder_quantity ? (int) $setting->reorder_quantity : null,
                        'available_quantity' => $available,
                    ];
                }
            } else {
                // Global setting applied to each warehouse/location separately
                foreach ($locations as $loc) {
                    $available = (int) $this->stockService->getAvailableQty($setting->product, $loc);
                    if ($available <= (int) $setting->min_quantity) {
                        $key = $setting->product_id . '_' . $loc->id;
                        $lowStockItems[$key] = [
                            'product_id' => $setting->product->id,
                            'product_name' => $setting->product->name,
                            'sku' => $setting->product->sku,
                            'warehouse_id' => $loc->warehouse_id,
                            'warehouse_name' => $loc->warehouse?->name ?? $loc->name,
                            'location_id' => $loc->id,
                            'location_name' => $loc->name,
                            'min_quantity' => (int) $setting->min_quantity,
                            'max_quantity' => $setting->max_quantity ? (int) $setting->max_quantity : null,
                            'reorder_quantity' => $setting->reorder_quantity ? (int) $setting->reorder_quantity : null,
                            'available_quantity' => $available,
                        ];
                    }
                }
            }
        }

        // 2. Evaluate products and variants per warehouse/location for out-of-stock / zero-stock items
        foreach ($products as $product) {
            foreach ($locations as $loc) {
                if ($product->has_variants && $product->relationLoaded('variants') && $product->variants->isNotEmpty()) {
                    foreach ($product->variants as $variant) {
                        $vKey = $product->id . '_' . $variant->id . '_' . $loc->id;
                        if (isset($lowStockItems[$vKey])) {
                            continue;
                        }

                        $sl = $product->stockLevels
                            ->where('location_id', $loc->id)
                            ->where('variant_id', $variant->id)
                            ->first();

                        $vAvailable = $sl ? max(0, (int) $sl->quantity_on_hand - (int) $sl->quantity_committed) : 0;

                        if ($vAvailable <= 0) {
                            $lowStockItems[$vKey] = [
                                'product_id' => $product->id,
                                'product_name' => $product->name . ' (' . $variant->name . ')',
                                'variant_id' => $variant->id,
                                'variant_name' => $variant->name,
                                'sku' => $variant->sku ?: $product->sku,
                                'warehouse_id' => $loc->warehouse_id,
                                'warehouse_name' => $loc->warehouse?->name ?? $loc->name,
                                'location_id' => $loc->id,
                                'location_name' => $loc->name,
                                'min_quantity' => 0,
                                'max_quantity' => null,
                                'reorder_quantity' => null,
                                'available_quantity' => $vAvailable,
                            ];
                        }
                    }
                } else {
                    $key = $product->id . '_' . $loc->id;
                    if (isset($lowStockItems[$key])) {
                        continue;
                    }

                    $locLevels = $product->stockLevels->where('location_id', $loc->id);
                    $onHand = (int) $locLevels->sum('quantity_on_hand');
                    $committed = (int) $locLevels->sum('quantity_committed');
                    $available = max(0, $onHand - $committed);

                    if ($available <= 0) {
                        $lowStockItems[$key] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'variant_id' => null,
                            'variant_name' => null,
                            'sku' => $product->sku,
                            'warehouse_id' => $loc->warehouse_id,
                            'warehouse_name' => $loc->warehouse?->name ?? $loc->name,
                            'location_id' => $loc->id,
                            'location_name' => $loc->name,
                            'min_quantity' => 0,
                            'max_quantity' => null,
                            'reorder_quantity' => null,
                            'available_quantity' => $available,
                        ];
                    }
                }
            }
        }

        return response()->json([
            'data' => array_values($lowStockItems),
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
