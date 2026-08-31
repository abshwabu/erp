<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('support_knowledge_articles')) {
            Schema::create('support_knowledge_articles', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('title');
                $table->string('slug')->index();
                $table->string('category', 50)->default('general'); // general, billing, technical, account, faq
                $table->text('content');
                $table->text('summary')->nullable();
                $table->boolean('is_published')->default(true);
                $table->unsignedInteger('views_count')->default(0);
                $table->unsignedInteger('helpful_count')->default(0);
                $table->uuid('author_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('author_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_knowledge_articles');
    }
};
