<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->string('feature_key');
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('feature_value')->nullable();
            } else {
                $table->json('feature_value')->nullable();
            }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
