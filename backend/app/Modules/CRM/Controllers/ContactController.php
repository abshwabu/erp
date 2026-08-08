<?php

declare(strict_types=1);

namespace App\Modules\CRM\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Sales\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ContactController extends BaseController
{
    public function index(): JsonResponse
    {
        $contacts = $this->query()->orderBy('name')->get();

        return $this->successResponse($contacts);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:lead,customer'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ];

        if (Schema::hasColumn($this->table(), 'company')) {
            $payload['company'] = $validated['company'] ?? null;
            $payload['status'] = $validated['status'] ?? 'customer';
        }

        $contact = $this->query()->create($payload);

        return $this->createdResponse($contact);
    }

    private function query()
    {
        if (class_exists(Customer::class) && Schema::hasTable('customers')) {
            return Customer::query();
        }

        return \App\Modules\CRM\Models\CrmContact::query();
    }

    private function table(): string
    {
        if (class_exists(Customer::class) && Schema::hasTable('customers')) {
            return 'customers';
        }

        return 'crm_contacts';
    }
}
