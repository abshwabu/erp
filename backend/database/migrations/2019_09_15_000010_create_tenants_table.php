<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->uuid('plan_id')->nullable()->index();
            $table->string('status')->default('active');
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('settings')->nullable();
                $table->jsonb('data')->nullable();
            } else {
                $table->json('settings')->nullable();
                $table->json('data')->nullable();
            }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
