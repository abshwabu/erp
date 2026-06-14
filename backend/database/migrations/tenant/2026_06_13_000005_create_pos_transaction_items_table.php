<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transaction_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->uuid('product_id');
            $table->uuid('variant_id')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->bigInteger('unit_price_cents');
            $table->bigInteger('discount_cents');
            $table->bigInteger('tax_cents');
            $table->bigInteger('total_cents');
            $table->string('lot_number')->nullable();
            $table->string('serial_number')->nullable();

            $table->foreign('transaction_id')->references('id')->on('pos_transactions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transaction_items');
    }
};
