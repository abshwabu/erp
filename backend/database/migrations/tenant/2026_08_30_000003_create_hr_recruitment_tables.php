<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->index();
            $table->uuid('department_id')->nullable();
            $table->uuid('position_id')->nullable();
            $table->string('location')->default('On-site');
            $table->string('employment_type')->default('full-time');
            $table->string('experience_level')->default('mid');
            $table->decimal('min_salary', 15, 2)->nullable();
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            $table->longText('description');
            $table->longText('requirements')->nullable();
            $table->longText('benefits')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status', 30)->default('published'); // published, draft, closed
            $table->json('custom_form_schema')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('hr_departments')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('hr_positions')->nullOnDelete();
        });

        Schema::create('hr_job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('job_posting_id');
            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_phone')->nullable();
            $table->string('resume_url')->nullable();
            $table->string('photo_url')->nullable();
            $table->text('cover_letter')->nullable();
            $table->json('custom_form_responses')->nullable();
            $table->string('status', 30)->default('new'); // new, reviewed, shortlisted, interviewing, offered, hired, rejected
            $table->unsignedTinyInteger('rating')->nullable(); // 1 to 5
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('job_posting_id')->references('id')->on('hr_job_postings')->cascadeOnDelete();
            $table->index(['job_posting_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_job_applications');
        Schema::dropIfExists('hr_job_postings');
    }
};
