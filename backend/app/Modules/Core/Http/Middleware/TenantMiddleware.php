<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use App\Modules\Core\Models\Tenant;
use App\Modules\Core\Tenancy\Finders\DomainTenantFinder;
use App\Modules\Core\Tenancy\Finders\SubdomainTenantFinder;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class TenantMiddleware
{
    public function __construct(
        private readonly SubdomainTenantFinder $subdomainFinder,
        private readonly DomainTenantFinder $domainFinder,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (! $tenant instanceof Tenant) {
            return new JsonResponse([
                'message' => 'Tenant not found.',
            ], 404);
        }

        tenancy()->initialize($tenant);
        $this->applyTenantContext($tenant);

        try {
            return $next($request);
        } finally {
            tenancy()->end();
        }
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        return $this->subdomainFinder->find($request)
            ?? $this->domainFinder->find($request);
    }

    private function applyTenantContext(Tenant $tenant): void
    {
        $settings = $tenant->settings ?? [];

        $timezone = $settings['timezone'] ?? config('app.timezone');
        $locale = $settings['locale'] ?? config('app.locale');
        $currency = $settings['currency'] ?? config('app.currency', 'USD');

        config([
            'app.timezone' => $timezone,
            'app.currency' => $currency,
        ]);

        date_default_timezone_set($timezone);
        App::setLocale($locale);
    }
}
