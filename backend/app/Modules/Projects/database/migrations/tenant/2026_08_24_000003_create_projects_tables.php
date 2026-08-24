<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description')->nullable();
            $t->uuid('manager_id')->nullable(); // references users.id
            $t->uuid('customer_id')->nullable(); // references customers.id
            $t->string('status')->default('planned'); // planned, in_progress, on_hold, completed, cancelled
            $t->bigInteger('budget_cents')->default(0);
            $t->date('start_date')->nullable();
            $t->date('due_date')->nullable();
            $t->timestamps();

            $t->foreign('manager_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('project_tasks', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('project_id');
            $t->string('title');
            $t->text('description')->nullable();
            $t->uuid('assigned_to')->nullable(); // references users.id
            $t->string('status')->default('todo'); // todo, in_progress, review, done
            $t->string('priority')->default('normal'); // low, normal, high, urgent
            $t->date('due_date')->nullable();
            $t->integer('estimated_hours')->default(0);
            $t->integer('logged_hours')->default(0);
            $t->timestamps();

            $t->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $t->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('projects');
    }
};
