<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('code', 20);
            $table->uuid('head_employee_id')->nullable();
            $table->integer('cost_center_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('hr_departments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_departments');
    }
};
