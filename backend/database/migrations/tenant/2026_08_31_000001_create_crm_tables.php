<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend Customers table for CRM fields if table exists
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'company')) {
                    $table->string('company')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('customers', 'job_title')) {
                    $table->string('job_title')->nullable()->after('company');
                }
                if (!Schema::hasColumn('customers', 'status')) {
                    $table->string('status', 30)->default('customer')->after('job_title'); // lead, customer, partner, churned
                }
                if (!Schema::hasColumn('customers', 'source')) {
                    $table->string('source', 50)->nullable()->after('status');
                }
                if (!Schema::hasColumn('customers', 'address')) {
                    $table->string('address')->nullable()->after('source');
                }
                if (!Schema::hasColumn('customers', 'city')) {
                    $table->string('city', 100)->nullable()->after('address');
                }
                if (!Schema::hasColumn('customers', 'country')) {
                    $table->string('country', 100)->nullable()->after('city');
                }
                if (!Schema::hasColumn('customers', 'website')) {
                    $table->string('website')->nullable()->after('country');
                }
                if (!Schema::hasColumn('customers', 'notes')) {
                    $table->text('notes')->nullable()->after('website');
                }
            });
        }

        // 2. Leads Table
        if (!Schema::hasTable('crm_leads')) {
            Schema::create('crm_leads', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('name');
                $table->string('company')->nullable();
                $table->string('title')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('source', 50)->default('website'); // website, referral, outreach, social, event, ads, other
                $table->string('status', 30)->default('new'); // new, contacted, qualified, unqualified, converted
                $table->string('priority', 20)->default('medium'); // low, medium, high, urgent
                $table->decimal('estimated_value', 15, 2)->nullable();
                $table->string('currency', 10)->default('USD');
                $table->uuid('assigned_to_user_id')->nullable();
                $table->uuid('converted_customer_id')->nullable();
                $table->uuid('converted_deal_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'priority']);
            });
        }

        // 3. Deals / Pipeline Opportunities Table
        if (!Schema::hasTable('crm_deals')) {
            Schema::create('crm_deals', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->uuid('customer_id')->nullable();
                $table->uuid('lead_id')->nullable();
                $table->string('stage', 30)->default('qualification'); // qualification, proposal, negotiation, won, lost
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->unsignedTinyInteger('probability')->default(20); // 0 to 100%
                $table->date('expected_close_date')->nullable();
                $table->date('actual_close_date')->nullable();
                $table->uuid('assigned_to_user_id')->nullable();
                $table->string('lost_reason')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['stage', 'expected_close_date']);
            });
        }

        // 4. Activities & Interaction Logs Table
        if (!Schema::hasTable('crm_activities')) {
            Schema::create('crm_activities', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type', 30)->default('call'); // call, meeting, email, task, follow_up, note
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('due_date')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->string('status', 30)->default('pending'); // pending, completed, cancelled
                $table->string('priority', 20)->default('medium'); // low, medium, high
                $table->uuid('customer_id')->nullable();
                $table->uuid('lead_id')->nullable();
                $table->uuid('deal_id')->nullable();
                $table->uuid('assigned_to_user_id')->nullable();
                $table->uuid('created_by_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['status', 'type', 'due_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_deals');
        Schema::dropIfExists('crm_leads');
    }
};
