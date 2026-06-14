<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->string('method'); // enum-like: cash, card, mobile_money, etc.
            $table->bigInteger('amount_cents');
            $table->string('reference', 100)->nullable();
            $table->bigInteger('change_cents')->default(0);
            $table->timestamp('processed_at');

            $table->foreign('transaction_id')->references('id')->on('pos_transactions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payments');
    }
};
