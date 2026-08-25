<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTerminal;
use App\Modules\POS\Models\POSTransaction;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

it('fetches consolidated real-time metrics for dashboard', function (): void {
    $tenant = Tenant::create([
        'name'   => 'Live Dashboard Corp',
        'slug'   => 'dashboardcorp',
        'status' => 'active',
    ]);

    $tenant->run(function () use ($tenant) {
        (new TenantRoleSeeder())->run();

        $owner = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Dashboard Owner',
            'email'     => 'owner@dash.test',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);
        $owner->assignRole('Owner');

        // Create sample product
        Product::create([
            'name'          => 'Apex Precision Monitor',
            'sku'           => 'MON-4K',
            'type'          => 'stockable',
            'selling_price' => 39999,
            'cost_price'    => 25000,
            'status'        => 'active',
        ]);

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

        // Create sample POS session
        $session = POSSession::create([
            'terminal_id'        => $terminal->id,
            'cashier_id'         => $owner->id,
            'opening_cash_cents' => 10000,
            'status'             => 'open',
            'opened_at'          => now(),
        ]);

        POSTransaction::create([
            'session_id'     => $session->id,
            'total_cents'    => 39999,
            'subtotal_cents' => 39999,
            'discount_cents' => 0,
            'tax_cents'      => 0,
            'currency_code'  => 'USD',
            'status'         => 'completed',
            'receipt_number' => '#REC-1001',
            'created_at'     => now(),
        ]);
    });

    $loginResponse = $this->postJson('http://dashboardcorp.localhost/api/auth/login', [
        'email'    => 'owner@dash.test',
        'password' => 'password123',
    ]);
    $token = $loginResponse->json('access_token');
    $headers = ['Authorization' => "Bearer {$token}"];

    $response = $this->withHeaders($headers)->getJson('http://dashboardcorp.localhost/api/core/dashboard');
    $response->assertStatus(200);

    expect($response->json('data.financial.monthly_revenue_cents'))->toBe(39999);
    expect($response->json('data.financial.today_orders_count'))->toBe(1);
    expect($response->json('data.inventory.total_products'))->toBe(1);
    expect($response->json('data.operations.open_pos_sessions'))->toBe(1);
    expect($response->json('data.operations.recent_orders.0.number'))->toBe('#REC-1001');
});
