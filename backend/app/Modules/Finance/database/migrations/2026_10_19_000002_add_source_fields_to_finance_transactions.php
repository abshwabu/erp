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
        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('finance_transactions', 'source_type')) {
                    $table->string('source_type', 50)->nullable()->index()->after('status');
                }
                if (!Schema::hasColumn('finance_transactions', 'source_id')) {
                    $table->uuid('source_id')->nullable()->index()->after('source_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('finance_transactions', 'source_id')) {
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('finance_transactions', 'source_type')) {
                    $table->dropColumn('source_type');
                }
            });
        }
    }
};
