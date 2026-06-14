<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_number')->unique();
            $table->uuid('user_id')->nullable();
            $table->uuid('department_id');
            $table->uuid('position_id');
            $table->uuid('manager_id')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('preferred_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('national_id_encrypted')->nullable();
            $table->string('gender')->nullable(); // male, female, other, prefer_not_to_say
            $table->string('employment_type'); // full_time, part_time, contract, intern, probationary
            $table->string('status')->default('active'); // active, on_leave, suspended, terminated
            $table->date('start_date');
            $table->date('probation_end_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->uuid('work_location_id')->nullable(); // FK to warehouses
            $table->jsonb('emergency_contacts')->nullable();
            $table->jsonb('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('hr_departments');
            $table->foreign('position_id')->references('id')->on('hr_positions');
            $table->foreign('manager_id')->references('id')->on('hr_employees');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
