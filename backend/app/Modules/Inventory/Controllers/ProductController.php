<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Jobs\ImportProductsJob;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductBarcode;
use App\Modules\Inventory\Models\ProductVariant;
use App\Modules\Inventory\Requests\StoreProductRequest;
use App\Modules\Inventory\Requests\UpdateProductRequest;
use App\Modules\Inventory\Resources\ProductBarcodeResource;
use App\Modules\Inventory\Resources\ProductResource;
use App\Modules\Inventory\Resources\ProductVariantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    /**
     * GET /api/inventory/products
     */
    public function index(): JsonResponse
    {
        $query = Product::filter()
            ->with(['category', 'barcodes', 'variants.stockLevels', 'stockLevels']);

        return $this->paginatedResponse($query, ProductResource::class)->response();
    }

    /**
     * POST /api/inventory/products
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $barcodes  = $validated['barcodes'] ?? [];
            $variants  = $validated['variants'] ?? [];
            unset($validated['barcodes'], $validated['variants']);

            $product = Product::create($validated);

            foreach ($barcodes as $barcode) {
                $product->barcodes()->create($barcode);
            }

            // Create initial stock level record for default main warehouse if none exists
            $warehouse = \App\Modules\Inventory\Models\Warehouse::firstOrCreate(
                ['code' => 'WH-MAIN'],
                ['name' => 'Main Warehouse', 'type' => 'own', 'is_active' => true]
            );

            $location = \App\Modules\Inventory\Models\StockLocation::firstOrCreate(
                ['code' => 'WH-MAIN'],
                [
                    'warehouse_id' => $warehouse->id,
                    'name' => 'Main Warehouse',
                    'type' => 'storage',
                    'is_active' => true
                ]
            );

            \App\Modules\Inventory\Models\StockLevel::firstOrCreate([
                'product_id' => $product->id,
                'location_id' => $location->id,
            ], [
                'quantity_on_hand' => 0,
                'quantity_committed' => 0,
                'quantity_on_order' => 0,
            ]);

            // Create variants
            foreach ($variants as $variant) {
                $createdVariant = $product->variants()->create([
                    'sku' => $variant['sku'],
                    'name' => $variant['name'],
                    'cost_price' => $variant['cost_price'] ?? 0,
                    'selling_price' => $variant['selling_price'],
                    'attribute_value_ids' => $variant['attribute_value_ids'] ?? [],
                    'is_active' => $variant['is_active'] ?? true,
                ]);

                // Create initial stock level record for variant if stock is set
                if (isset($variant['stock']) && $variant['stock'] > 0) {
                    \App\Modules\Inventory\Models\StockLevel::firstOrCreate([
                        'product_id' => $product->id,
                        'variant_id' => $createdVariant->id,
                        'location_id' => $location->id,
                    ], [
                        'quantity_on_hand' => $variant['stock'],
                        'quantity_committed' => 0,
                        'quantity_on_order' => 0,
                    ]);

                    \App\Modules\Inventory\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'variant_id' => $createdVariant->id,
                        'to_location_id' => $location->id,
                        'quantity' => $variant['stock'],
                        'type' => 'opening',
                        'reference_type' => 'InitialStock',
                        'unit_cost' => (int) ($variant['cost_price'] ?? 0),
                        'user_id' => auth()->id() ?? \App\Models\User::first()?->id,
                    ]);
                }
            }

            return $product;
        });

        $product->load(['category', 'barcodes', 'variants.stockLevels', 'stockLevels']);

        return $this->createdResponse(new ProductResource($product));
    }

    /**
     * GET /api/inventory/products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'barcodes', 'variants.stockLevels', 'images', 'uoms']);

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * PUT /api/inventory/products/{product}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        DB::transaction(function () use ($request, $product) {
            $validated = $request->validated();
            $variants  = $validated['variants'] ?? null;
            unset($validated['variants']);

            $product->update($validated);

            if ($variants !== null) {
                $incomingVariantIds = [];

                foreach ($variants as $variant) {
                    $variantData = [
                        'sku' => $variant['sku'],
                        'name' => $variant['name'],
                        'cost_price' => $variant['cost_price'] ?? 0,
                        'selling_price' => $variant['selling_price'],
                        'attribute_value_ids' => $variant['attribute_value_ids'] ?? [],
                        'is_active' => $variant['is_active'] ?? true,
                    ];

                    if (isset($variant['id']) && !empty($variant['id'])) {
                        $existingVariant = $product->variants()->findOrFail($variant['id']);
                        $existingVariant->update($variantData);
                        $incomingVariantIds[] = $existingVariant->id;
                        $targetVariant = $existingVariant;
                    } else {
                        $newVariant = $product->variants()->create($variantData);
                        $incomingVariantIds[] = $newVariant->id;
                        $targetVariant = $newVariant;
                    }

                    // Handle variant stock update if specified
                    if (isset($variant['stock'])) {
                        $warehouse = \App\Modules\Inventory\Models\Warehouse::firstOrCreate(
                            ['code' => 'WH-MAIN'],
                            ['name' => 'Main Warehouse', 'type' => 'own', 'is_active' => true]
                        );

                        $location = \App\Modules\Inventory\Models\StockLocation::firstOrCreate(
                            ['code' => 'WH-MAIN'],
                            [
                                'warehouse_id' => $warehouse->id,
                                'name' => 'Main Warehouse',
                                'type' => 'storage',
                                'is_active' => true
                            ]
                        );

                        $stockLevel = \App\Modules\Inventory\Models\StockLevel::firstOrCreate([
                            'product_id' => $product->id,
                            'variant_id' => $targetVariant->id,
                            'location_id' => $location->id,
                        ], [
                            'quantity_on_hand' => 0,
                            'quantity_committed' => 0,
                            'quantity_on_order' => 0,
                        ]);

                        $diff = $variant['stock'] - $stockLevel->quantity_on_hand;
                        if ($diff != 0) {
                            $stockService = app(\App\Modules\Inventory\Services\StockService::class);
                            if ($diff > 0) {
                                $stockService->receiveStock($product->id, $location->id, $diff, 0, ['type' => 'correction'], null, null, null, $targetVariant->id);
                            } else {
                                $stockService->issueStock($product->id, $location->id, abs($diff), ['type' => 'correction', 'movement_type' => 'adjustment'], null, null, $targetVariant->id);
                            }
                        }
                    }
                }

                // Delete variants that were removed in the frontend payload
                $product->variants()->whereNotIn('id', $incomingVariantIds)->delete();
            }
        });

        $product->load(['category', 'barcodes', 'variants.stockLevels']);

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * DELETE /api/inventory/products/{product}
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->noContentResponse();
    }

    // ── Variants ──────────────────────────────────────────────────────────────

    /**
     * GET /api/inventory/products/{product}/variants
     */
    public function variants(Product $product): JsonResponse
    {
        return $this->successResponse(
            ProductVariantResource::collection($product->variants)
        );
    }

    // ── Stock ─────────────────────────────────────────────────────────────────

    /**
     * GET /api/inventory/products/{product}/stock
     * Aggregate stock levels across locations for a product.
     */
    public function stock(Product $product): JsonResponse
    {
        $levels = $product->stockLevels()->with('location')->get();

        $locations = $levels->map(function ($level) {
            return [
                'location_id'         => $level->location_id,
                'location_name'       => $level->location->name ?? null,
                'location_code'       => $level->location->code ?? null,
                'variant_id'          => $level->variant_id,
                'quantity_on_hand'    => (int) $level->quantity_on_hand,
                'quantity_committed'  => (int) $level->quantity_committed,
                'available_quantity'  => (int) ($level->quantity_on_hand - $level->quantity_committed),
            ];
        })->values();

        $available = (int) $locations->sum('available_quantity');
        $reserved  = (int) $levels->sum('quantity_committed');

        return $this->successResponse([
            'product_id'         => $product->id,
            'available_quantity' => $available,
            'reserved_quantity'  => $reserved,
            'locations'          => $locations,
        ]);
    }

    // ── Import ────────────────────────────────────────────────────────────────

    /**
     * POST /api/inventory/products/import
     * Expects a multipart upload with a 'file' field (CSV).
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $path = $request->file('file')->store('imports/products');

        ImportProductsJob::dispatch($path);

        return $this->successResponse(['message' => 'Import queued successfully.'], 202);
    }
}
