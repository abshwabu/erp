<?php

declare(strict_types=1);

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
        if (!Schema::hasTable('finance_categories')) {
            Schema::create('finance_categories', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->enum('type', ['income', 'expense', 'both'])->default('both');
                $table->string('color', 30)->nullable();
                $table->text('description')->nullable();
                $table->uuid('account_id')->nullable();
                $table->boolean('is_system')->default(false);
                $table->timestamps();

                $table->foreign('account_id')->references('id')->on('accounting_accounts')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('finance_transactions')) {
            Schema::create('finance_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('transaction_number', 50)->unique();
                $table->enum('type', ['income', 'expense']);
                $table->uuid('category_id')->nullable();
                $table->uuid('account_id')->nullable(); // Target Revenue or Expense account
                $table->uuid('payment_account_id')->nullable(); // Cash or Bank account
                $table->uuid('journal_id')->nullable(); // Linked Accounting Journal entry
                $table->bigInteger('amount_cents');
                $table->char('currency_code', 3)->default('USD');
                $table->date('transaction_date');
                $table->string('payment_method', 50)->default('cash');
                $table->string('reference', 100)->nullable();
                $table->string('payee_payer')->nullable();
                $table->text('description')->nullable();
                $table->enum('status', ['completed', 'pending', 'cancelled'])->default('completed');
                $table->text('receipt_url')->nullable();
                $table->uuid('created_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('category_id')->references('id')->on('finance_categories')->onDelete('set null');
                $table->foreign('account_id')->references('id')->on('accounting_accounts')->onDelete('set null');
                $table->foreign('payment_account_id')->references('id')->on('accounting_accounts')->onDelete('set null');
                $table->foreign('journal_id')->references('id')->on('accounting_journals')->onDelete('set null');
                $table->foreign('created_by_id')->references('id')->on('users')->onDelete('set null');

                $table->index(['type', 'transaction_date']);
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('finance_categories');
    }
};
