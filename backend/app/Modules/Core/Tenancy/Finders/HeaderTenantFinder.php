<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy\Finders;

use App\Modules\Core\Models\Tenant;
use Illuminate\Http\Request;

final class HeaderTenantFinder
{
    public function find(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return null;
        }

        return Tenant::query()
            ->where(function ($query) use ($tenantId) {
                // Only query 'id' if it looks like a UUID to prevent Postgres errors
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $tenantId)) {
                    $query->where('id', $tenantId);
                }
                $query->orWhere('slug', $tenantId);
            })
            ->first();
    }
}
