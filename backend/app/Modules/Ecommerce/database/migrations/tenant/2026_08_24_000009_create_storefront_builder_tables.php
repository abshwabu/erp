<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefronts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('title')->nullable();
            $t->text('description')->nullable();
            $t->string('logo_url')->nullable();
            $t->json('theme_config')->nullable();
            $t->boolean('is_published')->default(false);
            $t->string('custom_domain')->nullable();
            $t->timestamps();
        });

        Schema::create('storefront_pages', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('storefront_id');
            $t->string('slug')->default('home');
            $t->string('title')->default('Home');
            $t->json('sections')->nullable(); // drag-and-drop block definitions
            $t->boolean('is_published')->default(true);
            $t->integer('order')->default(0);
            $t->timestamps();

            $t->foreign('storefront_id')->references('id')->on('storefronts')->cascadeOnDelete();
            $t->unique(['storefront_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storefront_pages');
        Schema::dropIfExists('storefronts');
    }
};
