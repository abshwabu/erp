<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecommerce_channels', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('platform')->default('shopify'); // shopify, woocommerce, magento, custom
            $t->string('store_url')->nullable();
            $t->string('api_key')->nullable();
            $t->string('api_secret')->nullable();
            $t->string('webhook_secret')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_sync_at')->nullable();
            $t->timestamps();
        });

        Schema::create('ecommerce_orders', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('channel_id');
            $t->string('external_order_id');
            $t->string('order_number');
            $t->string('customer_name');
            $t->string('customer_email')->nullable();
            $t->bigInteger('total_cents')->default(0);
            $t->string('currency', 3)->default('USD');
            $t->string('payment_status')->default('paid'); // pending, paid, refunded
            $t->string('fulfillment_status')->default('unfulfilled'); // unfulfilled, fulfilled, cancelled
            $t->json('items')->nullable();
            $t->timestamps();

            $t->foreign('channel_id')->references('id')->on('ecommerce_channels')->cascadeOnDelete();
            $t->unique(['channel_id', 'external_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecommerce_orders');
        Schema::dropIfExists('ecommerce_channels');
    }
};
