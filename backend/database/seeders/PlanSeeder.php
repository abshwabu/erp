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
                'price_monthly' => 2900,   // $29.00 / mo
                'price_annually' => 29000, // $290.00 / yr
                'features' => [
                    'tagline' => 'Essential tools for single-location shops and early-stage small businesses.',
                    'badge' => 'Starter',
                    'users_limit' => 5,
                    'storage_gb' => 5,
                    'invoices_limit' => 100,
                    'multi_warehouse' => false,
                    'advanced_accounting' => false,
                    'custom_domain' => false,
                    'webhooks_integrations' => false,
                    'support_sla' => 'Standard Email Support (48-hour response)',
                    'allowed_modules' => [
                        'sales',
                        'crm',
                        'inventory',
                        'pos',
                        'core',
                    ],
                    'perks' => [
                        'Up to 5 Team Member Seats',
                        'Up to 100 Invoices & Quotations / month',
                        'Basic Single-Warehouse Stock & Product Catalog',
                        'Core CRM Contacts & Embedded Lead Capture Forms',
                        'Point of Sale (POS) Cashier Terminal with Receipt Printing',
                        'Standard Revenue & Tax Calculation Summary',
                        'Basic Data Export (CSV / Excel)',
                    ],
                ],
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'price_monthly' => 7900,   // $79.00 / mo
                'price_annually' => 79000, // $790.00 / yr
                'features' => [
                    'tagline' => 'Full-spectrum ERP for growing companies requiring accounting, HR & procurement.',
                    'badge' => 'Most Popular',
                    'users_limit' => 25,
                    'storage_gb' => 50,
                    'invoices_limit' => -1, // Unlimited
                    'multi_warehouse' => true,
                    'advanced_accounting' => true,
                    'custom_domain' => true,
                    'webhooks_integrations' => false,
                    'support_sla' => 'Priority Business Support (12-hour response)',
                    'allowed_modules' => [
                        'sales',
                        'crm',
                        'inventory',
                        'pos',
                        'core',
                        'accounting',
                        'procurement',
                        'hr',
                        'payroll',
                        'projects',
                        'support',
                        'assets',
                    ],
                    'perks' => [
                        'Everything in Basic, plus:',
                        'Up to 25 Team Member Seats',
                        'Unlimited Sales Invoices, Estimates & Credit Notes',
                        'Full Double-Entry Accounting (General Ledger, Journals, Aging, P&L)',
                        'Procurement Management (Purchase Orders, Bills & Suppliers)',
                        'Complete HR Suite (Employees, Attendance & Leave Approvals)',
                        'Payroll Run Engine & Automated Payslip Slips',
                        'Project Task Boards, Milestones & Billable Time Tracking',
                        'Customer Support Helpdesk & Knowledge Base Articles',
                        'Fixed Asset Management & Depreciation Schedules',
                        'Multi-Warehouse Inventory & Internal Transfer Orders',
                        'Custom Domain Mapping (e.g. portal.yourcompany.com)',
                    ],
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'price_monthly' => 19900,   // $199.00 / mo
                'price_annually' => 199000, // $1,990.00 / yr
                'features' => [
                    'tagline' => 'Unrestricted platform operations with manufacturing, webhooks, ecommerce & dedicated SLA.',
                    'badge' => 'All-Inclusive',
                    'users_limit' => -1, // Unlimited
                    'storage_gb' => 500,
                    'invoices_limit' => -1, // Unlimited
                    'multi_warehouse' => true,
                    'advanced_accounting' => true,
                    'custom_domain' => true,
                    'webhooks_integrations' => true,
                    'support_sla' => 'Dedicated 24/7 Account Executive & 1-Hour SLA',
                    'allowed_modules' => [
                        '*', // All 15+ modules unlocked
                    ],
                    'perks' => [
                        'Everything in Professional, plus:',
                        'Unlimited Team Members, User Seats & Workspaces',
                        'Manufacturing & Production (BOMs & Work Orders)',
                        'Multi-Storefront Ecommerce & Digital Product Catalog Engine',
                        'Real-Time Webhook Engine & 12-Gateway Connectors (Slack, Stripe, Zapier, etc.)',
                        'Dedicated PostgreSQL Schema Isolation & Security Guard',
                        'Cross-Tenant Super Admin Workspace Impersonation',
                        'Hourly Automated Database Backups & Snapshots',
                        'Full Audit Log History & Activity Telemetry',
                        '24/7 Dedicated Account Rep with 1-Hour SLA',
                    ],
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $features = $planData['features'];
            unset($planData['features']);

            $plan = Plan::where('slug', $planData['slug'])->first();
            if ($plan) {
                $plan->update([
                    'name' => $planData['name'],
                    'price_monthly' => $planData['price_monthly'],
                    'price_annually' => $planData['price_annually'],
                    'is_active' => true,
                ]);
            } else {
                $plan = Plan::create([
                    'id' => (string) Str::uuid(),
                    'name' => $planData['name'],
                    'slug' => $planData['slug'],
                    'price_monthly' => $planData['price_monthly'],
                    'price_annually' => $planData['price_annually'],
                    'is_active' => true,
                ]);
            }

            // Sync features
            foreach ($features as $key => $value) {
                PlanFeature::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $key,
                    ],
                    [
                        'id' => (string) Str::uuid(),
                        'feature_value' => $value,
                    ]
                );
            }
        }
    }
}
