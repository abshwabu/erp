<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles warehouse location management, stock receiving, and stock transfers', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Warehouse Test Tenant',
        'slug'   => 'warehousetest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Warehouse Admin',
            'email'     => 'warehouse@test.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $product = Product::create([
            'name' => 'Industrial Steel Pipe',
            'sku' => 'PIPE-STEEL-001',
            'type' => 'stockable',
            'status' => 'active',
            'cost_price' => 1200,
            'selling_price' => 2000,
        ]);

        return [
            'admin' => $admin,
            'product' => $product,
        ];
    });

    $loginResponse = $this->postJson('http://warehousetest.localhost/api/auth/login', [
        'email'    => 'warehouse@test.com',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Fetch locations (ensures default location is initialized)
    $listLocResponse = $this->withHeaders($headers)->getJson('http://warehousetest.localhost/api/warehouse/locations');
    $listLocResponse->assertStatus(200);
    $locations = $listLocResponse->json('data');
    expect($locations)->toHaveCount(1);
    $defaultLocationId = $locations[0]['id'];

    // 2. Create secondary location
    $createLocResponse = $this->withHeaders($headers)->postJson('http://warehousetest.localhost/api/warehouse/locations', [
        'name' => 'Staging Dock 2',
        'code' => 'STG-02',
        'type' => 'stage',
    ]);
    $createLocResponse->assertStatus(201);
    $stagingLocationId = $createLocResponse->json('data.id');
    expect($stagingLocationId)->not->toBeNull();

    // 3. Receive 50 units of product into default location
    $receiveResponse = $this->withHeaders($headers)->postJson('http://warehousetest.localhost/api/warehouse/receive', [
        'product_id' => $data['product']->id,
        'location_id' => $defaultLocationId,
        'quantity' => 50,
        'unit_cost' => 1200,
    ]);
    $receiveResponse->assertStatus(201);
    expect($receiveResponse->json('data.quantity'))->toBe(50);

    // Verify stock level in tenant DB
    $tenant->run(function () use ($data, $defaultLocationId) {
        $level = StockLevel::where('product_id', $data['product']->id)
            ->where('location_id', $defaultLocationId)
            ->first();
        expect($level)->not->toBeNull();
        expect((int) $level->quantity_on_hand)->toBe(50);
    });

    // 4. Transfer 20 units from default location to staging location
    $transferResponse = $this->withHeaders($headers)->postJson('http://warehousetest.localhost/api/warehouse/transfer', [
        'product_id' => $data['product']->id,
        'from_location_id' => $defaultLocationId,
        'to_location_id' => $stagingLocationId,
        'quantity' => 20,
    ]);
    $transferResponse->assertStatus(201);

    // Verify stock level after transfer
    $tenant->run(function () use ($data, $defaultLocationId, $stagingLocationId) {
        $sourceLevel = StockLevel::where('product_id', $data['product']->id)
            ->where('location_id', $defaultLocationId)
            ->first();
        $destLevel = StockLevel::where('product_id', $data['product']->id)
            ->where('location_id', $stagingLocationId)
            ->first();

        expect((int) $sourceLevel->quantity_on_hand)->toBe(30);
        expect((int) $destLevel->quantity_on_hand)->toBe(20);
    });

    // 5. Attempt transfer with insufficient stock (e.g. 100 units from source which only has 30)
    $badTransfer = $this->withHeaders($headers)->postJson('http://warehousetest.localhost/api/warehouse/transfer', [
        'product_id' => $data['product']->id,
        'from_location_id' => $defaultLocationId,
        'to_location_id' => $stagingLocationId,
        'quantity' => 100,
    ]);
    $badTransfer->assertStatus(422);

    // 6. Attempt transfer between identical locations
    $sameLocationTransfer = $this->withHeaders($headers)->postJson('http://warehousetest.localhost/api/warehouse/transfer', [
        'product_id' => $data['product']->id,
        'from_location_id' => $defaultLocationId,
        'to_location_id' => $defaultLocationId,
        'quantity' => 5,
    ]);
    $sameLocationTransfer->assertStatus(422);
});
