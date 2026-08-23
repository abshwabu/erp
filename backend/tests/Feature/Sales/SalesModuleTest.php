<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Sales\Models\Customer;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles customers and invoice lifecycle including payments and filtering', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Sales Test Tenant',
        'slug'   => 'salestest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Sales Admin',
            'email'     => 'sales@test.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        return ['admin' => $admin];
    });

    $loginResponse = $this->postJson('http://salestest.localhost/api/auth/login', [
        'email'    => 'sales@test.com',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create Customer
    $createCustResponse = $this->withHeaders($headers)->postJson('http://salestest.localhost/api/sales/customers', [
        'name' => 'Acme Corporation',
        'email' => 'contact@acme.com',
        'phone' => '+1 555 1234',
    ]);
    $createCustResponse->assertStatus(201);
    $customerId = $createCustResponse->json('data.id');
    expect($customerId)->not->toBeNull();
    expect($createCustResponse->json('data.name'))->toBe('Acme Corporation');

    // 2. List Customers
    $listCustResponse = $this->withHeaders($headers)->getJson('http://salestest.localhost/api/sales/customers');
    $listCustResponse->assertStatus(200);
    $listCustResponse->assertJsonCount(1, 'data');

    // 3. Create Draft Invoice
    $createInvResponse = $this->withHeaders($headers)->postJson('http://salestest.localhost/api/sales/invoices', [
        'customer_id' => $customerId,
        'issue_date' => '2026-08-01',
        'due_date' => '2026-08-31',
        'tax_cents' => 500,
        'notes' => 'Consulting services for August',
        'lines' => [
            [
                'description' => 'Security Architecture Review',
                'quantity' => 10,
                'unit_price_cents' => 15000,
            ],
            [
                'description' => 'Cloud Audit',
                'quantity' => 5,
                'unit_price_cents' => 10000,
            ],
        ],
    ]);
    $createInvResponse->assertStatus(201);
    $invoiceId = $createInvResponse->json('data.id');
    expect($invoiceId)->not->toBeNull();
    expect($createInvResponse->json('data.subtotal_cents'))->toBe(200000);
    expect($createInvResponse->json('data.tax_cents'))->toBe(500);
    expect($createInvResponse->json('data.total_cents'))->toBe(200500);
    expect($createInvResponse->json('data.amount_paid_cents'))->toBe(0);
    expect($createInvResponse->json('data.status'))->toBe('draft');
    expect($createInvResponse->json('data.number'))->toBe('INV-00001');

    // 4. Draft invoice cannot accept payments
    $badPayment = $this->withHeaders($headers)->postJson("http://salestest.localhost/api/sales/invoices/{$invoiceId}/payments", [
        'amount_cents' => 50000,
        'method' => 'bank_transfer',
    ]);
    $badPayment->assertStatus(422);

    // 5. Mark Invoice as Sent
    $sentResponse = $this->withHeaders($headers)->postJson("http://salestest.localhost/api/sales/invoices/{$invoiceId}/mark-sent");
    $sentResponse->assertStatus(200);
    expect($sentResponse->json('data.status'))->toBe('sent');

    // 6. Record Partial Payment ($1,000.00 = 100,000 cents)
    $partialPayment = $this->withHeaders($headers)->postJson("http://salestest.localhost/api/sales/invoices/{$invoiceId}/payments", [
        'amount_cents' => 100000,
        'method' => 'bank_transfer',
        'reference' => 'TXN-101',
    ]);
    $partialPayment->assertStatus(200);
    expect($partialPayment->json('data.amount_paid_cents'))->toBe(100000);
    expect($partialPayment->json('data.status'))->toBe('sent');

    // 7. Overpayment rejection (total is 200,500, paid 100,000, remaining 100,500; try paying 150,000)
    $overpayment = $this->withHeaders($headers)->postJson("http://salestest.localhost/api/sales/invoices/{$invoiceId}/payments", [
        'amount_cents' => 150000,
        'method' => 'cash',
    ]);
    $overpayment->assertStatus(422);

    // 8. Record Final Payment ($1,005.00 = 100,500 cents)
    $finalPayment = $this->withHeaders($headers)->postJson("http://salestest.localhost/api/sales/invoices/{$invoiceId}/payments", [
        'amount_cents' => 100500,
        'method' => 'cash',
        'reference' => 'TXN-102',
    ]);
    $finalPayment->assertStatus(200);
    expect($finalPayment->json('data.amount_paid_cents'))->toBe(200500);
    expect($finalPayment->json('data.status'))->toBe('paid');
    expect($finalPayment->json('data.payments'))->toHaveCount(2);

    // 9. Status and Customer Filtering
    $draftFilter = $this->withHeaders($headers)->getJson('http://salestest.localhost/api/sales/invoices?status=draft');
    $draftFilter->assertStatus(200);
    $draftFilter->assertJsonCount(0, 'data');

    $paidFilter = $this->withHeaders($headers)->getJson('http://salestest.localhost/api/sales/invoices?status=paid');
    $paidFilter->assertStatus(200);
    $paidFilter->assertJsonCount(1, 'data');
    expect($paidFilter->json('data.0.id'))->toBe($invoiceId);
});
