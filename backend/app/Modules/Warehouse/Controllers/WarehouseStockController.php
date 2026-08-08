<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseStockController extends BaseController
{
    public function __construct(protected StockService $stockService)
    {
    }

    /**
     * POST /api/warehouse/receive
     */
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'location_id' => ['required', 'uuid', 'exists:stock_locations,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
            'lot_number' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $movement = $this->stockService->receiveStock(
                $validated['product_id'],
                $validated['location_id'],
                (int) $validated['quantity'],
                (int) ($validated['unit_cost'] ?? 0),
                ['type' => 'warehouse_receive'],
                $validated['lot_number'] ?? null,
                $validated['serial_number'] ?? null,
                null,
                $validated['variant_id'] ?? null
            );
        } catch (InsufficientStockException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($this->formatMovement($movement), 201);
    }

    /**
     * POST /api/warehouse/transfer
     */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'from_location_id' => ['required', 'uuid', 'exists:stock_locations,id'],
            'to_location_id' => ['required', 'uuid', 'exists:stock_locations,id', 'different:from_location_id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'uuid', 'exists:product_variants,id'],
        ]);

        try {
            $movements = $this->stockService->transferStock(
                $validated['product_id'],
                $validated['from_location_id'],
                $validated['to_location_id'],
                (int) $validated['quantity'],
                ['type' => 'warehouse_transfer'],
                $validated['variant_id'] ?? null
            );
        } catch (InsufficientStockException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        $movement = $movements[0] ?? null;

        return $this->successResponse(
            $movement ? $this->formatMovement($movement) : null,
            201
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatMovement(StockMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'product_id' => $movement->product_id,
            'variant_id' => $movement->variant_id,
            'from_location_id' => $movement->from_location_id,
            'to_location_id' => $movement->to_location_id,
            'quantity' => $movement->quantity,
            'type' => $movement->type,
            'unit_cost' => $movement->unit_cost,
            'reference_type' => $movement->reference_type,
            'created_at' => $movement->created_at?->toIso8601String(),
        ];
    }
}
