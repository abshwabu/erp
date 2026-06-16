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
        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID v7 if supported by driver, otherwise default UUID
            $table->uuid('journal_id');
            $table->uuid('account_id');
            $table->bigInteger('debit_cents')->default(0);
            $table->bigInteger('credit_cents')->default(0);
            $table->char('currency_code', 3)->default('USD');
            $table->bigInteger('base_debit_cents')->default(0);
            $table->bigInteger('base_credit_cents')->default(0);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->text('description')->nullable();
            $table->uuid('cost_center_id')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('employee_id')->nullable();
            $table->timestamps();

            $table->foreign('journal_id')->references('id')->on('accounting_journals')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounting_accounts');
            // Cost center and employee FKs can be added later if they exist in those modules
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
    }
};
