<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy\Finders;

use App\Modules\Core\Models\Tenant;
use Illuminate\Http\Request;

final class DomainTenantFinder
{
    public function find(Request $request): ?Tenant
    {
        $host = $request->getHost();

        return Tenant::query()
            ->where('custom_domain', $host)
            ->orWhere('slug', $host)
            ->first();
    }
}
