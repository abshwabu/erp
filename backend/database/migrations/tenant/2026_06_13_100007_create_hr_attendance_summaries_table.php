<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->date('date');
            $table->decimal('scheduled_hours', 4, 2);
            $table->decimal('actual_hours', 4, 2);
            $table->decimal('overtime_hours', 4, 2);
            $table->string('status'); // present, absent, late, half_day, on_leave, holiday

            $table->unique(['employee_id', 'date']);
            $table->foreign('employee_id')->references('id')->on('hr_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_summaries');
    }
};
