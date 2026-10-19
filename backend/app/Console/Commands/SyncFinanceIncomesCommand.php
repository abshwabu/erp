<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Core\Models\Tenant;
use App\Modules\Finance\Services\FinanceService;
use Illuminate\Console\Command;

final class SyncFinanceIncomesCommand extends Command
{
    protected $signature = 'finance:sync {--tenant= : Specific tenant UUID to sync}';

    protected $description = 'Sync all POS sales and invoice payments into Finance income transactions and Accounting journals.';

    public function handle(FinanceService $financeService): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();
        } else {
            $tenants = Tenant::all();
        }

        $this->info("Starting Finance sync across {$tenants->count()} tenant(s)...");

        $totalPos = 0;
        $totalInvoices = 0;

        foreach ($tenants as $tenant) {
            $this->line("Syncing tenant: {$tenant->id} ({$tenant->name})...");
            tenancy()->initialize($tenant);

            try {
                $result = $financeService->syncExistingIncomes();
                $this->info("  - POS Synced: {$result['pos_synced']}, Invoices Synced: {$result['invoices_synced']}");
                $totalPos += $result['pos_synced'];
                $totalInvoices += $result['invoices_synced'];
            } catch (\Throwable $e) {
                $this->error("  - Failed to sync tenant {$tenant->id}: " . $e->getMessage());
            } finally {
                tenancy()->end();
            }
        }

        $this->newLine();
        $this->info("Completed Finance sync: {$totalPos} POS sales and {$totalInvoices} invoice payments synced.");

        return self::SUCCESS;
    }
}
