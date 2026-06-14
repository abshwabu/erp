<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_entitlements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('leave_type_id');
            $table->integer('year');
            $table->decimal('entitled_days', 5, 2);
            $table->decimal('accrued_days', 5, 2);
            $table->decimal('taken_days', 5, 2);
            $table->decimal('carried_over_days', 5, 2)->default(0);

            $table->unique(['employee_id', 'leave_type_id', 'year']);
            $table->foreign('employee_id')->references('id')->on('hr_employees');
            $table->foreign('leave_type_id')->references('id')->on('hr_leave_types');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_entitlements');
    }
};
