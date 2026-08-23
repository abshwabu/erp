<?php

declare(strict_types=1);

namespace App\Modules\Shops\Services;

use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\POS\Models\POSTerminal;
use App\Modules\Shops\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopService
{
    public function __construct(protected ShopAccessService $access)
    {
    }

    public function create(array $data): Shop
    {
        return DB::transaction(function () use ($data) {
            $code = strtoupper(trim($data['code']));
            $stockMode = $data['stock_mode'];

            if ($stockMode === 'own') {
                $warehouse = Warehouse::create([
                    'name' => $data['name'] . ' Warehouse',
                    'code' => 'SHOP-' . $code,
                    'type' => 'own',
                    'is_active' => true,
                    'address' => $data['address'] ?? null,
                ]);

                $location = StockLocation::create([
                    'warehouse_id' => $warehouse->id,
                    'code' => 'SHOP-' . $code,
                    'name' => $data['name'] . ' Floor',
                    'type' => 'storage',
                    'is_active' => true,
                ]);
            } else {
                $location = StockLocation::with('warehouse')->find($data['stock_location_id'] ?? null);
                if (! $location) {
                    throw ValidationException::withMessages([
                        'stock_location_id' => ['A stock location is required when sharing warehouse stock.'],
                    ]);
                }
                $warehouse = $location->warehouse;
                if (! $warehouse) {
                    throw ValidationException::withMessages([
                        'stock_location_id' => ['Selected location has no warehouse.'],
                    ]);
                }
            }

            $shop = Shop::create([
                'name' => $data['name'],
                'code' => $code,
                'is_active' => $data['is_active'] ?? true,
                'stock_mode' => $stockMode,
                'warehouse_id' => $warehouse->id,
                'stock_location_id' => $location->id,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->ensureTerminal($shop);

            return $shop->load(['warehouse', 'stockLocation', 'users', 'terminals']);
        });
    }

    public function update(Shop $shop, array $data, bool $isManager): Shop
    {
        return DB::transaction(function () use ($shop, $data, $isManager) {
            $payload = [
                'name' => $data['name'] ?? $shop->name,
                'phone' => array_key_exists('phone', $data) ? $data['phone'] : $shop->phone,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $shop->notes,
                'address' => array_key_exists('address', $data) ? $data['address'] : $shop->address,
            ];

            if ($isManager) {
                if (array_key_exists('is_active', $data)) {
                    $payload['is_active'] = (bool) $data['is_active'];
                }
                if (array_key_exists('code', $data) && $data['code']) {
                    $payload['code'] = strtoupper(trim($data['code']));
                }

                // Stock mode / location changes only for managers
                if (($data['stock_mode'] ?? null) === 'shared_warehouse' && ! empty($data['stock_location_id'])) {
                    $location = StockLocation::with('warehouse')->findOrFail($data['stock_location_id']);
                    $payload['stock_mode'] = 'shared_warehouse';
                    $payload['stock_location_id'] = $location->id;
                    $payload['warehouse_id'] = $location->warehouse_id;
                }
            }

            $shop->update($payload);
            $shop->refresh();

            // Keep terminal location in sync
            POSTerminal::query()
                ->where('shop_id', $shop->id)
                ->update(['location_id' => $shop->stock_location_id]);

            $this->ensureTerminal($shop);

            return $shop->load(['warehouse', 'stockLocation', 'users', 'terminals']);
        });
    }

    public function syncKeepers(Shop $shop, array $keepers): Shop
    {
        $sync = [];

        foreach ($keepers as $keeper) {
            $userId = $keeper['user_id'] ?? $keeper['id'] ?? null;
            if (! $userId) {
                continue;
            }

            $sync[$userId] = ['role' => $keeper['role'] ?? 'keeper'];
        }

        $shop->users()->sync($sync);

        return $shop->load(['warehouse', 'stockLocation', 'users']);
    }

    public function ensureTerminal(Shop $shop): POSTerminal
    {
        $terminal = POSTerminal::query()
            ->where('shop_id', $shop->id)
            ->orderBy('created_at')
            ->first();

        if ($terminal) {
            $terminal->update([
                'location_id' => $shop->stock_location_id,
                'is_active' => $shop->is_active,
                'name' => $shop->name . ' Register',
            ]);

            return $terminal;
        }

        return POSTerminal::create([
            'name' => $shop->name . ' Register',
            'location_id' => $shop->stock_location_id,
            'shop_id' => $shop->id,
            'is_active' => $shop->is_active,
        ]);
    }
}
