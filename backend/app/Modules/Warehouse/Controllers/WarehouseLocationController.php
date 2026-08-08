<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseLocationController extends BaseController
{
    /**
     * GET /api/warehouse/locations
     */
    public function index(): JsonResponse
    {
        $locations = StockLocation::query()
            ->with('warehouse:id,name,code')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($locations->isEmpty()) {
            $location = $this->ensureDefaultLocation();
            $locations = collect([$location->load('warehouse:id,name,code')]);
        }

        return $this->successResponse($locations);
    }

    /**
     * POST /api/warehouse/locations
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:stock_locations,code'],
            'type' => ['nullable', 'string', 'max:20'],
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
        ]);

        $warehouseId = $validated['warehouse_id'] ?? $this->ensureDefaultWarehouse()->id;

        $location = StockLocation::create([
            'warehouse_id' => $warehouseId,
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'] ?? 'storage',
            'is_active' => true,
        ]);

        return $this->successResponse($location->load('warehouse:id,name,code'), 201);
    }

    protected function ensureDefaultWarehouse(): Warehouse
    {
        return Warehouse::firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'name' => 'Main Warehouse',
                'type' => 'own',
                'is_active' => true,
            ]
        );
    }

    protected function ensureDefaultLocation(): StockLocation
    {
        $warehouse = $this->ensureDefaultWarehouse();

        return StockLocation::firstOrCreate(
            ['code' => 'WH-MAIN'],
            [
                'warehouse_id' => $warehouse->id,
                'name' => 'Main Warehouse',
                'type' => 'storage',
                'is_active' => true,
            ]
        );
    }
}
