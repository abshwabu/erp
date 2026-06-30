<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables whose models use SoftDeletes but whose original migrations
     * omitted the deleted_at column.
     */
    private array $tables = [
        'hr_departments',
        'hr_positions',
        'hr_work_schedules',
        'hr_leave_types',
        'hr_leave_entitlements',
        'hr_leave_requests',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropSoftDeletes();
            });
        }
    }
};
