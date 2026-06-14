<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('terminal_id');
            $table->uuid('cashier_id');
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->bigInteger('opening_cash_cents');
            $table->bigInteger('closing_cash_cents')->nullable();
            $table->bigInteger('expected_cash_cents')->default(0); // Updated by trigger
            $table->bigInteger('cash_variance_cents')->nullable();
            $table->string('status')->default('open'); // 'open', 'closed'
            $table->jsonb('z_report_data')->nullable();
            $table->timestamps();

            $table->foreign('terminal_id')->references('id')->on('pos_terminals');
            $table->foreign('cashier_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
