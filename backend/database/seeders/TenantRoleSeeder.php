<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Core\Enums\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission as SpatiePermission;

class TenantRoleSeeder extends Seeder
{
    private const GUARD = 'api';

    public function run(): void
    {
        // Reset cached roles and permissions safely
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Ignore cache error if cache driver (e.g. Redis) is unavailable
        }

        // 1. Ensure every permission exists in the tenant DB
        $this->seedPermissions();

        // 2. Create roles and assign permissions
        $this->createOwner();
        $this->createAdmin();
        $this->createAccountant();
        $this->createSalesManager();
        $this->createCashier();
        $this->createShopKeeper();
        $this->createWarehouseStaff();
        $this->createHrOfficer();
        $this->createReadOnly();
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function seedPermissions(): void
    {
        foreach (Permission::allValues() as $name) {
            SpatiePermission::firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD]
            );
        }
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(
            ['name' => $name, 'guard_name' => self::GUARD]
        );
    }

    private function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);
    }

    // ── Role definitions ──────────────────────────────────────────────────────

    private function createOwner(): void
    {
        $this->syncPermissions(
            $this->role('Owner'),
            Permission::allValues()
        );
    }

    private function createAdmin(): void
    {
        $excluded = [
            Permission::CoreRolesDelete->value,
            Permission::PayrollRunsProcess->value,
        ];

        $this->syncPermissions(
            $this->role('Admin'),
            array_values(array_diff(Permission::allValues(), $excluded))
        );
    }

    private function createAccountant(): void
    {
        $this->syncPermissions(
            $this->role('Accountant'),
            array_merge(
                Permission::module('accounting'),
                Permission::module('payroll'),
                [
                    Permission::SalesInvoicesCreate->value,
                    Permission::SalesInvoicesSend->value,
                ]
            )
        );
    }

    private function createSalesManager(): void
    {
        // CRM module TBD — only sales.* for now
        $this->syncPermissions(
            $this->role('Sales Manager'),
            Permission::module('sales')
        );
    }

    private function createCashier(): void
    {
        $this->syncPermissions(
            $this->role('Cashier'),
            [
                Permission::PosSessionsOpen->value,
                Permission::PosSessionsClose->value,
                Permission::PosTransactionsCreate->value,
                Permission::PosDiscountsApply->value,
                Permission::PosRefundsProcess->value,
                Permission::PosReportsView->value,
                Permission::ShopsView->value,
                Permission::InventoryProductsView->value,
                Permission::InventoryStockView->value,
            ]
        );
    }

    private function createShopKeeper(): void
    {
        $this->syncPermissions(
            $this->role('Shop Keeper'),
            [
                Permission::ShopsView->value,
                Permission::InventoryProductsView->value,
                Permission::InventoryProductsCreate->value,
                Permission::InventoryProductsEdit->value,
                Permission::InventoryStockView->value,
                Permission::InventoryStockAdjust->value,
                Permission::InventoryStockMovementsView->value,
                Permission::PosSessionsOpen->value,
                Permission::PosSessionsClose->value,
                Permission::PosTransactionsCreate->value,
                Permission::PosDiscountsApply->value,
                Permission::PosRefundsProcess->value,
                Permission::PosReportsView->value,
            ]
        );
    }

    private function createWarehouseStaff(): void
    {
        $this->syncPermissions(
            $this->role('Warehouse Staff'),
            array_merge(
                Permission::module('warehouse'),
                [
                    Permission::InventoryProductsView->value,
                    Permission::InventoryStockView->value,
                    Permission::InventoryStockMovementsView->value,
                    Permission::InventoryStockAdjust->value,
                ]
            )
        );
    }

    private function createHrOfficer(): void
    {
        $this->syncPermissions(
            $this->role('HR Officer'),
            array_merge(
                Permission::module('hr'),
                [Permission::PayrollPayslipsView->value]
            )
        );
    }

    private function createReadOnly(): void
    {
        $this->syncPermissions(
            $this->role('Read Only'),
            Permission::viewOnly()
        );
    }
}
