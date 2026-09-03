<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $isSuperAdmin = false;

        if (method_exists($user, 'isSuperAdmin')) {
            $isSuperAdmin = $user->isSuperAdmin();
        } elseif ($user->email === 'superadmin@erp.local') {
            $isSuperAdmin = true;
        } elseif (\App\Models\User::where('email', $user->email)->exists()) {
            $isSuperAdmin = true;
        }

        if (! $isSuperAdmin) {
            return response()->json([
                'message' => 'Access denied. Platform Super Administrator credentials required.',
            ], 403);
        }

        return $next($request);
    }
}
