<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\POS\Models\POSTerminal;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('completes POS checkout flow and verifies stock reduction and receipt', function (): void {
    $tenant = Tenant::create([
        'name'   => 'POS Test Tenant',
        'slug'   => 'postest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $cashier = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Cashier User',
            'email'     => 'cashier@pos.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $cashier->assignRole('Cashier');

        $warehouse = Warehouse::create([
            'name'      => 'POS Warehouse',
            'code'      => 'POS-WH',
            'type'      => 'own',
            'is_active' => true,
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'name'         => 'Storefront',
            'code'         => 'STORE-1',
            'type'         => 'storage',
            'is_active'    => true,
        ]);

        $terminal = POSTerminal::create([
            'name'        => 'Main Register',
            'location_id' => $location->id,
            'is_active'   => true,
        ]);

        $product = Product::create([
            'sku'           => 'COFFEE-01',
            'name'          => 'Espresso Roast',
            'type'          => 'stockable',
            'status'        => 'active',
            'cost_price'    => 500,
            'selling_price' => 1200,
        ]);

        StockLevel::create([
            'product_id'         => $product->id,
            'location_id'        => $location->id,
            'quantity_on_hand'   => 50,
            'quantity_committed' => 0,
            'quantity_on_order'  => 0,
        ]);

        return [
            'cashier'  => $cashier,
            'terminal' => $terminal,
            'product'  => $product,
            'location' => $location,
        ];
    });

    $loginResponse = $this->postJson('http://postest.localhost/api/auth/login', [
        'email'    => 'cashier@pos.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Open session
    $openResponse = $this->withHeaders($headers)->postJson('http://postest.localhost/api/pos/sessions/open', [
        'terminal_id'        => $data['terminal']->id,
        'opening_cash_cents' => 10000, // $100.00
    ]);

    $openResponse->assertStatus(201);
    $sessionId = $openResponse->json('data.id');
    expect($sessionId)->not->toBeNull();

    // 2. Checkout
    $checkoutResponse = $this->withHeaders($headers)->postJson('http://postest.localhost/api/pos/checkout', [
        'session_id'  => $sessionId,
        'location_id' => $data['location']->id,
        'items'       => [
            [
                'product_id'       => $data['product']->id,
                'quantity'         => 2,
                'unit_price_cents' => 1200,
            ],
        ],
        'payments'    => [
            [
                'method'       => 'cash',
                'amount_cents' => 3000,
                'change_cents' => 480,
            ],
        ],
    ]);

    $checkoutResponse->assertStatus(201);
    $receiptNumber = $checkoutResponse->json('data.receipt_number');
    $subtotalCents = $checkoutResponse->json('data.subtotal_cents');
    $totalCents = $checkoutResponse->json('data.total_cents');

    expect($subtotalCents)->toBe(2400); // 2 * $12.00
    expect($totalCents)->toBe(2520);    // $24.00 + 5% tax ($1.20) = $25.20
    expect($receiptNumber)->toStartWith('REC-');

    // 3. Verify stock reduced to 48
    $tenant->run(function () use ($data) {
        $level = StockLevel::where('product_id', $data['product']->id)
            ->where('location_id', $data['location']->id)
            ->first();

        expect((int) $level->quantity_on_hand)->toBe(48);
    });

    // 4. Receipt lookup
    $receiptResponse = $this->withHeaders($headers)->getJson("http://postest.localhost/api/pos/receipts/{$receiptNumber}");
    $receiptResponse->assertStatus(200);
    $receiptResponse->assertJsonPath('data.receipt_number', $receiptNumber);
    $receiptResponse->assertJsonCount(1, 'data.items');
    $receiptResponse->assertJsonCount(1, 'data.payments');

    // 5. Close session
    $closeResponse = $this->withHeaders($headers)->postJson("http://postest.localhost/api/pos/sessions/{$sessionId}/close", [
        'closing_cash_cents' => 12520, // $100 opening + $25.20 cash sales
    ]);
    $closeResponse->assertStatus(200);
    $closeResponse->assertJsonPath('data.status', 'closed');
    $closeResponse->assertJsonPath('data.cash_variance_cents', 12520 - 10000);
});

it('rejects checkout with insufficient stock', function (): void {
    $tenant = Tenant::create([
        'name'   => 'POS Stock Test Tenant',
        'slug'   => 'posstock',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $cashier = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Cashier User',
            'email'     => 'cashier@posstock.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $cashier->assignRole('Cashier');

        $warehouse = Warehouse::create([
            'name'      => 'Warehouse',
            'code'      => 'WH-LOW',
            'type'      => 'own',
            'is_active' => true,
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'name'         => 'Storefront',
            'code'         => 'STORE-LOW',
            'type'         => 'storage',
            'is_active'    => true,
        ]);

        $terminal = POSTerminal::create([
            'name'        => 'Register 1',
            'location_id' => $location->id,
            'is_active'   => true,
        ]);

        $product = Product::create([
            'sku'           => 'LIMITED-01',
            'name'          => 'Limited Item',
            'type'          => 'stockable',
            'status'        => 'active',
            'cost_price'    => 100,
            'selling_price' => 500,
        ]);

        StockLevel::create([
            'product_id'         => $product->id,
            'location_id'        => $location->id,
            'quantity_on_hand'   => 1,
            'quantity_committed' => 0,
            'quantity_on_order'  => 0,
        ]);

        return [
            'cashier'  => $cashier,
            'terminal' => $terminal,
            'product'  => $product,
            'location' => $location,
        ];
    });

    $loginResponse = $this->postJson('http://posstock.localhost/api/auth/login', [
        'email'    => 'cashier@posstock.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    $openResponse = $this->withHeaders($headers)->postJson('http://posstock.localhost/api/pos/sessions/open', [
        'terminal_id' => $data['terminal']->id,
    ]);
    $sessionId = $openResponse->json('data.id');

    // Attempt to buy 10 items when only 1 is available
    $checkoutResponse = $this->withHeaders($headers)->postJson('http://posstock.localhost/api/pos/checkout', [
        'session_id'  => $sessionId,
        'location_id' => $data['location']->id,
        'items'       => [
            [
                'product_id'       => $data['product']->id,
                'quantity'         => 10,
                'unit_price_cents' => 500,
            ],
        ],
        'payments'    => [
            [
                'method'       => 'cash',
                'amount_cents' => 6000,
            ],
        ],
    ]);

    $checkoutResponse->assertStatus(422);
    $checkoutResponse->assertJsonFragment([
        'pointer' => '/data/attributes/items',
    ]);
});
