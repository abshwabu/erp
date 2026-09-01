<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User as CentralUser;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Models\User as TenantUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch available plans
        $enterprisePlan = Plan::where('name', 'Enterprise')->first() ?? Plan::first();
        $proPlan = Plan::where('name', 'Professional')->first() ?? Plan::first();
        $basicPlan = Plan::where('name', 'Basic')->first() ?? Plan::first();

        // 2. Ensure central super admin user exists
        $centralSuperAdmin = CentralUser::firstOrCreate(
            ['email' => 'superadmin@erp.local'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 3. Update primary Demo Tenant with Enterprise Plan
        $demoTenant = Tenant::where('slug', 'demo')->first();
        if ($demoTenant) {
            $demoTenant->update([
                'plan_id' => $enterprisePlan?->id,
                'status'  => 'active',
                'name'    => 'Demo Enterprise Global',
                'custom_domain' => 'demo.erp.local',
            ]);

            // Link central super admin to demo tenant
            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $demoTenant->getTenantKey(), 'user_id' => $centralSuperAdmin->id],
                ['created_at' => now(), 'updated_at' => now()]
            );

            // Initialize demo tenant to ensure Super Admin user exists inside tenant
            tenancy()->initialize($demoTenant);
            $tenantSuperAdmin = TenantUser::firstOrCreate(
                ['email' => 'superadmin@erp.local'],
                [
                    'tenant_id' => $demoTenant->getTenantKey(),
                    'name'      => 'Super Administrator',
                    'password'  => Hash::make('password123'),
                    'is_active' => true,
                ]
            );
            $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'api']);
            $tenantSuperAdmin->syncRoles([$adminRole]);
            tenancy()->end();
        }

        // 4. Seed additional realistic multi-tenant companies in Central DB
        $tenantsToSeed = [
            [
                'name' => 'Nexus Cloud Technologies',
                'slug' => 'nexus-tech',
                'custom_domain' => 'erp.nexuscloud.io',
                'plan_id' => $enterprisePlan?->id,
                'status' => 'active',
                'settings' => [
                    'display_name' => 'Nexus Cloud Technologies Inc.',
                    'currency' => 'USD',
                    'timezone' => 'America/New_York',
                    'owner_email' => 'admin@nexuscloud.io',
                    'company_phone' => '+1 (415) 890-1234',
                ],
            ],
            [
                'name' => 'Apex Retail Global',
                'slug' => 'apex-retail',
                'custom_domain' => 'portal.apexretail.com',
                'plan_id' => $proPlan?->id,
                'status' => 'active',
                'settings' => [
                    'display_name' => 'Apex Retail Global Ltd',
                    'currency' => 'EUR',
                    'timezone' => 'Europe/London',
                    'owner_email' => 'ops@apexretail.com',
                    'company_phone' => '+44 20 7946 0912',
                ],
            ],
            [
                'name' => 'Addis Trading & Manufacturing PLC',
                'slug' => 'addis-trading',
                'custom_domain' => 'addistrading.et',
                'plan_id' => $basicPlan?->id,
                'status' => 'active',
                'settings' => [
                    'display_name' => 'Addis Trading & Manufacturing PLC',
                    'currency' => 'ETB',
                    'timezone' => 'Africa/Addis_Ababa',
                    'owner_email' => 'contact@addistrading.et',
                    'company_phone' => '+251 11 661 2345',
                ],
            ],
            [
                'name' => 'Horizon Logistics & Supply Chain',
                'slug' => 'horizon-logistics',
                'custom_domain' => 'fleet.horizonlogistics.org',
                'plan_id' => $proPlan?->id,
                'status' => 'trial',
                'settings' => [
                    'display_name' => 'Horizon Logistics & Supply Chain LLC',
                    'currency' => 'AED',
                    'timezone' => 'Asia/Dubai',
                    'owner_email' => 'director@horizonlogistics.org',
                    'company_phone' => '+971 4 312 9876',
                ],
            ],
        ];

        foreach ($tenantsToSeed as $tData) {
            $t = Tenant::firstOrCreate(
                ['slug' => $tData['slug']],
                [
                    'name' => $tData['name'],
                    'custom_domain' => $tData['custom_domain'],
                    'plan_id' => $tData['plan_id'],
                    'status' => $tData['status'],
                    'settings' => $tData['settings'],
                ]
            );

            // Ensure plan is attached
            $t->update([
                'plan_id' => $tData['plan_id'],
                'status' => $tData['status'],
                'custom_domain' => $tData['custom_domain'],
            ]);
        }
    }
}
