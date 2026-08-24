<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('asset_tag')->unique();
            $t->string('name');
            $t->string('category')->default('equipment'); // equipment, machinery, vehicle, electronics, furniture, building
            $t->string('serial_number')->nullable();
            $t->date('purchase_date')->nullable();
            $t->bigInteger('purchase_cost_cents')->default(0);
            $t->bigInteger('salvage_value_cents')->default(0);
            $t->integer('useful_life_years')->default(5);
            $t->string('depreciation_method')->default('straight_line'); // straight_line, declining_balance
            $t->string('status')->default('active'); // active, maintenance, disposed, retired
            $t->uuid('assigned_to')->nullable(); // references users.id
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('asset_depreciations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('asset_id');
            $t->integer('fiscal_year');
            $t->bigInteger('depreciation_amount_cents');
            $t->bigInteger('accumulated_depreciation_cents');
            $t->bigInteger('book_value_cents');
            $t->timestamps();

            $t->foreign('asset_id')->references('id')->on('assets')->cascadeOnDelete();
            $t->unique(['asset_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('assets');
    }
};
