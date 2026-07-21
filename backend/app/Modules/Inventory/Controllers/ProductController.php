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
            ->with(['category', 'barcodes', 'stockLevels']);

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
            unset($validated['barcodes']);

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

            return $product;
        });

        $product->load(['category', 'barcodes', 'variants', 'stockLevels']);

        return $this->createdResponse(new ProductResource($product));
    }

    /**
     * GET /api/inventory/products/{product}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'barcodes', 'variants', 'images', 'uoms']);

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * PUT /api/inventory/products/{product}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());
        $product->load(['category', 'barcodes', 'variants']);

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

    // ── Stock (stub) ──────────────────────────────────────────────────────────

    /**
     * GET /api/inventory/products/{product}/stock
     * Stubbed until the Warehouse / StockLocation module is built.
     */
    public function stock(Product $product): JsonResponse
    {
        return $this->successResponse([
            'product_id'         => $product->id,
            'available_quantity' => 0,
            'reserved_quantity'  => 0,
            'locations'          => [],
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
