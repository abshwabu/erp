<?php

declare(strict_types=1);

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Sales\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{
    public function index(): JsonResponse
    {
        $customers = Customer::query()
            ->orderBy('name')
            ->get();

        return $this->successResponse($customers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $customer = Customer::create($validated);

        return $this->createdResponse($customer);
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::with('invoices')->findOrFail($id);

        return $this->successResponse($customer);
    }
}
