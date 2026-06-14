<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID v7 should be handled in model
            $table->uuid('session_id');
            $table->uuid('customer_id')->nullable();
            $table->bigInteger('subtotal_cents');
            $table->bigInteger('discount_cents');
            $table->bigInteger('tax_cents');
            $table->bigInteger('total_cents');
            $table->char('currency_code', 3);
            $table->string('status'); // 'completed', 'voided', 'held'
            $table->string('receipt_number', 50)->unique();
            $table->timestamp('synced_at')->nullable();
            $table->uuid('offline_uuid')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at');

            $table->foreign('session_id')->references('id')->on('pos_sessions');
            // Assuming customers table exists
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
    }
};
