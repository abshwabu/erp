<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles assets lifecycle: registration, straight-line depreciation schedule, updates, and deletion', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Assets Test Tenant',
        'slug'   => 'asttest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $accountant = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Asset Accountant',
            'email'     => 'accountant@ast.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $accountant->assignRole('Owner');

        $driver = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Delivery Driver',
            'email'     => 'driver@ast.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $driver->assignRole('Read Only');

        return [
            'accountant' => $accountant,
            'driver'     => $driver,
        ];
    });

    $loginResponse = $this->postJson('http://asttest.localhost/api/auth/login', [
        'email'    => 'accountant@ast.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Register an Asset
    $createResponse = $this->withHeaders($headers)->postJson('http://asttest.localhost/api/assets', [
        'name'                => 'Delivery Van Mercedes Sprinter',
        'category'            => 'vehicle',
        'serial_number'       => 'VIN-9876543210',
        'purchase_date'       => '2026-01-15',
        'purchase_cost_cents' => 5000000, // $50,000.00
        'salvage_value_cents' => 500000,  // $5,000.00
        'useful_life_years'   => 5,
        'depreciation_method' => 'straight_line',
        'status'              => 'active',
        'assigned_to'         => $data['driver']->id,
        'notes'               => 'Primary logistics delivery vehicle',
    ]);
    $createResponse->assertStatus(201);
    $assetId = $createResponse->json('data.id');
    expect($assetId)->not->toBeNull();
    expect($createResponse->json('data.asset_tag'))->toBe('AST-00001');
    expect($createResponse->json('data.purchase_cost_cents'))->toBe(5000000);

    // 2. Generate Depreciation Schedule
    // Depreciable base = 50,000 - 5,000 = 45,000. Over 5 years = $9,000/yr = 900,000 cents/yr
    $schedResponse = $this->withHeaders($headers)->postJson("http://asttest.localhost/api/assets/{$assetId}/depreciation-schedule");
    $schedResponse->assertStatus(200);
    $depreciations = $schedResponse->json('data.depreciations');
    expect($depreciations)->toHaveCount(5);
    expect($depreciations[0]['depreciation_amount_cents'])->toBe(900000);
    expect($depreciations[0]['accumulated_depreciation_cents'])->toBe(900000);
    expect($depreciations[0]['book_value_cents'])->toBe(4100000);
    expect($depreciations[4]['accumulated_depreciation_cents'])->toBe(4500000);
    expect($depreciations[4]['book_value_cents'])->toBe(500000); // salvage value

    // 3. Update asset status
    $updateResponse = $this->withHeaders($headers)->putJson("http://asttest.localhost/api/assets/{$assetId}", [
        'status' => 'maintenance',
    ]);
    $updateResponse->assertStatus(200);
    expect($updateResponse->json('data.status'))->toBe('maintenance');

    // 4. Retrieve asset specification
    $detailResponse = $this->withHeaders($headers)->getJson("http://asttest.localhost/api/assets/{$assetId}");
    $detailResponse->assertStatus(200);
    expect($detailResponse->json('data.name'))->toBe('Delivery Van Mercedes Sprinter');
    expect($detailResponse->json('data.assignee.id'))->toBe($data['driver']->id);

    // 5. List assets
    $listResponse = $this->withHeaders($headers)->getJson('http://asttest.localhost/api/assets');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data.data'))->toHaveCount(1);

    // 6. Delete asset
    $deleteResponse = $this->withHeaders($headers)->deleteJson("http://asttest.localhost/api/assets/{$assetId}");
    $deleteResponse->assertStatus(204);

    $listAfterDelete = $this->withHeaders($headers)->getJson('http://asttest.localhost/api/assets');
    $listAfterDelete->assertStatus(200);
    expect($listAfterDelete->json('data.data'))->toHaveCount(0);
});
