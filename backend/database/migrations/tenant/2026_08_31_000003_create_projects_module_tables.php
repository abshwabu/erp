<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Projects table
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->uuid('manager_id')->nullable();
                $table->uuid('customer_id')->nullable();
                $table->string('status', 30)->default('planned'); // planned, in_progress, on_hold, completed, cancelled
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->decimal('budget', 15, 2)->default(0.00);
                $table->string('currency', 10)->default('USD');
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('color', 30)->default('primary');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            });
        }

        // 2. Project Milestones table
        if (!Schema::hasTable('project_milestones')) {
            Schema::create('project_milestones', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('project_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('due_date')->nullable();
                $table->string('status', 30)->default('pending'); // pending, in_progress, achieved, delayed
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            });
        }

        // 3. Project Tasks table
        if (!Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('project_id');
                $table->uuid('milestone_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->uuid('assigned_to_user_id')->nullable();
                $table->string('status', 30)->default('todo'); // todo, in_progress, review, done
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->date('due_date')->nullable();
                $table->decimal('estimated_hours', 8, 2)->default(0.00);
                $table->decimal('logged_hours', 8, 2)->default(0.00);
                $table->integer('order')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->uuid('created_by_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
                $table->foreign('milestone_id')->references('id')->on('project_milestones')->nullOnDelete();
                $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        // 4. Project Time Logs table
        if (!Schema::hasTable('project_time_logs')) {
            Schema::create('project_time_logs', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('project_id');
                $table->uuid('task_id')->nullable();
                $table->uuid('user_id');
                $table->decimal('hours', 6, 2);
                $table->date('log_date');
                $table->text('description')->nullable();
                $table->boolean('is_billable')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
                $table->foreign('task_id')->references('id')->on('project_tasks')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_time_logs');
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('projects');
    }
};
