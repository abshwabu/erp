<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\Models\BillOfMaterial;
use App\Modules\Manufacturing\Models\BomLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BomController extends BaseController
{
    public function index(): JsonResponse
    {
        $boms = BillOfMaterial::with('product:id,name,sku', 'lines.material:id,name,sku')
            ->orderByDesc('created_at')
            ->paginate(25);

        return $this->successResponse($boms);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'      => ['required', 'uuid', 'exists:products,id'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'output_quantity' => ['sometimes', 'integer', 'min:1'],
            'lines'           => ['required', 'array', 'min:1'],
            'lines.*.material_id' => ['required', 'uuid', 'exists:products,id'],
            'lines.*.quantity'    => ['required', 'integer', 'min:1'],
            'lines.*.unit'        => ['nullable', 'string', 'max:20'],
            'lines.*.notes'       => ['nullable', 'string'],
        ]);

        $bom = BillOfMaterial::create([
            'product_id'      => $data['product_id'],
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'output_quantity' => $data['output_quantity'] ?? 1,
            'status'          => 'draft',
        ]);

        foreach ($data['lines'] as $line) {
            BomLine::create([
                'bom_id'      => $bom->id,
                'material_id' => $line['material_id'],
                'quantity'    => $line['quantity'],
                'unit'        => $line['unit'] ?? 'pcs',
                'notes'       => $line['notes'] ?? null,
            ]);
        }

        return $this->createdResponse($bom->load('lines.material:id,name,sku'));
    }

    public function show(string $id): JsonResponse
    {
        $bom = BillOfMaterial::with('product:id,name,sku', 'lines.material:id,name,sku')
            ->findOrFail($id);

        return $this->successResponse($bom);
    }

    public function activate(string $id): JsonResponse
    {
        $bom = BillOfMaterial::findOrFail($id);

        if ($bom->status === 'active') {
            return $this->errorResponse('BOM is already active.', 422);
        }

        $bom->update(['status' => 'active']);

        return $this->successResponse($bom);
    }
}
