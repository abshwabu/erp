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
            ->withCount(['invoices' => fn ($q) => $q->where('status', '!=', 'void')])
            ->withSum(['invoices as total_invoiced_cents' => fn ($q) => $q->whereIn('status', ['sent', 'paid'])], 'total_cents')
            ->withSum(['invoices as total_paid_cents' => fn ($q) => $q->whereIn('status', ['sent', 'paid'])], 'amount_paid_cents')
            ->orderBy('name')
            ->get()
            ->map(function (Customer $customer) {
                $totalInvoiced = (int) ($customer->total_invoiced_cents ?? 0);
                $totalPaid = (int) ($customer->total_paid_cents ?? 0);
                $customer->total_invoiced_cents = $totalInvoiced;
                $customer->total_paid_cents = $totalPaid;
                $customer->outstanding_cents = max(0, $totalInvoiced - $totalPaid);
                return $customer;
            });

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
        $customer = Customer::with(['invoices' => fn ($q) => $q->orderByDesc('issue_date')->with('payments')])->findOrFail($id);

        $totalInvoiced = (int) $customer->invoices->whereIn('status', ['sent', 'paid'])->sum('total_cents');
        $totalPaid = (int) $customer->invoices->whereIn('status', ['sent', 'paid'])->sum('amount_paid_cents');

        $customer->total_invoiced_cents = $totalInvoiced;
        $customer->total_paid_cents = $totalPaid;
        $customer->outstanding_cents = max(0, $totalInvoiced - $totalPaid);

        return $this->successResponse($customer);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $customer->update($validated);

        return $this->successResponse($customer);
    }

    public function destroy(string $id): JsonResponse
    {
        $customer = Customer::withCount('invoices')->findOrFail($id);

        if ($customer->invoices_count > 0) {
            return $this->errorResponse(
                "Cannot delete customer \"{$customer->name}\" because they have {$customer->invoices_count} invoice(s) attached.",
                422
            );
        }

        $customer->delete();

        return $this->noContentResponse();
    }
}
