<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or retrieve the primary demo tenant
        $tenant = Tenant::where('slug', 'demo')->first();

        if ($tenant) {
            $dbName = $tenant->database()->getName();
            if (! $tenant->database()->manager()->databaseExists($dbName)) {
                $tenant->database()->manager()->createDatabase($tenant);
                \Illuminate\Support\Facades\Artisan::call('tenants:migrate', [
                    '--tenants' => [$tenant->getTenantKey()],
                    '--force' => true,
                ]);
            }
        } else {
            $tenant = Tenant::create([
                'name' => 'Demo Enterprise',
                'slug' => 'demo',
                'status' => 'active',
                'settings' => [
                    'timezone' => 'UTC',
                    'locale' => 'en',
                    'currency' => 'USD',
                ],
            ]);
        }

        // 2. Initialize tenancy
        tenancy()->initialize($tenant);

        // 3. Ensure roles and accounting accounts are seeded
        $this->call([
            TenantRoleSeeder::class,
            AccountingSeeder::class,
        ]);

        // 4. Create standard demo users
        $demoUsers = [
            [
                'name' => 'Store Owner',
                'email' => 'owner@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Owner',
            ],
            [
                'name' => 'System Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Admin',
            ],
            [
                'name' => 'POS Cashier',
                'email' => 'cashier@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Cashier',
            ],
            [
                'name' => 'Senior Accountant',
                'email' => 'accountant@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Accountant',
            ],
            [
                'name' => 'Shop Keeper',
                'email' => 'shopkeeper@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Shop Keeper',
            ],
            [
                'name' => 'Warehouse Lead',
                'email' => 'warehouse@example.com',
                'password' => Hash::make('password123'),
                'role' => 'Warehouse Staff',
            ],
        ];

        foreach ($demoUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'tenant_id' => $tenant->getTenantKey(),
                    'name' => $data['name'],
                    'password' => $data['password'],
                    'is_active' => true,
                ]
            );

            // Ensure password is password123 and role is assigned
            $user->update(['password' => Hash::make('password123'), 'is_active' => true]);
            $user->syncRoles([$data['role']]);
        }

        // 5. Seed complete operational demo dataset (products, stock, shops, POS, HR, sales, projects)
        $this->call(TenantDemoDataSeeder::class);

        tenancy()->end();
    }
}
