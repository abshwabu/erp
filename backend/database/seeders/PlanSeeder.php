<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\PlanFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'price_monthly' => 29.00,
                'price_annually' => 290.00,
                'features' => [
                    'users_limit' => 5,
                    'inventory_items_limit' => 1000,
                    'multi_warehouse' => false,
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'price_monthly' => 79.00,
                'price_annually' => 790.00,
                'features' => [
                    'users_limit' => 20,
                    'inventory_items_limit' => 10000,
                    'multi_warehouse' => true,
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => 199.00,
                'price_annually' => 1990.00,
                'features' => [
                    'users_limit' => -1, // Unlimited
                    'inventory_items_limit' => -1, // Unlimited
                    'multi_warehouse' => true,
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = Plan::firstOrCreate(['slug' => $planData['slug']], array_merge($planData, [
                'id' => (string) Str::uuid(),
                'is_active' => true,
            ]));

            foreach ($features as $key => $value) {
                PlanFeature::firstOrCreate([
                    'plan_id' => $plan->id,
                    'feature_key' => $key,
                ], [
                    'id' => (string) Str::uuid(),
                    'feature_value' => $value,
                ]);
            }
        }
    }
}
