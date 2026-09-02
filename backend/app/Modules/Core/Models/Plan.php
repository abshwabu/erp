<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price_monthly' => 'integer',
            'price_annually' => 'integer',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    /**
     * Get array of allowed module slugs for this plan.
     * e.g. ['sales', 'crm', 'inventory', 'pos'] or ['*'] for all.
     */
    public function getAllowedModules(): array
    {
        $feature = $this->features->firstWhere('feature_key', 'allowed_modules');
        if (!$feature || empty($feature->feature_value)) {
            return match ($this->slug) {
                'basic' => ['sales', 'crm', 'inventory', 'pos', 'core'],
                'professional' => [
                    'sales', 'crm', 'inventory', 'pos', 'core',
                    'accounting', 'procurement', 'hr', 'payroll',
                    'projects', 'support', 'assets'
                ],
                default => ['*'],
            };
        }

        $val = $feature->feature_value;
        return is_array($val) ? $val : [$val];
    }

    /**
     * Check if this plan includes access to a given module.
     */
    public function hasModule(string $module): bool
    {
        $allowed = $this->getAllowedModules();
        if (in_array('*', $allowed, true)) {
            return true;
        }

        return in_array(strtolower($module), array_map('strtolower', $allowed), true);
    }

    /**
     * Get marketing perks for this plan.
     */
    public function getPerks(): array
    {
        $feature = $this->features->firstWhere('feature_key', 'perks');
        if ($feature && is_array($feature->feature_value)) {
            return $feature->feature_value;
        }

        return match ($this->slug) {
            'basic' => [
                'Up to 5 Team Members',
                '100 Sales Invoices & Quotes / month',
                'CRM Contacts & Website Lead Forms',
                'Single-Warehouse Inventory & SKU Tracking',
                'Point of Sale (POS) Cashier Register',
                'Standard Financial Summary Reports',
            ],
            'professional' => [
                'Up to 25 Team Members',
                'Unlimited Invoices & Quotations',
                'Full Double-Entry Accounting & Financial Reports',
                'Procurement Management & Supplier Orders',
                'Complete HR Staff, Attendance & Leave Approvals',
                'Automated Payroll Runs & Payslip PDF Generation',
                'Project Tasks, Milestones & Time Logs',
                'Customer Support Helpdesk & Knowledge Base',
                'Fixed Asset Depreciation Schedules',
                'Multi-Warehouse & Inventory Transfer Orders',
                'Custom Domain Mapping (portal.yourbrand.com)',
            ],
            default => [
                'Unlimited Team Members & Workspaces',
                'Manufacturing & Production (BOM & Work Orders)',
                'Multi-Storefront Ecommerce & Online Catalogs',
                '12-Gateway Connectors & Real-Time Webhook Engine',
                'Dedicated PostgreSQL Schema Guard',
                'Cross-Tenant Super Admin Impersonation & Workspace Switching',
                'Automated Hourly Database Backup Snapshots',
                '24/7 Dedicated Account Manager & 1-Hour SLA',
            ],
        };
    }

    /**
     * Get capacity limits for this plan.
     */
    public function getLimits(): array
    {
        $map = [];
        foreach ($this->features as $f) {
            if ($f->feature_key !== 'perks' && $f->feature_key !== 'allowed_modules') {
                $map[$f->feature_key] = $f->feature_value;
            }
        }

        return array_merge([
            'users_limit' => $this->slug === 'enterprise' ? -1 : ($this->slug === 'professional' ? 25 : 5),
            'storage_gb' => $this->slug === 'enterprise' ? 500 : ($this->slug === 'professional' ? 50 : 5),
            'invoices_limit' => $this->slug === 'basic' ? 100 : -1,
            'multi_warehouse' => $this->slug !== 'basic',
            'advanced_accounting' => $this->slug !== 'basic',
            'custom_domain' => $this->slug !== 'basic',
            'webhooks' => $this->slug === 'enterprise',
        ], $map);
    }
}
