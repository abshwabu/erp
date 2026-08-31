<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Projects Table columns check
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!Schema::hasColumn('projects', 'priority')) {
                    $table->string('priority', 20)->default('medium')->after('status');
                }
                if (!Schema::hasColumn('projects', 'budget')) {
                    $table->decimal('budget', 15, 2)->default(0.00)->after('priority');
                }
                if (!Schema::hasColumn('projects', 'currency')) {
                    $table->string('currency', 10)->default('USD')->after('budget');
                }
                if (!Schema::hasColumn('projects', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('due_date');
                }
                if (!Schema::hasColumn('projects', 'color')) {
                    $table->string('color', 30)->default('primary')->after('completed_at');
                }
                if (!Schema::hasColumn('projects', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        // 2. Project Tasks Table columns check
        if (Schema::hasTable('project_tasks')) {
            Schema::table('project_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('project_tasks', 'milestone_id')) {
                    $table->uuid('milestone_id')->nullable()->after('project_id');
                }
                if (!Schema::hasColumn('project_tasks', 'assigned_to_user_id')) {
                    $table->uuid('assigned_to_user_id')->nullable()->after('description');
                }
                if (!Schema::hasColumn('project_tasks', 'order')) {
                    $table->integer('order')->default(0)->after('logged_hours');
                }
                if (!Schema::hasColumn('project_tasks', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('order');
                }
                if (!Schema::hasColumn('project_tasks', 'created_by_user_id')) {
                    $table->uuid('created_by_user_id')->nullable()->after('completed_at');
                }
                if (!Schema::hasColumn('project_tasks', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }
    }

    public function down(): void
    {
        // No-op
    }
};
