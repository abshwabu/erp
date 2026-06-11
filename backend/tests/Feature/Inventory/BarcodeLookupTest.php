<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductBarcode;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Hash;

// ── Helper ────────────────────────────────────────────────────────────────────

function makeBarcodeTenant(): array
{
    $slug = strtolower('bcd-' . str()->random(6));

    $tenant = Tenant::create([
        'name'   => "Barcode Tenant {$slug}",
        'slug'   => $slug,
        'status' => 'active',
    ]);

    $token = $tenant->run(function () use ($tenant, $slug) {
        (new TenantRoleSeeder())->run();

        $user = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Cashier',
            'email'     => "cashier@{$slug}.test",
            'password'  => Hash::make('secret'),
            'is_active' => true,
        ]);

        // Cashier has inventory.products.view via the Owner role for simplicity
        $user->assignRole('Owner');

        return auth('api')->login($user);
    });

    return [$tenant, $slug, $token];
}

// ─────────────────────────────────────────────────────────────────────────────
// Barcode Lookup Tests
// ─────────────────────────────────────────────────────────────────────────────

it('resolves a barcode to the correct product', function (): void {
    [$tenant, $slug, $token] = makeBarcodeTenant();

    $tenant->run(function () {
        $product = Product::create([
            'sku'           => 'SCAN-001',
            'name'          => 'Scanned Product',
            'type'          => 'stockable',
            'status'        => 'active',
            'cost_price'    => 500,
            'selling_price' => 999,
        ]);

        ProductBarcode::create([
            'product_id' => $product->id,
            'barcode'    => '9780201379624',
            'type'       => 'EAN13',
            'is_primary' => true,
        ]);
    });

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/barcodes/lookup?barcode=9780201379624");

    $response->assertOk()
        ->assertJsonPath('data.sku', 'SCAN-001')
        ->assertJsonPath('data.name', 'Scanned Product');
});

it('returns 404 for an unknown barcode', function (): void {
    [$tenant, $slug, $token] = makeBarcodeTenant();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/barcodes/lookup?barcode=0000000000000");

    $response->assertNotFound();
});

it('returns 422 when barcode query param is missing', function (): void {
    [$tenant, $slug, $token] = makeBarcodeTenant();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/barcodes/lookup");

    $response->assertStatus(422);
});

it('resolves barcode to a product with multiple barcodes and returns the correct one', function (): void {
    [$tenant, $slug, $token] = makeBarcodeTenant();

    $tenant->run(function () {
        $product = Product::create([
            'sku'           => 'MULTI-BC',
            'name'          => 'Multi-Barcode Product',
            'type'          => 'stockable',
            'status'        => 'active',
            'cost_price'    => 100,
            'selling_price' => 200,
        ]);

        ProductBarcode::create(['product_id' => $product->id, 'barcode' => 'BC-PRIM', 'type' => 'CODE128', 'is_primary' => true]);
        ProductBarcode::create(['product_id' => $product->id, 'barcode' => 'BC-ALT',  'type' => 'QR',      'is_primary' => false]);
    });

    // Both barcodes should resolve to the same product
    foreach (['BC-PRIM', 'BC-ALT'] as $barcode) {
        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("http://{$slug}.localhost/api/inventory/barcodes/lookup?barcode={$barcode}")
            ->assertOk()
            ->assertJsonPath('data.sku', 'MULTI-BC');
    }
});
