<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('leave_type_id');
            $table->uuid('approver_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_taken', 5, 2);
            $table->boolean('half_day')->default(false);
            $table->string('half_day_period')->nullable(); // morning, afternoon
            $table->text('reason')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->text('approver_notes')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('hr_employees');
            $table->foreign('leave_type_id')->references('id')->on('hr_leave_types');
            $table->foreign('approver_id')->references('id')->on('hr_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_leave_requests');
    }
};
