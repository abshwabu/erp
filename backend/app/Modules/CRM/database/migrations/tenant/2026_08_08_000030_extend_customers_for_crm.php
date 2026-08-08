<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend Sales customers for CRM fields when the table already exists.
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'company')) {
                    $table->string('company')->nullable()->after('phone');
                }
                if (! Schema::hasColumn('customers', 'status')) {
                    $table->enum('status', ['lead', 'customer'])->default('customer')->after('company');
                }
            });

            return;
        }

        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->enum('status', ['lead', 'customer'])->default('lead');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'company')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn(['company', 'status']);
            });
        }

        Schema::dropIfExists('crm_contacts');
    }
};
