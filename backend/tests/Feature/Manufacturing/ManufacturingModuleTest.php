<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles manufacturing workflows: BOMs, lines, activation, and work order execution', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Manufacturing Test Tenant',
        'slug'   => 'mfgtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $owner = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Plant Manager',
            'email'     => 'manager@mfg.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $owner->assignRole('Owner');

        $category = ProductCategory::create([
            'name' => 'Assembly',
        ]);

        $finishedGood = Product::create([
            'category_id'   => $category->id,
            'name'          => 'Office Desk',
            'sku'           => 'DESK-01',
            'type'          => 'stockable',
            'status'        => 'active',
            'selling_price' => 15000,
            'cost_price'    => 8000,
        ]);

        $materialWood = Product::create([
            'category_id'   => $category->id,
            'name'          => 'Wood Panel',
            'sku'           => 'WOOD-01',
            'type'          => 'stockable',
            'status'        => 'active',
            'selling_price' => 2000,
            'cost_price'    => 1000,
        ]);

        $materialScrews = Product::create([
            'category_id'   => $category->id,
            'name'          => 'Steel Screws Pack',
            'sku'           => 'SCREW-01',
            'type'          => 'stockable',
            'status'        => 'active',
            'selling_price' => 500,
            'cost_price'    => 200,
        ]);

        return [
            'owner'          => $owner,
            'finishedGood'   => $finishedGood,
            'materialWood'   => $materialWood,
            'materialScrews' => $materialScrews,
        ];
    });

    $loginResponse = $this->postJson('http://mfgtest.localhost/api/auth/login', [
        'email'    => 'manager@mfg.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a Bill of Materials
    $bomResponse = $this->withHeaders($headers)->postJson('http://mfgtest.localhost/api/manufacturing/boms', [
        'product_id'      => $data['finishedGood']->id,
        'name'            => 'Standard Desk Assembly BOM',
        'description'     => 'Standard bill of materials for 1 office desk',
        'output_quantity' => 1,
        'lines'           => [
            [
                'material_id' => $data['materialWood']->id,
                'quantity'    => 2,
                'unit'        => 'panels',
            ],
            [
                'material_id' => $data['materialScrews']->id,
                'quantity'    => 1,
                'unit'        => 'pack',
            ],
        ],
    ]);
    $bomResponse->assertStatus(201);
    $bomId = $bomResponse->json('data.id');
    expect($bomId)->not->toBeNull();
    expect($bomResponse->json('data.status'))->toBe('draft');
    expect($bomResponse->json('data.lines'))->toHaveCount(2);

    // 2. Activate BOM
    $activateResponse = $this->withHeaders($headers)->postJson("http://mfgtest.localhost/api/manufacturing/boms/{$bomId}/activate");
    $activateResponse->assertStatus(200);
    expect($activateResponse->json('data.status'))->toBe('active');

    // 3. Create a Work Order
    $woResponse = $this->withHeaders($headers)->postJson('http://mfgtest.localhost/api/manufacturing/work-orders', [
        'bom_id'        => $bomId,
        'quantity'      => 10,
        'priority'      => 'high',
        'planned_start' => '2026-09-01',
        'planned_end'   => '2026-09-05',
        'notes'         => 'Batch run of 10 desks',
    ]);
    $woResponse->assertStatus(201);
    $woId = $woResponse->json('data.id');
    expect($woId)->not->toBeNull();
    expect($woResponse->json('data.number'))->toBe('WO-00001');
    expect($woResponse->json('data.status'))->toBe('draft');
    expect($woResponse->json('data.quantity'))->toBe(10);

    // 4. Start Work Order
    $startResponse = $this->withHeaders($headers)->postJson("http://mfgtest.localhost/api/manufacturing/work-orders/{$woId}/start");
    $startResponse->assertStatus(200);
    expect($startResponse->json('data.status'))->toBe('in_progress');
    expect($startResponse->json('data.started_at'))->not->toBeNull();

    // 5. Complete Work Order
    $completeResponse = $this->withHeaders($headers)->postJson("http://mfgtest.localhost/api/manufacturing/work-orders/{$woId}/complete");
    $completeResponse->assertStatus(200);
    expect($completeResponse->json('data.status'))->toBe('completed');
    expect($completeResponse->json('data.completed_at'))->not->toBeNull();

    // 6. List Work Orders and verify pagination/structure
    $listResponse = $this->withHeaders($headers)->getJson('http://mfgtest.localhost/api/manufacturing/work-orders');
    $listResponse->assertStatus(200);
    $items = $listResponse->json('data.data') ?? $listResponse->json('data');
    expect($items)->toHaveCount(1);
    expect($items[0]['number'])->toBe('WO-00001');
});
