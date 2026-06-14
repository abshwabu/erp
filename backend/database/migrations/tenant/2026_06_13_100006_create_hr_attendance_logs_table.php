<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_attendance_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('clock_type'); // in, out
            $table->timestamp('logged_at');
            $table->string('method'); // web, mobile, biometric, manual
            $table->point('location_coords')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by_id')->nullable();

            $table->foreign('employee_id')->references('id')->on('hr_employees');
            $table->foreign('created_by_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_attendance_logs');
    }
};
