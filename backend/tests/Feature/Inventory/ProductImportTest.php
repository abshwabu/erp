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

// ─────────────────────────────────────────────────────────────────────────────
// Template Download & Spreadsheet Import Tests
// ─────────────────────────────────────────────────────────────────────────────

it('downloads spreadsheet template in XLSX format', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->get("http://{$slug}.localhost/api/inventory/products/template?format=xlsx");

    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'attachment; filename="inventory_import_template.xlsx"');
    expect($response->getContent())->not()->toBeEmpty();
});

it('downloads spreadsheet template in CSV format', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->get("http://{$slug}.localhost/api/inventory/products/template?format=csv");

    $response->assertStatus(200);
    $response->assertHeader('Content-Disposition', 'attachment; filename="inventory_import_template.csv"');
    $content = $response->getContent();
    expect($content)->toContain('sku,name,category,type,cost_price,selling_price');
    expect($content)->toContain('PRD-OIL-5W30');
});

it('imports products and initial stock from XLSX spreadsheet synchronously', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    $service = app(\App\Modules\Inventory\Services\InventorySpreadsheetService::class);
    $tpl = $service->generateTemplate('xlsx');

    $file = UploadedFile::fake()->createWithContent('inventory_upload.xlsx', $tpl['content']);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post(
            "http://{$slug}.localhost/api/inventory/products/import",
            [
                'file'            => $file,
                'sync'            => '1',
                'update_existing' => '1',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

    $response->assertStatus(200);
    expect($response->json('data.errors'))->toBe([]);
    $response->assertJsonPath('data.total_rows', 4);
    $response->assertJsonPath('data.created_count', 4);
    $response->assertJsonPath('data.failed_count', 0);

    $tenant->run(function () {
        // Verify product was created
        $product = Product::where('sku', 'PRD-OIL-5W30')->first();
        expect($product)->not()->toBeNull();
        expect($product->name)->toBe('Total Quartz 9000 5W-30 (4L)');
        expect($product->cost_price)->toBe(320000);
        expect($product->selling_price)->toBe(410000);

        // Verify stock level was initialized
        $level = \App\Modules\Inventory\Models\StockLevel::where('product_id', $product->id)->first();
        expect($level)->not()->toBeNull();
        expect($level->quantity_on_hand)->toBe(45);

        // Verify barcode was registered
        $barcode = \App\Modules\Inventory\Models\ProductBarcode::where('product_id', $product->id)->first();
        expect($barcode)->not()->toBeNull();
        expect($barcode->barcode)->toBe('600109283741');

        // Verify category was created
        expect(ProductCategory::where('name', 'Lubricants')->exists())->toBeTrue();
    });
});

it('handles product updates and row errors in synchronous spreadsheet import', function (): void {
    [$tenant, $slug, $token] = makeImportTenant();

    // Pre-create a product to update
    $tenant->run(fn () => Product::create([
        'sku'           => 'PRD-UPDATE-1',
        'name'          => 'Old Name',
        'type'          => 'stockable',
        'status'        => 'active',
        'cost_price'    => 1000,
        'selling_price' => 2000,
    ]));

    // CSV with: 1 update, 1 new, 1 invalid row (missing name)
    $csv = "sku,name,category,cost_price,selling_price,initial_stock\n"
         . "PRD-UPDATE-1,Updated Product Name,Upgraded Category,15.00,25.00,10\n"
         . "PRD-NEW-1,Brand New Product,New Category,5.00,10.00,50\n"
         . "PRD-INVALID,,Missing Name Category,5.00,10.00,5\n";

    $file = UploadedFile::fake()->createWithContent('import_test.csv', $csv);

    $response = $this
        ->withHeader('Authorization', "Bearer {$token}")
        ->post(
            "http://{$slug}.localhost/api/inventory/products/import",
            [
                'file'            => $file,
                'sync'            => '1',
                'update_existing' => '1',
            ],
            ['Content-Type' => 'multipart/form-data']
        );

    $response->assertStatus(200);
    $response->assertJsonPath('data.created_count', 1);
    $response->assertJsonPath('data.updated_count', 1);
    $response->assertJsonPath('data.failed_count', 1);

    $errors = $response->json('data.errors');
    expect($errors)->toHaveCount(1);
    expect($errors[0]['row'])->toBe(4);
    expect($errors[0]['error'])->toContain('Product Name is required');

    $tenant->run(function () {
        expect(Product::where('sku', 'PRD-UPDATE-1')->value('name'))->toBe('Updated Product Name');
        expect(Product::where('sku', 'PRD-NEW-1')->value('name'))->toBe('Brand New Product');
    });
});

