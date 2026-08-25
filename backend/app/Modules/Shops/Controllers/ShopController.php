<?php

declare(strict_types=1);

namespace App\Modules\Shops\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Shops\Models\Shop;
use App\Modules\Shops\Services\ShopAccessService;
use App\Modules\Shops\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends BaseController
{
    public function __construct(
        protected ShopAccessService $access,
        protected ShopService $shops,
        protected StockService $stockService
    ) {
    }

    public function index(): JsonResponse
    {
        $shops = $this->access->shopsForUser(activeOnly: false);

        return $this->successResponse($shops->map(fn (Shop $shop) => $this->serializeShop($shop)));
    }

    /**
     * Shops available for the current user (POS picker).
     * Managers see all active shops; others see assigned active shops.
     */
    public function mine(): JsonResponse
    {
        $shops = $this->access->shopsForUser(activeOnly: true);

        return $this->successResponse($shops->map(fn (Shop $shop) => $this->serializeShop($shop)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->access->assertCanManageShops();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:shops,code'],
            'stock_mode' => ['required', 'in:own,shared_warehouse'],
            'stock_location_id' => ['nullable', 'uuid', 'exists:stock_locations,id'],
            'address' => ['nullable', 'array'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($data['stock_mode'] === 'shared_warehouse' && empty($data['stock_location_id'])) {
            return $this->errorResponse('stock_location_id is required for shared warehouse shops.', 422);
        }

        $shop = $this->shops->create($data);

        return $this->createdResponse($this->serializeShop($shop));
    }

    public function show(string $shop): JsonResponse
    {
        $model = $this->access->assertCanAccessShop($shop);

        return $this->successResponse(
            $this->serializeShop($model->load(['warehouse', 'stockLocation', 'users', 'terminals']))
        );
    }

    public function update(Request $request, string $shop): JsonResponse
    {
        $model = $this->access->assertCanAccessShop($shop);
        $isManager = $this->access->canManageShops();

        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'address' => ['nullable', 'array'],
        ];

        if ($isManager) {
            $rules['code'] = ['sometimes', 'string', 'max:50', 'unique:shops,code,' . $model->id];
            $rules['is_active'] = ['sometimes', 'boolean'];
            $rules['stock_mode'] = ['sometimes', 'in:own,shared_warehouse'];
            $rules['stock_location_id'] = ['nullable', 'uuid', 'exists:stock_locations,id'];
        }

        $data = $request->validate($rules);
        $updated = $this->shops->update($model, $data, $isManager);

        return $this->successResponse($this->serializeShop($updated));
    }

    public function keepers(string $shop): JsonResponse
    {
        $this->access->assertCanManageShops();
        $model = Shop::with('users')->findOrFail($shop);

        return $this->successResponse(
            $model->users->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot->role ?? 'keeper',
            ])
        );
    }

    public function syncKeepers(Request $request, string $shop): JsonResponse
    {
        $this->access->assertCanManageShops();
        $model = Shop::findOrFail($shop);

        $data = $request->validate([
            'keepers' => ['required', 'array'],
            'keepers.*.user_id' => ['required', 'uuid', 'exists:users,id'],
            'keepers.*.role' => ['nullable', 'in:keeper,manager'],
        ]);

        $updated = $this->shops->syncKeepers($model, $data['keepers']);

        return $this->successResponse($this->serializeShop($updated));
    }

    public function assignableUsers(): JsonResponse
    {
        $this->access->assertCanManageShops();

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return $this->successResponse($users);
    }

    public function stock(string $shop): JsonResponse
    {
        $model = $this->access->assertCanAccessShop($shop);
        $locationId = $model->stock_location_id;

        $levels = StockLevel::query()
            ->where('location_id', $locationId)
            ->with(['product.category', 'product.images', 'location'])
            ->get();

        $byProduct = [];
        foreach ($levels as $level) {
            if (! $level->product) {
                continue;
            }
            $key = $level->product_id;
            if (! isset($byProduct[$key])) {
                $byProduct[$key] = [
                    'product_id' => $level->product_id,
                    'name' => $level->product->name,
                    'sku' => $level->product->sku,
                    'selling_price' => (int) $level->product->selling_price,
                    'cost_price' => (int) $level->product->cost_price,
                    'category_id' => $level->product->category_id,
                    'primary_image_url' => $level->product->primary_image_url,
                    'images' => $level->product->images,
                    'quantity_on_hand' => 0,
                    'quantity_committed' => 0,
                    'available_quantity' => 0,
                    'location_id' => $locationId,
                    'location_name' => $level->location?->name,
                ];
            }
            $byProduct[$key]['quantity_on_hand'] += (int) $level->quantity_on_hand;
            $byProduct[$key]['quantity_committed'] += (int) $level->quantity_committed;
            $byProduct[$key]['available_quantity'] =
                $byProduct[$key]['quantity_on_hand'] - $byProduct[$key]['quantity_committed'];
        }

        // Include active products with zero stock at this location for adjustment & POS catalog UX
        $existingIds = array_keys($byProduct);
        $zeroProducts = Product::query()
            ->where('status', 'active')
            ->with(['category', 'images'])
            ->when($existingIds !== [], fn ($q) => $q->whereNotIn('id', $existingIds))
            ->orderBy('name')
            ->limit(200)
            ->get();

        foreach ($zeroProducts as $product) {
            $byProduct[$product->id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => (int) $product->selling_price,
                'cost_price' => (int) $product->cost_price,
                'category_id' => $product->category_id,
                'primary_image_url' => $product->primary_image_url,
                'images' => $product->images,
                'quantity_on_hand' => 0,
                'quantity_committed' => 0,
                'available_quantity' => 0,
                'location_id' => $locationId,
                'location_name' => $model->stockLocation?->name,
            ];
        }

        return $this->successResponse(array_values($byProduct));
    }

    public function adjustStock(Request $request, string $shop): JsonResponse
    {
        $model = $this->access->assertCanAccessShop($shop);

        $data = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:add,remove'],
            'reason' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
        ]);

        $locationId = $model->stock_location_id;
        $qty = (int) $data['quantity'];

        try {
            if ($data['type'] === 'add') {
                $movement = $this->stockService->receiveStock(
                    $data['product_id'],
                    $locationId,
                    $qty,
                    0,
                    ['type' => $data['reason'] ?? 'shop_adjustment'],
                    null,
                    null,
                    null,
                    $data['variant_id'] ?? null
                );
            } else {
                $movement = $this->stockService->issueStock(
                    $data['product_id'],
                    $locationId,
                    $qty,
                    [
                        'type' => $data['reason'] ?? 'shop_adjustment',
                        'movement_type' => 'adjustment',
                    ],
                    null,
                    null,
                    $data['variant_id'] ?? null,
                    true // strict to shop location
                );
            }
        } catch (\App\Modules\Inventory\Exceptions\InsufficientStockException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse([
            'id' => $movement->id,
            'product_id' => $movement->product_id,
            'quantity' => $movement->quantity,
            'type' => $movement->type,
            'location_id' => $locationId,
        ]);
    }

    private function serializeShop(Shop $shop): array
    {
        return [
            'id' => $shop->id,
            'name' => $shop->name,
            'code' => $shop->code,
            'is_active' => (bool) $shop->is_active,
            'stock_mode' => $shop->stock_mode,
            'warehouse_id' => $shop->warehouse_id,
            'stock_location_id' => $shop->stock_location_id,
            'warehouse' => $shop->relationLoaded('warehouse') && $shop->warehouse
                ? ['id' => $shop->warehouse->id, 'name' => $shop->warehouse->name, 'code' => $shop->warehouse->code]
                : null,
            'stock_location' => $shop->relationLoaded('stockLocation') && $shop->stockLocation
                ? [
                    'id' => $shop->stockLocation->id,
                    'name' => $shop->stockLocation->name,
                    'code' => $shop->stockLocation->code,
                ]
                : null,
            'address' => $shop->address,
            'phone' => $shop->phone,
            'notes' => $shop->notes,
            'keepers' => $shop->relationLoaded('users')
                ? $shop->users->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role ?? 'keeper',
                ])->values()
                : [],
            'terminals' => $shop->relationLoaded('terminals')
                ? $shop->terminals->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'location_id' => $t->location_id,
                    'is_active' => (bool) $t->is_active,
                ])->values()
                : [],
            'created_at' => $shop->created_at?->toIso8601String(),
            'updated_at' => $shop->updated_at?->toIso8601String(),
        ];
    }
}
