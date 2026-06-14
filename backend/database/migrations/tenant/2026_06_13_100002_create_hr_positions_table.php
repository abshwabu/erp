<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id');
            $table->string('title');
            $table->string('job_grade', 20)->nullable();
            $table->bigInteger('min_salary_cents')->nullable();
            $table->bigInteger('max_salary_cents')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('hr_departments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_positions');
    }
};
