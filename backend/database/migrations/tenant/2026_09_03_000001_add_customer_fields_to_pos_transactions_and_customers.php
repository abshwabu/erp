<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_transactions')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_transactions', 'customer_tin')) {
                    $table->string('customer_tin', 50)->nullable()->index()->after('customer_id');
                }
                if (!Schema::hasColumn('pos_transactions', 'customer_name')) {
                    $table->string('customer_name', 255)->nullable()->after('customer_tin');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'tin')) {
                    $table->string('tin', 50)->nullable()->index()->after('phone');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pos_transactions')) {
            Schema::table('pos_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('pos_transactions', 'customer_name')) {
                    $table->dropColumn('customer_name');
                }
                if (Schema::hasColumn('pos_transactions', 'customer_tin')) {
                    $table->dropColumn('customer_tin');
                }
            });
        }

        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'tin')) {
                    $table->dropColumn('tin');
                }
            });
        }
    }
};
