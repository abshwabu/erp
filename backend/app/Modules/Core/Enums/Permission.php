<?php

declare(strict_types=1);

namespace App\Modules\Core\Enums;

enum Permission: string
{
    // ─── Core ────────────────────────────────────────────────────────────────
    case CoreUsersView   = 'core.users.view';
    case CoreUsersCreate = 'core.users.create';
    case CoreUsersEdit   = 'core.users.edit';
    case CoreUsersDelete = 'core.users.delete';

    case CoreRolesView   = 'core.roles.view';
    case CoreRolesCreate = 'core.roles.create';
    case CoreRolesEdit   = 'core.roles.edit';
    case CoreRolesDelete = 'core.roles.delete';

    case CoreSettingsView = 'core.settings.view';
    case CoreSettingsEdit = 'core.settings.edit';

    // ─── Inventory ───────────────────────────────────────────────────────────
    case InventoryProductsView   = 'inventory.products.view';
    case InventoryProductsCreate = 'inventory.products.create';
    case InventoryProductsEdit   = 'inventory.products.edit';
    case InventoryProductsDelete = 'inventory.products.delete';

    case InventoryStockView   = 'inventory.stock.view';
    case InventoryStockAdjust = 'inventory.stock.adjust';

    case InventoryStockMovementsView = 'inventory.stock_movements.view';

    // ─── POS ─────────────────────────────────────────────────────────────────
    case PosSessionsOpen    = 'pos.sessions.open';
    case PosSessionsClose   = 'pos.sessions.close';
    case PosTransactionsCreate = 'pos.transactions.create';
    case PosDiscountsApply  = 'pos.discounts.apply';
    case PosRefundsProcess  = 'pos.refunds.process';
    case PosReportsView     = 'pos.reports.view';

    // ─── Procurement ─────────────────────────────────────────────────────────
    case ProcurementPurchaseOrdersView    = 'procurement.purchase_orders.view';
    case ProcurementPurchaseOrdersCreate  = 'procurement.purchase_orders.create';
    case ProcurementPurchaseOrdersApprove = 'procurement.purchase_orders.approve';
    case ProcurementSuppliersManage       = 'procurement.suppliers.manage';

    // ─── HR ──────────────────────────────────────────────────────────────────
    case HrEmployeesView       = 'hr.employees.view';
    case HrEmployeesCreate     = 'hr.employees.create';
    case HrEmployeesEdit       = 'hr.employees.edit';
    case HrEmployeesViewSalary = 'hr.employees.view_salary';
    case HrLeaveApprove        = 'hr.leave.approve';
    case HrAttendanceView      = 'hr.attendance.view';

    // ─── Accounting ───────────────────────────────────────────────────────────
    case AccountingJournalsView   = 'accounting.journals.view';
    case AccountingJournalsPost   = 'accounting.journals.post';
    case AccountingReportsView    = 'accounting.reports.view';
    case AccountingReportsExport  = 'accounting.reports.export';

    // ─── Sales ───────────────────────────────────────────────────────────────
    case SalesOrdersView    = 'sales.orders.view';
    case SalesOrdersCreate  = 'sales.orders.create';
    case SalesInvoicesCreate = 'sales.invoices.create';
    case SalesInvoicesSend   = 'sales.invoices.send';

    // ─── Payroll ─────────────────────────────────────────────────────────────
    case PayrollRunsView     = 'payroll.runs.view';
    case PayrollRunsProcess  = 'payroll.runs.process';
    case PayrollPayslipsView = 'payroll.payslips.view';

    // ─── Warehouse ────────────────────────────────────────────────────────────
    case WarehouseReceive = 'warehouse.receive';
    case WarehousePick    = 'warehouse.pick';
    case WarehouseShip    = 'warehouse.ship';

    // ─── Manufacturing ────────────────────────────────────────────────────────
    case ManufacturingWorkOrdersView   = 'manufacturing.work_orders.view';
    case ManufacturingWorkOrdersCreate = 'manufacturing.work_orders.create';
    case ManufacturingBomManage        = 'manufacturing.bom.manage';

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * All permission string values.
     */
    public static function allValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * All permission values belonging to a given module prefix.
     * e.g. Permission::module('pos') returns all pos.* strings.
     */
    public static function module(string $module): array
    {
        return array_values(
            array_filter(
                self::allValues(),
                static fn (string $p) => str_starts_with($p, $module . '.')
            )
        );
    }

    /**
     * All "view" permissions across every module (for Read-Only role).
     */
    public static function viewOnly(): array
    {
        return array_values(
            array_filter(
                self::allValues(),
                static fn (string $p) => str_ends_with($p, '.view')
            )
        );
    }
}
