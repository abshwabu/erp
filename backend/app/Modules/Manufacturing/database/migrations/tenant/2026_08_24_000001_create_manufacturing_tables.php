<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_of_materials', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('product_id');
            $t->string('name');
            $t->text('description')->nullable();
            $t->integer('output_quantity')->default(1);
            $t->string('status')->default('draft'); // draft, active, archived
            $t->timestamps();

            $t->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('bom_lines', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('bom_id');
            $t->uuid('material_id'); // references products.id
            $t->integer('quantity')->default(1);
            $t->string('unit')->default('pcs');
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->foreign('bom_id')->references('id')->on('bill_of_materials')->cascadeOnDelete();
            $t->foreign('material_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('work_orders', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('number')->unique();
            $t->uuid('bom_id');
            $t->integer('quantity')->default(1);
            $t->string('status')->default('draft'); // draft, in_progress, completed, cancelled
            $t->string('priority')->default('normal'); // low, normal, high, urgent
            $t->date('planned_start')->nullable();
            $t->date('planned_end')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();

            $t->foreign('bom_id')->references('id')->on('bill_of_materials');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('bom_lines');
        Schema::dropIfExists('bill_of_materials');
    }
};
