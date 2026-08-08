<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Procurement\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends BaseController
{
    public function index(): JsonResponse
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        return $this->successResponse($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $supplier = Supplier::create($validated);

        return $this->createdResponse($supplier);
    }
}
