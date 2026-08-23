<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Shops\Models\Shop;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles shops lifecycle: creating own-stock shops, keeper assignments, and stock viewing', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Retail Express Tenant',
        'slug'   => 'retailtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Retail Admin',
            'email'     => 'admin@retail.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        $keeper = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Sam Keeper',
            'email'     => 'sam@retail.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $keeper->assignRole('Shop Keeper');

        $category = ProductCategory::create([
            'name' => 'General Merchandise',
        ]);

        $product = Product::create([
            'category_id'   => $category->id,
            'name'          => 'Wireless Mouse',
            'sku'           => 'W-MOUSE-01',
            'type'          => 'stockable',
            'status'        => 'active',
            'selling_price' => 2500,
            'cost_price'    => 1200,
        ]);

        return [
            'admin'    => $admin,
            'keeper'   => $keeper,
            'product'  => $product,
        ];
    });

    $loginResponse = $this->postJson('http://retailtest.localhost/api/auth/login', [
        'email'    => 'admin@retail.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create a new shop with dedicated stock (stock_mode = own)
    $createResponse = $this->withHeaders($headers)->postJson('http://retailtest.localhost/api/shops', [
        'name'       => 'Downtown Flagship',
        'code'       => 'SHOP-DT-01',
        'stock_mode' => 'own',
        'phone'      => '+1-555-0199',
        'notes'      => 'Main store in downtown area',
    ]);
    $createResponse->assertStatus(201);
    $shopId = $createResponse->json('data.id');
    expect($shopId)->not->toBeNull();
    expect($createResponse->json('data.name'))->toBe('Downtown Flagship');
    expect($createResponse->json('data.stock_mode'))->toBe('own');
    expect($createResponse->json('data.warehouse_id'))->not->toBeNull();
    expect($createResponse->json('data.stock_location_id'))->not->toBeNull();

    // 2. List shops
    $listResponse = $this->withHeaders($headers)->getJson('http://retailtest.localhost/api/shops');
    $listResponse->assertStatus(200);
    expect($listResponse->json('data'))->toHaveCount(1);

    // 3. Assign shop keeper
    $syncKeepersResponse = $this->withHeaders($headers)->putJson("http://retailtest.localhost/api/shops/{$shopId}/keepers", [
        'keepers' => [['user_id' => $data['keeper']->id, 'role' => 'keeper']],
    ]);
    $syncKeepersResponse->assertStatus(200);

    // 4. Check keepers endpoint
    $keepersResponse = $this->withHeaders($headers)->getJson("http://retailtest.localhost/api/shops/{$shopId}/keepers");
    $keepersResponse->assertStatus(200);
    expect($keepersResponse->json('data'))->toHaveCount(1);
    expect($keepersResponse->json('data.0.id'))->toBe($data['keeper']->id);

    // 5. Adjust shop stock
    $adjustResponse = $this->withHeaders($headers)->postJson("http://retailtest.localhost/api/shops/{$shopId}/stock/adjust", [
        'product_id' => $data['product']->id,
        'quantity'   => 50,
        'type'       => 'add',
        'notes'      => 'Initial stock adjustment for downtown shop',
    ]);
    $adjustResponse->assertStatus(200);

    // 6. View shop stock
    $stockResponse = $this->withHeaders($headers)->getJson("http://retailtest.localhost/api/shops/{$shopId}/stock");
    $stockResponse->assertStatus(200);
    $items = $stockResponse->json('data');
    expect($items)->toHaveCount(1);
    expect($items[0]['product_id'])->toBe($data['product']->id);
    expect($items[0]['quantity_on_hand'])->toBe(50);
});
