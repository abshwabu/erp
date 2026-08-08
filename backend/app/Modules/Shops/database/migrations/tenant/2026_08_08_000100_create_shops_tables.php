<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('stock_mode', 32); // own | shared_warehouse
            $table->uuid('warehouse_id');
            $table->uuid('stock_location_id');
            $table->jsonb('address')->nullable();
            $table->string('phone')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('stock_location_id')->references('id')->on('stock_locations');
            $table->index(['is_active']);
        });

        Schema::create('shop_user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('shop_id');
            $table->uuid('user_id');
            $table->string('role', 32)->default('keeper'); // keeper | manager
            $table->timestamps();

            $table->foreign('shop_id')->references('id')->on('shops')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['shop_id', 'user_id']);
        });

        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable()->after('location_id');
            $table->foreign('shop_id')->references('id')->on('shops')->nullOnDelete();
            $table->index('shop_id');
        });

        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->uuid('shop_id')->nullable()->after('terminal_id');
            $table->foreign('shop_id')->references('id')->on('shops')->nullOnDelete();
            $table->index('shop_id');
        });
    }

    public function down(): void
    {
        Schema::table('pos_sessions', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });

        Schema::table('pos_terminals', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropColumn('shop_id');
        });

        Schema::dropIfExists('shop_user');
        Schema::dropIfExists('shops');
    }
};
