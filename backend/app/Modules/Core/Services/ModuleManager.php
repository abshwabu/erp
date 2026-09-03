<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

class ModuleManager
{
    /**
     * Complete ERP Module Registry with metadata, categories, icons, and dependencies.
     */
    public const MODULES = [
        'core' => [
            'key'          => 'core',
            'name'         => 'Core Settings & RBAC',
            'category'     => 'Administration',
            'description'  => 'User management, role-based access control, security & system settings.',
            'icon'         => 'ShieldCheck',
            'dependencies' => [],
        ],
        'inventory' => [
            'key'          => 'inventory',
            'name'         => 'Inventory & Stock Control',
            'category'     => 'Operations',
            'description'  => 'Multi-warehouse stock tracking, valuations, adjustments & low-stock alerts.',
            'icon'         => 'Package',
            'dependencies' => [],
        ],
        'accounting' => [
            'key'          => 'accounting',
            'name'         => 'Double-Entry Accounting',
            'category'     => 'Finance',
            'description'  => 'General ledger, chart of accounts, journal entries, trial balance & balance sheet.',
            'icon'         => 'BookOpen',
            'dependencies' => [],
        ],
        'hr' => [
            'key'          => 'hr',
            'name'         => 'Human Resources & Attendance',
            'category'     => 'People',
            'description'  => 'Employee records, departments, clock-in attendance & leave requests.',
            'icon'         => 'Users',
            'dependencies' => [],
        ],
        'sales' => [
            'key'          => 'sales',
            'name'         => 'Sales & Customer Invoicing',
            'category'     => 'Revenue',
            'description'  => 'Tax invoices, customer receipts, credit terms & recurring billing.',
            'icon'         => 'FileText',
            'dependencies' => ['inventory', 'accounting'],
        ],
        'pos' => [
            'key'          => 'pos',
            'name'         => 'Point of Sale (POS)',
            'category'     => 'Operations',
            'description'  => 'Retail cashier checkout, receipt printing, offline cache & session totals.',
            'icon'         => 'Store',
            'dependencies' => ['inventory', 'sales'],
        ],
        'crm' => [
            'key'          => 'crm',
            'name'         => 'CRM & Lead Intake',
            'category'     => 'Revenue',
            'description'  => 'Lead intake forms, contact stages, deal pipeline & activity logging.',
            'icon'         => 'UserCheck',
            'dependencies' => ['sales'],
        ],
        'procurement' => [
            'key'          => 'procurement',
            'name'         => 'Procurement & Purchasing',
            'category'     => 'Operations',
            'description'  => 'Purchase orders, vendor directory, bills & goods received notes.',
            'icon'         => 'Truck',
            'dependencies' => ['inventory', 'accounting'],
        ],
        'payroll' => [
            'key'          => 'payroll',
            'name'         => 'Payroll Runs & Payslips',
            'category'     => 'People',
            'description'  => 'Automated salary calculations, allowances, deductions & tax reports.',
            'icon'         => 'Banknote',
            'dependencies' => ['hr', 'accounting'],
        ],
        'projects' => [
            'key'          => 'projects',
            'name'         => 'Projects & Billable Time',
            'category'     => 'Operations',
            'description'  => 'Kanban task boards, milestones, timesheets & project budgeting.',
            'icon'         => 'FolderKanban',
            'dependencies' => ['hr', 'accounting'],
        ],
        'manufacturing' => [
            'key'          => 'manufacturing',
            'name'         => 'Manufacturing & BOMs',
            'category'     => 'Operations',
            'description'  => 'Production work orders, Bill of Materials (BOM), scrap tracking & costing.',
            'icon'         => 'Factory',
            'dependencies' => ['inventory', 'procurement'],
        ],
        'ecommerce' => [
            'key'          => 'ecommerce',
            'name'         => 'Multi-Storefront Ecommerce',
            'category'     => 'Revenue',
            'description'  => 'Public online web stores, digital catalog, shopping carts & online orders.',
            'icon'         => 'ShoppingBag',
            'dependencies' => ['inventory', 'sales'],
        ],
        'support' => [
            'key'          => 'support',
            'name'         => 'Customer Support & Helpdesk',
            'category'     => 'People',
            'description'  => 'Support tickets, SLA management, knowledge base articles & resolution history.',
            'icon'         => 'LifeBuoy',
            'dependencies' => ['crm'],
        ],
        'assets' => [
            'key'          => 'assets',
            'name'         => 'Fixed Asset Depreciation',
            'category'     => 'Finance',
            'description'  => 'Asset lifecycle registry, straight-line/declining depreciation & write-downs.',
            'icon'         => 'Coins',
            'dependencies' => ['accounting'],
        ],
        'integrations' => [
            'key'          => 'integrations',
            'name'         => 'Webhooks & API Gateways',
            'category'     => 'Technology',
            'description'  => 'Real-time webhook events, third-party payment gateways & REST API connectors.',
            'icon'         => 'Webhook',
            'dependencies' => ['core'],
        ],
    ];

    /**
     * Recursively calculate all prerequisites needed when turning a module ON.
     */
    public static function getPrerequisites(string $moduleKey): array
    {
        $moduleKey = strtolower($moduleKey);
        if (!isset(self::MODULES[$moduleKey])) {
            return [];
        }

        $result = [];
        $direct = self::MODULES[$moduleKey]['dependencies'] ?? [];

        foreach ($direct as $dep) {
            $result[] = $dep;
            $result = array_merge($result, self::getPrerequisites($dep));
        }

        return array_values(array_unique($result));
    }

    /**
     * Recursively calculate all dependent modules that rely on this module.
     */
    public static function getDependents(string $moduleKey): array
    {
        $moduleKey = strtolower($moduleKey);
        $result = [];

        foreach (self::MODULES as $key => $mod) {
            if ($key === $moduleKey) {
                continue;
            }
            $prereqs = self::getPrerequisites($key);
            if (in_array($moduleKey, $prereqs, true)) {
                $result[] = $key;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * Compute new enabled modules state when toggling a module ON or OFF.
     *
     * @param array $currentEnabled
     * @param string $targetModule
     * @param bool $state
     * @param array $planAllowed
     * @return array ['enabled' => array, 'activated' => array, 'deactivated' => array]
     */
    public static function resolveToggle(
        array $currentEnabled,
        string $targetModule,
        bool $state,
        array $planAllowed = ['*']
    ): array {
        $targetModule = strtolower($targetModule);
        $currentEnabled = array_map('strtolower', $currentEnabled);

        $activated = [];
        $deactivated = [];

        if ($state) {
            // Turning ON: enable target and all its prerequisites
            $toEnable = array_merge([$targetModule], self::getPrerequisites($targetModule));

            // Core is always enabled
            $toEnable[] = 'core';

            // Filter against plan permissions if restricted
            if (!in_array('*', $planAllowed, true)) {
                $toEnable = array_filter($toEnable, fn ($m) => in_array($m, $planAllowed, true));
            }

            foreach ($toEnable as $mod) {
                if (!in_array($mod, $currentEnabled, true)) {
                    $activated[] = $mod;
                }
            }

            $newEnabled = array_values(array_unique(array_merge($currentEnabled, $toEnable)));
        } else {
            // Turning OFF: cannot turn off 'core'
            if ($targetModule === 'core') {
                return [
                    'enabled'     => $currentEnabled,
                    'activated'   => [],
                    'deactivated' => [],
                ];
            }

            // Disable target and all modules that depend on it
            $toDisable = array_merge([$targetModule], self::getDependents($targetModule));

            foreach ($toDisable as $mod) {
                if (in_array($mod, $currentEnabled, true)) {
                    $deactivated[] = $mod;
                }
            }

            $newEnabled = array_values(array_diff($currentEnabled, $toDisable));
        }

        // Guarantee core remains enabled
        if (!in_array('core', $newEnabled, true)) {
            $newEnabled[] = 'core';
        }

        return [
            'enabled'     => $newEnabled,
            'activated'   => $activated,
            'deactivated' => $deactivated,
        ];
    }
}
