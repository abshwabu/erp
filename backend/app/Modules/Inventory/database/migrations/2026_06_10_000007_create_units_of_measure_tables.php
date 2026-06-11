<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units_of_measure', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('symbol', 20);
            $table->string('type', 20)->default('unit'); // unit | weight | volume | length
            $table->boolean('is_base')->default(false);
            $table->decimal('conversion_factor', 15, 8)->default(1.00000000);
            $table->timestamps();
        });

        Schema::create('product_uom', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('uom_id');
            $table->string('role', 20)->default('sale'); // sale | purchase | stock
            $table->decimal('conversion_factor', 15, 8)->default(1.00000000);
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('uom_id')
                ->references('id')
                ->on('units_of_measure')
                ->onDelete('restrict');

            $table->unique(['product_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_uom');
        Schema::dropIfExists('units_of_measure');
    }
};
