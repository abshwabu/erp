<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('provider'); // stripe, slack, sendgrid, zapier, webhook, quickbooks
            $t->string('name');
            $t->string('status')->default('disconnected'); // connected, disconnected, error
            $t->text('api_key')->nullable();
            $t->string('webhook_url')->nullable();
            $t->json('settings')->nullable();
            $t->timestamp('last_tested_at')->nullable();
            $t->timestamps();
        });

        Schema::create('integration_logs', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('integration_id');
            $t->string('event');
            $t->string('direction')->default('outbound'); // inbound, outbound
            $t->integer('status_code')->nullable();
            $t->json('payload')->nullable();
            $t->json('response')->nullable();
            $t->timestamps();

            $t->foreign('integration_id')->references('id')->on('integrations')->cascadeOnDelete();
            $t->index(['integration_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('integrations');
    }
};
