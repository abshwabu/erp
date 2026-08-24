<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkOrderController extends BaseController
{
    public function index(): JsonResponse
    {
        $orders = WorkOrder::with('bom:id,name,product_id', 'bom.product:id,name,sku')
            ->orderByDesc('created_at')
            ->paginate(25);

        return $this->successResponse($orders);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bom_id'        => ['required', 'uuid', 'exists:bill_of_materials,id'],
            'quantity'      => ['required', 'integer', 'min:1'],
            'priority'      => ['sometimes', 'in:low,normal,high,urgent'],
            'planned_start' => ['nullable', 'date'],
            'planned_end'   => ['nullable', 'date', 'after_or_equal:planned_start'],
            'notes'         => ['nullable', 'string'],
        ]);

        $order = WorkOrder::create([
            'number'        => WorkOrder::nextNumber(),
            'bom_id'        => $data['bom_id'],
            'quantity'      => $data['quantity'],
            'status'        => 'draft',
            'priority'      => $data['priority'] ?? 'normal',
            'planned_start' => $data['planned_start'] ?? null,
            'planned_end'   => $data['planned_end'] ?? null,
            'notes'         => $data['notes'] ?? null,
        ]);

        return $this->createdResponse($order->load('bom:id,name'));
    }

    public function show(string $id): JsonResponse
    {
        $order = WorkOrder::with('bom.lines.material:id,name,sku', 'bom.product:id,name,sku')
            ->findOrFail($id);

        return $this->successResponse($order);
    }

    public function start(string $id): JsonResponse
    {
        $order = WorkOrder::findOrFail($id);

        if ($order->status !== 'draft') {
            return $this->errorResponse('Only draft work orders can be started.', 422);
        }

        $order->update([
            'status'     => 'in_progress',
            'started_at' => now(),
        ]);

        return $this->successResponse($order);
    }

    public function complete(string $id): JsonResponse
    {
        $order = WorkOrder::findOrFail($id);

        if ($order->status !== 'in_progress') {
            return $this->errorResponse('Only in-progress work orders can be completed.', 422);
        }

        $order->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return $this->successResponse($order);
    }
}
