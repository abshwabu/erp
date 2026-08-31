<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Lead Forms Table
        if (!Schema::hasTable('crm_lead_forms')) {
            Schema::create('crm_lead_forms', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->string('slug')->index();
                $table->string('source', 50)->default('website'); // agency, social_media, website, google_ads, referral, event, other
                $table->string('form_type', 30)->default('wizard'); // wizard, classic_embed
                $table->string('headline')->nullable();
                $table->text('description')->nullable();
                $table->json('custom_questions')->nullable();
                $table->string('thank_you_title')->nullable();
                $table->text('thank_you_message')->nullable();
                $table->string('redirect_url')->nullable();
                $table->string('default_priority', 20)->default('medium');
                $table->decimal('default_estimated_value', 15, 2)->nullable();
                $table->uuid('assigned_to_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('views_count')->default(0);
                $table->unsignedInteger('submissions_count')->default(0);
                $table->string('theme_color', 30)->default('primary');
                $table->uuid('created_by_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Add lead_form_id and custom_form_responses to crm_leads if not existing
        if (Schema::hasTable('crm_leads')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('crm_leads', 'lead_form_id')) {
                    $table->uuid('lead_form_id')->nullable()->after('id');
                    $table->foreign('lead_form_id')->references('id')->on('crm_lead_forms')->nullOnDelete();
                }
                if (!Schema::hasColumn('crm_leads', 'custom_form_responses')) {
                    $table->json('custom_form_responses')->nullable()->after('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_leads') && Schema::hasColumn('crm_leads', 'lead_form_id')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->dropForeign(['lead_form_id']);
                $table->dropColumn(['lead_form_id', 'custom_form_responses']);
            });
        }

        Schema::dropIfExists('crm_lead_forms');
    }
};
