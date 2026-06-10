<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log mutating requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->logActivity($request, $response);
        }

        return $response;
    }

    /**
     * Log the activity.
     */
    protected function logActivity(Request $request, $response): void
    {
        $tenantId = null;
        
        // Attempt to get tenant id. If tenancy is initialized, it should be available.
        if (function_exists('tenant') && tenant()) {
            $tenantId = tenant('id');
        }

        $payload = $request->except(['password', 'password_confirmation']);

        activity()
            ->useLog('api_request')
            ->causedBy($request->user())
            ->withProperties([
                'tenant_id' => $tenantId,
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => empty($payload) ? null : $payload,
                'response_status' => $response->getStatusCode(),
            ])
            ->log('API Request');
    }
}
