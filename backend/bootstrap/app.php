<?php

use App\Modules\Core\Http\Middleware\PermissionMiddleware;
use App\Modules\Core\Http\Middleware\TenantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            TenantMiddleware::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'audit' => \App\Http\Middleware\AuditLogMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $errors = [];
                foreach ($e->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'status' => '422',
                            'source' => ['pointer' => '/data/attributes/' . $field],
                            'title'  => 'Unprocessable Entity',
                            'detail' => $message,
                        ];
                    }
                }
                return response()->json(['errors' => $errors], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['message' => $e->getMessage() ?: 'Forbidden.'], 403);
            }
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $modelName = class_basename($e->getModel());
                return response()->json(['message' => "{$modelName} not found."], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                return response()->json(['message' => 'Too many requests.'], 429, $e->getHeaders());
            }
        });

        // Catch-all Throwable for 500s or unhandled HttpExceptions
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $status = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getStatusCode() : 500;
                
                $response = [
                    'message' => $status === 500 ? 'Server Error' : $e->getMessage(),
                ];

                if (config('app.debug')) {
                    $response['debug'] = [
                        'message'   => $e->getMessage(),
                        'exception' => get_class($e),
                        'file'      => $e->getFile(),
                        'line'      => $e->getLine(),
                        'trace'     => collect($e->getTrace())->map(fn ($trace) => \Illuminate\Support\Arr::except($trace, ['args']))->all(),
                    ];
                }

                $headers = $e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $e->getHeaders() : [];
                return response()->json($response, $status, $headers);
            }
        });
    })->create();

