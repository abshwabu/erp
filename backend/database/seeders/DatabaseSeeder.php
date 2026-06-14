<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Run inside tenant context via: php artisan tenants:seed
     */
    public function run(): void
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            $this->call([
                TenantRoleSeeder::class,
            ]);
        } else {
            $this->call([
                PlanSeeder::class,
            ]);
        }
    }
}
