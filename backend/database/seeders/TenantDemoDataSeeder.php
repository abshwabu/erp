<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Previously seeded fake inventory products, doubled stock levels, and POS
 * sales that never deducted inventory. Intentionally empty so inventory
 * stays empty until users create real products.
 *
 * Kept as a stub so older docs/commands referencing this class do not break.
 */
class TenantDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: do not seed inventory, stock, or POS demo transactions.
    }
}
