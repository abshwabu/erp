<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $t) {
            $t->string('tracking_number')->nullable()->after('fulfillment_status');
            $t->string('shipping_carrier')->nullable()->after('tracking_number');
            $t->text('notes')->nullable()->after('shipping_carrier');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_orders', function (Blueprint $t) {
            $t->dropColumn(['tracking_number', 'shipping_carrier', 'notes']);
        });
    }
};
