<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->decimal('base_salary', 15, 2)->nullable();
            $table->string('salary_currency', 10)->default('USD');
            $table->string('salary_type', 30)->default('monthly'); // monthly, hourly, yearly, weekly
            $table->string('payment_method', 30)->default('bank_transfer'); // bank_transfer, cash, cheque
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn([
                'base_salary',
                'salary_currency',
                'salary_type',
                'payment_method',
                'bank_name',
                'bank_account_number',
                'bank_routing_number',
            ]);
        });
    }
};
