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
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 100);
            $table->string('description');
            $table->date('journal_date');
            $table->uuid('period_id');
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->string('source_type', 50)->nullable();
            $table->uuid('source_id')->nullable();
            $table->uuid('reversal_of_id')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->uuid('posted_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('period_id')->references('id')->on('accounting_fiscal_periods');
            // Assuming users table uses UUIDs as well
            $table->foreign('posted_by_id')->references('id')->on('users');
        });

        Schema::table('accounting_journals', function (Blueprint $table) {
            $table->foreign('reversal_of_id')->references('id')->on('accounting_journals');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_journals');
    }
};
