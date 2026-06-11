<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code', 20)->unique();
            $table->jsonb('address')->nullable();
            $table->string('type', 20); // own, 3pl, virtual
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Stock Locations
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('parent_id')->nullable();
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 20); // receive, storage, pick, pack, stage, ship, damaged, quarantine
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('cascade');
        });

        // 3. Stock Levels
        Schema::create('stock_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('location_id');
            $table->bigInteger('quantity_on_hand')->default(0);
            $table->bigInteger('quantity_committed')->default(0);
            $table->bigInteger('quantity_on_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('cascade');

            $table->unique(['product_id', 'variant_id', 'location_id'], 'stock_levels_product_variant_location_unique');
        });

        // 4. Stock Movements
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->uuid('from_location_id')->nullable();
            $table->uuid('to_location_id')->nullable();
            $table->bigInteger('quantity');
            $table->string('type', 20); // receive, sale, return_in, return_out, transfer, adjustment, production_in, production_out, opening
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->string('lot_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->bigInteger('unit_cost')->default(0);
            $table->char('currency_code', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->uuid('user_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->onDelete('cascade');

            $table->foreign('from_location_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('cascade');

            $table->foreign('to_location_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        // 5. Lot Tracking
        Schema::create('lot_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('lot_number', 100);
            $table->date('expiry_date')->nullable();
            $table->date('received_date');
            $table->uuid('supplier_id')->nullable();
            $table->bigInteger('quantity_remaining');
            $table->uuid('location_id');
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('cascade');
        });

        // 6. Serial Numbers
        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('serial_number', 200)->unique();
            $table->string('status', 20); // in_stock, sold, returned, scrapped
            $table->uuid('location_id')->nullable();
            $table->uuid('customer_id')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('set null');
        });

        // 7. Reorder Settings
        Schema::create('reorder_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('location_id')->nullable();
            $table->bigInteger('min_quantity');
            $table->bigInteger('max_quantity');
            $table->bigInteger('reorder_quantity');
            $table->boolean('is_auto_reorder')->default(false);
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')
                ->on('stock_locations')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_settings');
        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('lot_tracking');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_levels');
        Schema::dropIfExists('stock_locations');
        Schema::dropIfExists('warehouses');
    }
};
