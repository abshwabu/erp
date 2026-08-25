<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Jobs\ImportProductsJob;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductBarcode;
use App\Modules\Inventory\Models\ProductImage;
use App\Modules\Inventory\Models\ProductVariant;
use App\Modules\Inventory\Requests\StoreProductRequest;
use App\Modules\Inventory\Requests\UpdateProductRequest;
use App\Modules\Inventory\Resources\ProductBarcodeResource;
use App\Modules\Inventory\Resources\ProductResource;
use App\Modules\Inventory\Resources\ProductVariantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends BaseController
{
    /**
     * GET /api/inventory/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category', 'barcodes', 'variants.stockLevels', 'stockLevels', 'images']);

        if ($search = trim((string) $request->input('search', $request->input('filter.search', '')))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        $categoryId = $request->input('category_id', $request->input('filter.category_id'));
        if (! empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        $status = $request->input('status', $request->input('filter.status'));
        if (! empty($status)) {
            $query->where('status', $status);
        }

        $type = $request->input('type', $request->input('filter.type'));
        if (! empty($type)) {
            $query->where('type', $type);
        }

        $query->orderBy('name');

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
            $images    = $validated['images'] ?? [];
            $primaryImageUrl = $validated['primary_image_url'] ?? null;
            $initialStock = (int) ($validated['initial_stock'] ?? 0);
            $requestedLocationId = $validated['location_id'] ?? null;
            unset(
                $validated['barcodes'],
                $validated['variants'],
                $validated['images'],
                $validated['primary_image_url'],
                $validated['initial_stock'],
                $validated['location_id']
            );

            if (empty($validated['sku'])) {
                $validated['sku'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']) ?: 'PRD', 0, 6))
                    . '-' . strtoupper(substr(uniqid(), -5));
            }

            $product = Product::create($validated);

            foreach ($barcodes as $barcode) {
                $product->barcodes()->create($barcode);
            }

            // Create images
            $hasPrimary = false;
            foreach ($images as $idx => $img) {
                $isPrimary = ! empty($img['is_primary']) || ($idx === 0 && ! $hasPrimary);
                if ($isPrimary) {
                    $hasPrimary = true;
                }
                $path = $img['path'] ?? $img['url'] ?? '';
                if ($path) {
                    $product->images()->create([
                        'id' => (string) Str::uuid(),
                        'path' => $path,
                        'is_primary' => $isPrimary,
                        'sort_order' => $img['sort_order'] ?? $idx,
                    ]);
                }
            }

            if (! $hasPrimary && $primaryImageUrl) {
                $product->images()->create([
                    'id' => (string) Str::uuid(),
                    'path' => $primaryImageUrl,
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }

            // Create initial stock level record for default main warehouse if none exists
            $warehouse = \App\Modules\Inventory\Models\Warehouse::firstOrCreate(
                ['code' => 'WH-MAIN'],
                ['name' => 'Main Warehouse', 'type' => 'own', 'is_active' => true]
            );

            $location = $requestedLocationId
                ? \App\Modules\Inventory\Models\StockLocation::findOrFail($requestedLocationId)
                : \App\Modules\Inventory\Models\StockLocation::firstOrCreate(
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
                'variant_id' => null,
            ], [
                'quantity_on_hand' => 0,
                'quantity_committed' => 0,
                'quantity_on_order' => 0,
            ]);

            if ($initialStock > 0 && empty($variants)) {
                \App\Modules\Inventory\Models\StockMovement::create([
                    'product_id' => $product->id,
                    'to_location_id' => $location->id,
                    'quantity' => $initialStock,
                    'type' => 'opening',
                    'reference_type' => 'InitialStock',
                    'unit_cost' => (int) ($validated['cost_price'] ?? 0),
                    'currency_code' => $product->currency_code ?: 'USD',
                    'user_id' => auth()->id() ?? \App\Modules\Core\Models\User::first()?->id,
                ]);
            }

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

                // Opening stock via movement only (observer updates levels — avoid double count)
                if (isset($variant['stock']) && $variant['stock'] > 0) {
                    \App\Modules\Inventory\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'variant_id' => $createdVariant->id,
                        'to_location_id' => $location->id,
                        'quantity' => $variant['stock'],
                        'type' => 'opening',
                        'reference_type' => 'InitialStock',
                        'unit_cost' => (int) ($variant['cost_price'] ?? 0),
                        'currency_code' => $product->currency_code ?: 'USD',
                        'user_id' => auth()->id(),
                    ]);
                } else {
                    \App\Modules\Inventory\Models\StockLevel::firstOrCreate([
                        'product_id' => $product->id,
                        'variant_id' => $createdVariant->id,
                        'location_id' => $location->id,
                    ], [
                        'quantity_on_hand' => 0,
                        'quantity_committed' => 0,
                        'quantity_on_order' => 0,
                    ]);
                }
            }

            return $product;
        });

        $product->load(['category', 'barcodes', 'variants.stockLevels', 'stockLevels', 'images']);

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
            $images    = $validated['images'] ?? null;
            $primaryImageUrl = $validated['primary_image_url'] ?? null;
            unset($validated['variants'], $validated['images'], $validated['primary_image_url']);

            $product->update($validated);

            // Handle images update if provided
            if ($images !== null) {
                $product->images()->delete();
                $hasPrimary = false;
                foreach ($images as $idx => $img) {
                    $isPrimary = ! empty($img['is_primary']) || ($idx === 0 && ! $hasPrimary);
                    if ($isPrimary) {
                        $hasPrimary = true;
                    }
                    $path = $img['path'] ?? $img['url'] ?? '';
                    if ($path) {
                        $product->images()->create([
                            'id' => (string) Str::uuid(),
                            'path' => $path,
                            'is_primary' => $isPrimary,
                            'sort_order' => $img['sort_order'] ?? $idx,
                        ]);
                    }
                }

                if (! $hasPrimary && $primaryImageUrl) {
                    $product->images()->create([
                        'id' => (string) Str::uuid(),
                        'path' => $primaryImageUrl,
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]);
                }
            }

            // Handle non-variant stock update if provided
            $incomingStock = $validated['stock'] ?? $validated['initial_stock'] ?? null;
            if ($incomingStock !== null && $variants === null && ! $product->has_variants) {
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
                    'variant_id' => null,
                    'location_id' => $location->id,
                ], [
                    'quantity_on_hand' => 0,
                    'quantity_committed' => 0,
                    'quantity_on_order' => 0,
                ]);

                $diff = (int) $incomingStock - (int) $stockLevel->quantity_on_hand;
                if ($diff !== 0) {
                    $stockLevel->update(['quantity_on_hand' => (int) $incomingStock]);

                    \App\Modules\Inventory\Models\StockMovement::create([
                        'product_id' => $product->id,
                        'to_location_id' => $location->id,
                        'quantity' => abs($diff),
                        'type' => 'adjustment',
                        'reference_type' => 'StockAdjustment',
                        'unit_cost' => (int) ($validated['cost_price'] ?? $product->cost_price ?? 0),
                        'currency_code' => $product->currency_code ?: 'USD',
                        'user_id' => auth()->id() ?? \App\Modules\Core\Models\User::first()?->id,
                    ]);
                }
            }

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

                    if (isset($variant['id']) && ! empty($variant['id'])) {
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

        $product->load(['category', 'barcodes', 'variants.stockLevels', 'images']);

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

    // ── Media & Image Upload ───────────────────────────────────────────────────

    /**
     * POST /api/inventory/media/upload
     * General media uploader that stores image in public storage and returns URL.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $file = $request->file('file');
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('products', $fileName, 'public');

        $url = Storage::disk('public')->url($path);

        return $this->successResponse([
            'path' => $path,
            'url'  => $url,
            'name' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * POST /api/inventory/products/{product}/images
     */
    public function uploadImage(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'image', 'max:10240'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('file');
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('products/' . $product->id, $fileName, 'public');

        $isPrimary = $request->boolean('is_primary') || $product->images()->count() === 0;

        if ($isPrimary) {
            $product->images()->update(['is_primary' => false]);
        }

        $image = $product->images()->create([
            'id' => (string) Str::uuid(),
            'path' => $path,
            'is_primary' => $isPrimary,
            'sort_order' => $product->images()->count(),
        ]);

        return $this->createdResponse([
            'id' => $image->id,
            'path' => $image->path,
            'url' => $image->url,
            'is_primary' => $image->is_primary,
            'sort_order' => $image->sort_order,
        ]);
    }

    /**
     * DELETE /api/inventory/products/{product}/images/{image}
     */
    public function deleteImage(Product $product, ProductImage $image): JsonResponse
    {
        if ($image->product_id !== $product->id) {
            return $this->errorResponse('Image does not belong to this product', 404);
        }

        if ($image->path && ! str_starts_with($image->path, 'http') && ! str_starts_with($image->path, 'data:')) {
            Storage::disk('public')->delete($image->path);
        }

        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

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
