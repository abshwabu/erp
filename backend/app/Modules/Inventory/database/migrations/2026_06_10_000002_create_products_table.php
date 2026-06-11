<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('sku', 100);
            $table->string('type', 20); // stockable | consumable | service
            $table->string('status', 20)->default('active');
            $table->bigInteger('cost_price')->default(0);
            $table->bigInteger('selling_price')->default(0);
            $table->char('currency_code', 3)->default('USD');
            $table->uuid('tax_class_id')->nullable();
            $table->bigInteger('min_selling_price')->nullable();
            $table->bigInteger('max_selling_price')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->boolean('track_serial_numbers')->default(false);
            $table->boolean('track_lots')->default(false);
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                ->references('id')
                ->on('product_categories')
                ->onDelete('set null');
        });

        // Partial unique index on SKU (active products only).
        // Uses raw SQL for cross-DB compatibility; the application-level
        // unique validation in StoreProductRequest handles this for SQLite in tests.
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement(
                'CREATE UNIQUE INDEX products_sku_unique ON products (sku) WHERE deleted_at IS NULL'
            );
        } else {
            // SQLite: plain unique index — soft-deleted rows still hold the slot in tests,
            // but the SKU reuse test validates business logic at the application level.
            Schema::table('products', function (Blueprint $table) {
                $table->unique('sku', 'products_sku_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
