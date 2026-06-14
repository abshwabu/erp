<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_work_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type'); // fixed, rotating, flexible
            $table->jsonb('days_of_week'); // [1,2,3,4,5]
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours_per_day', 4, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_work_schedules');
    }
};
