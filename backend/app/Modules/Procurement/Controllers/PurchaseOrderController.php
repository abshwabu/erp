<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Inventory\Services\StockService;
use App\Modules\Procurement\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends BaseController
{
    public function __construct(
        private readonly StockService $stockService
    ) {}

    public function index(): JsonResponse
    {
        $orders = PurchaseOrder::query()
            ->with(['supplier', 'lines'])
            ->orderByDesc('order_date')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,id'],
            'number' => ['nullable', 'string', 'max:50', 'unique:purchase_orders,number'],
            'status' => ['nullable', 'in:draft,ordered,received,cancelled'],
            'order_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'uuid', 'exists:products,id'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost_cents' => ['required', 'integer', 'min:0'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $totalCents = 0;
            foreach ($validated['lines'] as $line) {
                $totalCents += (int) round((float) $line['quantity'] * (int) $line['unit_cost_cents']);
            }

            $order = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'number' => $validated['number'] ?? $this->nextNumber(),
                'status' => $validated['status'] ?? 'draft',
                'order_date' => $validated['order_date'],
                'total_cents' => $totalCents,
            ]);

            foreach ($validated['lines'] as $line) {
                $order->lines()->create([
                    'product_id' => $line['product_id'] ?? null,
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_cost_cents' => $line['unit_cost_cents'],
                    'received_quantity' => 0,
                ]);
            }

            return $order->load(['supplier', 'lines']);
        });

        return $this->createdResponse($order);
    }

    public function receive(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'location_id' => ['nullable', 'uuid', 'exists:stock_locations,id'],
        ]);

        $order = PurchaseOrder::with('lines')->findOrFail($id);

        if (in_array($order->status, ['received', 'cancelled'], true)) {
            return $this->errorResponse('Purchase order cannot be received in its current status.', 422);
        }

        $order = DB::transaction(function () use ($order, $validated) {
            foreach ($order->lines as $line) {
                $remaining = (float) $line->quantity - (float) $line->received_quantity;
                if ($remaining <= 0) {
                    continue;
                }

                if ($line->product_id && ! empty($validated['location_id'])) {
                    $this->stockService->receiveStock(
                        $line->product_id,
                        $validated['location_id'],
                        (int) ceil($remaining),
                        (int) $line->unit_cost_cents,
                        ['type' => 'purchase_order', 'id' => $order->id]
                    );
                }

                $line->update([
                    'received_quantity' => $line->quantity,
                ]);
            }

            $order->update(['status' => 'received']);

            return $order->fresh(['supplier', 'lines']);
        });

        return $this->successResponse($order);
    }

    private function nextNumber(): string
    {
        $seq = PurchaseOrder::query()->count() + 1;

        return 'PO-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
