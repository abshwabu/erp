<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Models\StockLocation;
use Illuminate\Http\JsonResponse;

class StockLocationController extends BaseController
{
    /**
     * GET /api/inventory/locations
     * List all active stock locations.
     */
    public function index(): JsonResponse
    {
        $locations = StockLocation::where('is_active', true)->get();

        if ($locations->isEmpty()) {
            $defaultLocation = StockLocation::firstOrCreate(
                ['code' => 'WH-MAIN'],
                ['name' => 'Main Warehouse', 'type' => 'internal', 'is_active' => true]
            );
            $locations = collect([$defaultLocation]);
        }

        return response()->json([
            'data' => $locations
        ]);
    }
}
