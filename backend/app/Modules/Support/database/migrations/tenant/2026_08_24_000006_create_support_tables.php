<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('ticket_number')->unique();
            $t->string('subject');
            $t->uuid('customer_id')->nullable(); // references customers.id
            $t->uuid('assigned_to')->nullable(); // references users.id
            $t->string('contact_email')->nullable();
            $t->string('contact_name')->nullable();
            $t->string('status')->default('open'); // open, in_progress, pending, resolved, closed
            $t->string('priority')->default('normal'); // low, normal, high, urgent
            $t->string('channel')->default('web'); // web, email, phone, portal
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();

            $t->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $t->index(['status', 'priority']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('ticket_id');
            $t->uuid('user_id')->nullable(); // null if from customer
            $t->string('sender_name');
            $t->string('sender_type')->default('agent'); // agent, customer, system
            $t->text('message');
            $t->boolean('is_internal')->default(false);
            $t->timestamps();

            $t->foreign('ticket_id')->references('id')->on('support_tickets')->cascadeOnDelete();
            $t->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
