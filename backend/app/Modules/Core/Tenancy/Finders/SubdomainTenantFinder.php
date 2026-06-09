<?php

declare(strict_types=1);

namespace App\Modules\Core\Tenancy\Finders;

use App\Modules\Core\Models\Tenant;
use Illuminate\Http\Request;

final class SubdomainTenantFinder
{
    public function find(Request $request): ?Tenant
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        if (in_array($host, $centralDomains, true)) {
            return null;
        }

        $parts = explode('.', $host);

        if (count($parts) < 2) {
            return null;
        }

        $subdomain = $parts[0];

        return Tenant::query()
            ->where('slug', $subdomain)
            ->first();
    }
}
