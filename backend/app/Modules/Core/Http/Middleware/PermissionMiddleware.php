<?php

declare(strict_types=1);

namespace App\Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->cannot($permission)) {
            return new JsonResponse([
                'message' => 'Forbidden. You do not have the required permission.',
                'required_permission' => $permission,
            ], 403);
        }

        return $next($request);
    }
}
