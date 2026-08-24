<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('handles ecommerce lifecycle: channel setup, order sync, pagination, and sync updates', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Ecommerce Test Tenant',
        'slug'   => 'ecomtest',
        'status' => 'active',
    ]);

    $data = $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $admin = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Ecom Admin',
            'email'     => 'admin@ecom.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('Admin');

        return ['admin' => $admin];
    });

    $loginResponse = $this->postJson('http://ecomtest.localhost/api/auth/login', [
        'email'    => 'admin@ecom.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    // 1. Create an Ecommerce Channel
    $createResponse = $this->withHeaders($headers)->postJson('http://ecomtest.localhost/api/ecommerce/channels', [
        'name'           => 'Shopify Main Store',
        'platform'       => 'shopify',
        'store_url'      => 'https://my-store.myshopify.com',
        'api_key'        => 'shpat_1234567890abcdef',
        'api_secret'     => 'shpss_secretkey123',
        'webhook_secret' => 'whsec_987654321',
        'is_active'      => true,
    ]);
    $createResponse->assertStatus(201);
    $channelId = $createResponse->json('data.id');
    expect($channelId)->not->toBeNull();
    expect($createResponse->json('data.platform'))->toBe('shopify');
    expect($createResponse->json('data.api_secret'))->toBeNull(); // hidden

    // 2. Sync an Order from the channel
    $orderResponse = $this->withHeaders($headers)->postJson("http://ecomtest.localhost/api/ecommerce/channels/{$channelId}/orders", [
        'external_order_id'  => 'SHOPIFY-90210',
        'order_number'       => '#1001',
        'customer_name'      => 'Alice Smith',
        'customer_email'     => 'alice@example.com',
        'total_cents'        => 12999, // $129.99
        'currency'           => 'USD',
        'payment_status'     => 'paid',
        'fulfillment_status' => 'unfulfilled',
        'items'              => [
            ['name' => 'Cotton Hoodie', 'quantity' => 1, 'price_cents' => 12999],
        ],
    ]);
    $orderResponse->assertStatus(201);
    $orderId = $orderResponse->json('data.id');
    expect($orderId)->not->toBeNull();
    expect($orderResponse->json('data.order_number'))->toBe('#1001');
    expect($orderResponse->json('data.total_cents'))->toBe(12999);

    // 3. Trigger manual sync
    $syncResponse = $this->withHeaders($headers)->postJson("http://ecomtest.localhost/api/ecommerce/channels/{$channelId}/sync");
    $syncResponse->assertStatus(200);
    expect($syncResponse->json('data.last_sync_at'))->not->toBeNull();

    // 4. List channels
    $listChanResponse = $this->withHeaders($headers)->getJson('http://ecomtest.localhost/api/ecommerce/channels');
    $listChanResponse->assertStatus(200);
    expect($listChanResponse->json('data'))->toHaveCount(1);
    expect($listChanResponse->json('data.0.orders_count'))->toBe(1);

    // 5. List orders
    $listOrderResponse = $this->withHeaders($headers)->getJson('http://ecomtest.localhost/api/ecommerce/orders');
    $listOrderResponse->assertStatus(200);
    expect($listOrderResponse->json('data.data'))->toHaveCount(1);
    expect($listOrderResponse->json('data.data.0.external_order_id'))->toBe('SHOPIFY-90210');

    // 6. Delete channel
    $deleteResponse = $this->withHeaders($headers)->deleteJson("http://ecomtest.localhost/api/ecommerce/channels/{$channelId}");
    $deleteResponse->assertStatus(204);

    $listChanAfter = $this->withHeaders($headers)->getJson('http://ecomtest.localhost/api/ecommerce/channels');
    $listChanAfter->assertStatus(200);
    expect($listChanAfter->json('data'))->toHaveCount(0);
});
