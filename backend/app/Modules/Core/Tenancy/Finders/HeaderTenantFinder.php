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

        if (! $tenantId && $authHeader = $request->header('Authorization')) {
            if (str_starts_with($authHeader, 'Bearer ')) {
                $jwtString = substr($authHeader, 7);
                $parts = explode('.', $jwtString);
                if (count($parts) >= 2) {
                    $payloadJson = base64_decode(strtr($parts[1], '-_', '+/'));
                    $payloadData = json_decode($payloadJson, true);
                    if (! empty($payloadData['tenant_id'])) {
                        $tenantId = $payloadData['tenant_id'];
                    }
                }
            }
        }

        if (! $tenantId) {
            return null;
        }

        try {
            return Tenant::query()
                ->where(function ($query) use ($tenantId) {
                    // Only query 'id' if it looks like a UUID to prevent Postgres errors
                    if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $tenantId)) {
                        $query->where('id', $tenantId);
                    }
                    $query->orWhere('slug', $tenantId);
                })
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
