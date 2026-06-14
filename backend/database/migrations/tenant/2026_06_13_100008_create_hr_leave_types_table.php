<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code', 20);
            $table->boolean('is_paid')->default(true);
            $table->decimal('max_days_per_year', 5, 2);
            $table->decimal('carry_over_days', 5, 2)->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_document')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_types');
    }
};
