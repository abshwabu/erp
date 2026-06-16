<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_account_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Asset, Liability, Equity, Revenue, Expense, COGS
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->string('report_section');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_account_types');
    }
};
