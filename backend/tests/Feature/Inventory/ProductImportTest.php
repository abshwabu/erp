<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Jobs\ImportProductsJob;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

// ── Helper ────────────────────────────────────────────────────────────────────

function makeImportTenant(): array
{
    $slug = strtolower('imp-' . str()->random(6));

    $tenant = Tenant::create([
        'name'   => "Import Tenant {$slug}",
        'slug'   => $slug,
        'status' => 'active',
    ]);

    $token = $tenant->run(function () use ($tenant, $slug) {
        (new TenantRoleSeeder())->run();

        $user = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Import Owner',
            'email'     => "owner@{$slug}.test",
            'password'  => Hash::make('secret'),
            'is_active' => true,
        ]);

        $user->assignRole('Owner');

        return auth('api')->login($user);
    });

    return [$tenant, $slug, $token];
}

/**
 * Build a minimal valid CSV string for product import.
 *
 * @param array<array<string,string>> $rows
 */
function buildCsv(array $rows): string
{
    $header = 'sku,name,type,status,cost_price,selling_price,category,description,has_variants';
    $lines  = [$header];

    foreach ($rows as $row) {
        $lines[] = implode(',', [
            $row['sku']           ?? '',
            $row['name']          ?? '',
            $row['type']          ?? 'stockable',
            $row['status']        ?? 'active',
            $row['cost_price']    ?? '0',
            $row['selling_price'] ?? '0',
            $row['category']      ?? '',
            $row['description']   ?? '',
            $row['has_variants']  ?? 'false',
        ]);
    }

    return implode("\n", $lines);
}

// ─────────────────────────────────────────────────────────────────────────────
// Upload Endpoint Tests
// ─────────────────────────────────────────────────────────────────────────────

it('dispatches ImportProductsJob when a valid CSV is uploaded', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    Bus::fake();
    Storage::fake('local');

    $csv  = buildCsv([['sku' => 'IMP-001', 'name' => 'Imported Product']]);
    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post(
            "http://{$slug}.localhost/api/inventory/products/import",
            ['file' => $file],
            ['Content-Type' => 'multipart/form-data']
        );

    $response->assertStatus(202);
    Bus::assertDispatched(ImportProductsJob::class);
});

it('rejects import when no file is uploaded', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->postJson("http://{$slug}.localhost/api/inventory/products/import");

    $response->assertStatus(422);
});

it('rejects import for non-CSV file types', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    Storage::fake('local');

    $file = UploadedFile::fake()->create('products.pdf', 100, 'application/pdf');

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post(
            "http://{$slug}.localhost/api/inventory/products/import",
            ['file' => $file],
            ['Content-Type' => 'multipart/form-data']
        );

    $response->assertStatus(422);
});

// ─────────────────────────────────────────────────────────────────────────────
// Job Execution Tests
// ─────────────────────────────────────────────────────────────────────────────

it('ImportProductsJob creates products from a CSV file', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    Storage::fake('local');

    $csv = buildCsv([
        ['sku' => 'JOB-001', 'name' => 'Job Product One', 'category' => 'Electronics', 'cost_price' => '10.50', 'selling_price' => '19.99'],
        ['sku' => 'JOB-002', 'name' => 'Job Product Two', 'type' => 'service'],
    ]);

    $path = 'imports/products/test-import.csv';
    $tenant->run(fn () => Storage::put($path, $csv));

    // Run the job synchronously inside the tenant context
    $tenant->run(fn () => (new ImportProductsJob($path))->handle());

    $tenant->run(function () {
        expect(Product::where('sku', 'JOB-001')->exists())->toBeTrue();
        expect(Product::where('sku', 'JOB-002')->exists())->toBeTrue();

        // Category should be created automatically
        expect(ProductCategory::where('slug', 'electronics')->exists())->toBeTrue();

        // Prices stored in cents
        $product = Product::where('sku', 'JOB-001')->first();
        expect($product->cost_price)->toBe(1050);
        expect($product->selling_price)->toBe(1999);
    });
});

it('ImportProductsJob upserts existing products without creating duplicates', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    Storage::fake('local');

    // Pre-create the product
    $tenant->run(fn () => Product::create([
        'sku'           => 'UPSERT-001',
        'name'          => 'Original Name',
        'type'          => 'stockable',
        'status'        => 'active',
        'cost_price'    => 500,
        'selling_price' => 1000,
    ]));

    $csv  = buildCsv([['sku' => 'UPSERT-001', 'name' => 'Updated Name', 'selling_price' => '15.00']]);
    $path = 'imports/products/upsert-test.csv';
    $tenant->run(fn () => Storage::put($path, $csv));

    $tenant->run(fn () => (new ImportProductsJob($path))->handle());

    $tenant->run(function () {
        expect(Product::where('sku', 'UPSERT-001')->count())->toBe(1);
        expect(Product::where('sku', 'UPSERT-001')->value('name'))->toBe('Updated Name');
        expect(Product::where('sku', 'UPSERT-001')->value('selling_price'))->toBe(1500);
    });
});

it('ImportProductsJob skips malformed rows and continues processing', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    Storage::fake('local');

    // Row 2 is malformed (fewer columns), Row 3 is valid
    $csv  = "sku,name,type,status,cost_price,selling_price,category,description,has_variants\n"
          . "BAD-ROW\n"  // malformed
          . "GOOD-001,Good Product,stockable,active,1.00,2.00,,,false\n";

    $path = 'imports/products/malformed-test.csv';
    $tenant->run(fn () => Storage::put($path, $csv));

    // Should not throw
    $tenant->run(fn () => (new ImportProductsJob($path))->handle());

    $tenant->run(function () {
        expect(Product::where('sku', 'GOOD-001')->exists())->toBeTrue();
    });
});

it('ImportProductsJob deletes the file after processing', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    Storage::fake('local');

    $csv  = buildCsv([['sku' => 'CLEAN-001', 'name' => 'Cleanup Test']]);
    $path = 'imports/products/cleanup-test.csv';
    $tenant->run(fn () => Storage::put($path, $csv));

    $tenant->run(fn () => expect(Storage::exists($path))->toBeTrue());

    $tenant->run(fn () => (new ImportProductsJob($path))->handle());

    $tenant->run(fn () => expect(Storage::exists($path))->toBeFalse());
});
