<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Procurement\Models\PurchaseOrder;
use App\Modules\Procurement\Models\Supplier;
use App\Modules\Inventory\Models\Warehouse;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles procurement workflows: suppliers, purchase orders, and stock receiving', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Procurement Test Tenant',
        'slug'   => 'procurementtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Procurement Admin',
            'email'     => 'procurement@test.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $warehouse = Warehouse::create([
            'name'      => 'Central Warehouse',
            'code'      => 'WH-CENTRAL',
            'type'      => 'own',
            'is_active' => true,
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'name'         => 'Main Receiving Warehouse',
            'code'         => 'MRW-01',
            'type'         => 'storage',
            'is_active'    => true,
        ]);

        $product = Product::create([
            'name'          => 'Raw Aluminum Ingot',
            'sku'           => 'RAW-ALUM-001',
            'type'          => 'stockable',
            'status'        => 'active',
            'cost_price'    => 5000,
            'selling_price' => 8000,
        ]);

        return [
            'admin'    => $admin,
            'location' => $location,
            'product'  => $product,
        ];
    });

    $loginResponse = $this->postJson('http://procurementtest.localhost/api/auth/login', [
        'email'    => 'procurement@test.com',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a supplier
    $supplierResponse = $this->withHeaders($headers)->postJson('http://procurementtest.localhost/api/procurement/suppliers', [
        'name'  => 'Acme Raw Materials Ltd',
        'email' => 'orders@acme-materials.test',
        'phone' => '+1-555-0199',
    ]);
    $supplierResponse->assertStatus(201);
    $supplierId = $supplierResponse->json('data.id');
    expect($supplierId)->not->toBeNull();
    expect($supplierResponse->json('data.name'))->toBe('Acme Raw Materials Ltd');

    // List suppliers
    $listSuppliersResponse = $this->withHeaders($headers)->getJson('http://procurementtest.localhost/api/procurement/suppliers');
    $listSuppliersResponse->assertStatus(200);
    expect($listSuppliersResponse->json('data'))->toHaveCount(1);

    // 2. Create a purchase order
    $orderDate = now()->toDateString();
    $createPoResponse = $this->withHeaders($headers)->postJson('http://procurementtest.localhost/api/procurement/purchase-orders', [
        'supplier_id' => $supplierId,
        'order_date'  => $orderDate,
        'status'      => 'ordered',
        'lines'       => [
            [
                'product_id'      => $data['product']->id,
                'description'     => '100 units of Raw Aluminum Ingot',
                'quantity'        => 100,
                'unit_cost_cents' => 4500, // 45.00 each => 450,000 cents total
            ],
            [
                'product_id'      => null,
                'description'     => 'Freight / Delivery surcharge',
                'quantity'        => 1,
                'unit_cost_cents' => 5000, // 50.00 => 5,000 cents
            ],
        ],
    ]);
    $createPoResponse->assertStatus(201);
    $poData = $createPoResponse->json('data');
    $poId = $poData['id'];

    expect($poData['number'])->toBe('PO-00001');
    expect($poData['status'])->toBe('ordered');
    expect($poData['total_cents'])->toBe(455000);
    expect($poData['lines'])->toHaveCount(2);
    expect((float) $poData['lines'][0]['received_quantity'])->toBe(0.0);

    // List purchase orders
    $listPoResponse = $this->withHeaders($headers)->getJson('http://procurementtest.localhost/api/procurement/purchase-orders');
    $listPoResponse->assertStatus(200);
    expect($listPoResponse->json('data'))->toHaveCount(1);

    // 3. Receive the purchase order into stock location
    $receiveResponse = $this->withHeaders($headers)->postJson("http://procurementtest.localhost/api/procurement/purchase-orders/{$poId}/receive", [
        'location_id' => $data['location']->id,
    ]);
    $receiveResponse->assertStatus(200);
    expect($receiveResponse->json('data.status'))->toBe('received');
    expect((float) $receiveResponse->json('data.lines.0.received_quantity'))->toBe(100.0);

    // Verify stock levels were created and updated in the inventory tables
    $tenant->run(function () use ($data) {
        $level = StockLevel::where('product_id', $data['product']->id)
            ->where('location_id', $data['location']->id)
            ->first();

        expect($level)->not->toBeNull();
        expect((int) $level->quantity_on_hand)->toBe(100);
    });

    // 4. Attempt to receive an already received PO (should be rejected with 422)
    $duplicateReceiveResponse = $this->withHeaders($headers)->postJson("http://procurementtest.localhost/api/procurement/purchase-orders/{$poId}/receive", [
        'location_id' => $data['location']->id,
    ]);
    $duplicateReceiveResponse->assertStatus(422);
});
