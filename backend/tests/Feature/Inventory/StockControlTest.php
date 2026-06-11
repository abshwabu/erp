<?php

declare(strict_types=1);

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use App\Modules\Inventory\Events\LowStockDetected;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Jobs\CheckReorderLevelsJob;
use App\Modules\Inventory\Models\LotTracking;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ReorderSetting;
use App\Modules\Inventory\Models\SerialNumber;
use App\Modules\Inventory\Models\StockLevel;
use App\Modules\Inventory\Models\StockLocation;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Warehouse;
use App\Modules\Inventory\Services\StockService;
use Database\Seeders\TenantRoleSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// ── Helper ─────────────────────────────────────────────────────────────────────

function makeStockUser(string $slugSuffix = ''): array
{
    $slug = strtolower('stk-' . str()->random(6) . $slugSuffix);

    $tenant = Tenant::create([
        'name'   => "Stock Tenant {$slug}",
        'slug'   => $slug,
        'status' => 'active',
    ]);

    [$user, $token] = $tenant->run(function () use ($tenant, $slug) {
        (new TenantRoleSeeder())->run();

        $user = User::create([
            'tenant_id' => $tenant->getKey(),
            'name'      => 'Stock Owner',
            'email'     => "owner@{$slug}.test",
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);

        $user->assignRole('Owner');

        $token = auth('api')->login($user);

        return [$user, $token];
    });

    return [$tenant, $slug, $token];
}

// ── Tests ────────────────────────────────────────────────────────────────────

it('receives stock and updates levels and tracking', function (): void {
    [$tenant, $slug, $token] = makeStockUser();

    $tenant->run(function () use ($token) {
        $product = Product::create([
            'name' => 'Test Widget',
            'sku' => 'WIDGET-01',
            'type' => 'stockable',
            'status' => 'active',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'track_lots' => true,
            'track_serial_numbers' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'own',
            'is_active' => true,
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-A1',
            'name' => 'Bin A1',
            'type' => 'storage',
            'is_active' => true,
        ]);

        $service = app(StockService::class);

        // Receive stock
        $movement = $service->receiveStock(
            product: $product,
            location: $location,
            qty: 5,
            unitCost: 1000,
            ref: ['type' => 'purchase_order', 'id' => str()->uuid()],
            lot: 'LOT-123',
            serial: 'SN-001',
            expiry: now()->addYear()
        );

        expect($movement)->toBeInstanceOf(StockMovement::class);
        expect($movement->quantity)->toBe(5);
        expect($movement->type)->toBe('receive');

        // Verify Stock Level updated via observer
        $level = StockLevel::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->first();

        expect($level)->not->toBeNull();
        expect($level->quantity_on_hand)->toBe(5);

        // Verify Lot Tracking
        $lot = LotTracking::where('product_id', $product->id)
            ->where('lot_number', 'LOT-123')
            ->first();

        expect($lot)->not->toBeNull();
        expect($lot->quantity_remaining)->toBe(5);

        // Verify Serial Number
        $serial = SerialNumber::where('product_id', $product->id)
            ->where('serial_number', 'SN-001')
            ->first();

        expect($serial)->not->toBeNull();
        expect($serial->status)->toBe('in_stock');
        expect($serial->location_id)->toBe($location->id);
    });
});

it('issues stock and validates sufficient quantity', function (): void {
    [$tenant, $slug, $token] = makeStockUser();

    $tenant->run(function () use ($token) {
        $product = Product::create([
            'name' => 'Test Widget',
            'sku' => 'WIDGET-02',
            'type' => 'stockable',
            'status' => 'active',
            'cost_price' => 1000,
            'selling_price' => 2000,
            'track_lots' => true,
            'track_serial_numbers' => true,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'own',
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-A1',
            'name' => 'Bin A1',
            'type' => 'storage',
        ]);

        $service = app(StockService::class);

        // Receive stock first
        $service->receiveStock(
            product: $product,
            location: $location,
            qty: 10,
            lot: 'LOT-999',
            serial: 'SN-999'
        );

        // Issue stock successfully
        $movement = $service->issueStock(
            product: $product,
            location: $location,
            qty: 4,
            ref: ['movement_type' => 'sale'],
            lot: 'LOT-999',
            serial: 'SN-999'
        );

        expect($movement->type)->toBe('sale');
        expect($movement->quantity)->toBe(4);

        $level = StockLevel::where('product_id', $product->id)->first();
        expect($level->quantity_on_hand)->toBe(6);

        // Verify Serial Number updated
        $serial = SerialNumber::where('product_id', $product->id)->first();
        expect($serial->status)->toBe('sold');
        expect($serial->location_id)->toBeNull();

        // Attempting to issue more than on hand should throw InsufficientStockException
        expect(fn () => $service->issueStock(
            product: $product,
            location: $location,
            qty: 10
        ))->toThrow(InsufficientStockException::class);
    });
});

it('transfers stock atomically between locations', function (): void {
    [$tenant, $slug, $token] = makeStockUser();

    $tenant->run(function () use ($token) {
        $product = Product::create([
            'name' => 'Test Widget',
            'sku' => 'WIDGET-03',
            'type' => 'stockable',
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'own',
        ]);

        $locA = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-A',
            'name' => 'Bin A',
            'type' => 'storage',
        ]);

        $locB = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-B',
            'name' => 'Bin B',
            'type' => 'storage',
        ]);

        $service = app(StockService::class);

        // Receive at locA
        $service->receiveStock($product, $locA, 10);

        // Transfer 4 units to locB
        $movements = $service->transferStock(
            product: $product,
            fromLocation: $locA,
            toLocation: $locB,
            qty: 4
        );

        expect($movements)->toBeArray();
        expect($movements[0]->type)->toBe('transfer');

        $levelA = StockLevel::where('product_id', $product->id)->where('location_id', $locA->id)->first();
        $levelB = StockLevel::where('product_id', $product->id)->where('location_id', $locB->id)->first();

        expect($levelA->quantity_on_hand)->toBe(6);
        expect($levelB->quantity_on_hand)->toBe(4);

        // Fail transfer due to insufficient stock
        expect(fn () => $service->transferStock(
            product: $product,
            fromLocation: $locA,
            toLocation: $locB,
            qty: 15
        ))->toThrow(InsufficientStockException::class);
    });
});

it('adjusts stock and calculates differences', function (): void {
    [$tenant, $slug, $token] = makeStockUser();

    $tenant->run(function () use ($token) {
        $product = Product::create([
            'name' => 'Test Widget',
            'sku' => 'WIDGET-04',
            'type' => 'stockable',
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'own',
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-A1',
            'name' => 'Bin A1',
            'type' => 'storage',
        ]);

        $service = app(StockService::class);

        // Adjust to 8 units (increase from 0)
        $m1 = $service->adjustStock($product, $location, 8, 'Initial count');
        expect($m1->quantity)->toBe(8);
        expect($m1->to_location_id)->toBe($location->id);
        expect($m1->from_location_id)->toBeNull();

        $level = StockLevel::where('product_id', $product->id)->first();
        expect($level->quantity_on_hand)->toBe(8);

        // Adjust down to 5 units (decrease of 3)
        $m2 = $service->adjustStock($product, $location, 5, 'Damaged items');
        expect($m2->quantity)->toBe(3);
        expect($m2->from_location_id)->toBe($location->id);
        expect($m2->to_location_id)->toBeNull();

        $level->refresh();
        expect($level->quantity_on_hand)->toBe(5);
    });
});

it('dispatches LowStockDetected event when checking reorder settings', function (): void {
    [$tenant, $slug, $token] = makeStockUser();

    $tenant->run(function () use ($token) {
        Event::fake([LowStockDetected::class]);

        $product = Product::create([
            'name' => 'Alert Widget',
            'sku' => 'WIDGET-ALERT',
            'type' => 'stockable',
            'status' => 'active',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'own',
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-A1',
            'name' => 'Bin A1',
            'type' => 'storage',
        ]);

        // Add 2 units
        app(StockService::class)->receiveStock($product, $location, 2);

        // Set reorder settings (min: 5, target: 15)
        ReorderSetting::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'min_quantity' => 5,
            'max_quantity' => 20,
            'reorder_quantity' => 15,
            'is_auto_reorder' => true,
        ]);

        // Run reorder levels job
        dispatch_sync(new CheckReorderLevelsJob());

        Event::assertDispatched(LowStockDetected::class, function ($event) use ($product, $location) {
            return $event->product->id === $product->id &&
                   $event->location->id === $location->id &&
                   $event->availableQuantity === 2 &&
                   $event->minQuantity === 5;
        });
    });
});

it('verifies stock controller REST endpoints', function (): void {
    [$tenant, $slug, $token] = makeStockUser();

    $tenant->run(function () use ($slug, $token) {
        $product = Product::create([
            'name' => 'Valued Item',
            'sku' => 'VAL-01',
            'type' => 'stockable',
            'status' => 'active',
            'cost_price' => 1500, // $15.00
            'selling_price' => 3000,
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
            'type' => 'own',
        ]);

        $location = StockLocation::create([
            'warehouse_id' => $warehouse->id,
            'code' => 'BIN-A1',
            'name' => 'Bin A1',
            'type' => 'storage',
        ]);

        // Setup some stock
        app(StockService::class)->receiveStock($product, $location, 10);

        ReorderSetting::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'min_quantity' => 15, // Low stock because min is 15 but we only have 10
            'max_quantity' => 30,
            'reorder_quantity' => 20,
        ]);
    });

    // 1. Index
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/stock")
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);

    // 2. Show product breakdown
    $product = $tenant->run(fn () => Product::first());
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/stock/{$product->id}")
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');

    // 3. Post Adjustment
    $location = $tenant->run(fn () => StockLocation::first());
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("http://{$slug}.localhost/api/inventory/stock/adjustments", [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 12,
            'reason' => 'Annual recount',
        ])
        ->assertStatus(200);

    // Verify adjust worked
    $level = $tenant->run(fn () => StockLevel::first());
    expect($level->quantity_on_hand)->toBe(12);

    // 4. Movements History
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/stock/movements")
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);

    // 5. Low Stock
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/stock/low")
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.available_quantity', 12)
        ->assertJsonPath('data.0.min_quantity', 15);

    // 6. Valuation
    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("http://{$slug}.localhost/api/inventory/stock/valuation")
        ->assertStatus(200)
        ->assertJsonPath('data.total_valuation', 12 * 1500); // 12 items * $15.00 cost price
});
