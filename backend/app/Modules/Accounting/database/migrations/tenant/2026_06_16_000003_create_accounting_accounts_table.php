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
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->uuid('account_type_id');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->char('currency_code', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_account')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('accounting_accounts')->onDelete('set null');
            $table->foreign('account_type_id')->references('id')->on('accounting_account_types');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
