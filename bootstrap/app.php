<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: ['api/*']);

        $middleware->alias([
            // Middleware lama
            'api.key'            => \App\Http\Middleware\ApiKeyMiddleware::class,

            // Spatie RBAC
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,

            // Sanctum ability check
            // 'ability'  → semua ability HARUS ada (AND)
            // 'abilities'→ salah satu ability HARUS ada (OR)
            'ability'            => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
            'abilities'          => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (
            \Spatie\Permission\Exceptions\UnauthorizedException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki hak akses untuk fitur ini.',
                ], 403);
            }
        });
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Tangani error Spatie agar response-nya JSON (bukan halaman HTML error)
        $exceptions->render(function (
            \Spatie\Permission\Exceptions\UnauthorizedException $e,
            \Illuminate\Http\Request $request
        ) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki hak akses untuk fitur ini.',
                ], 403);
            }
        });
    })->create();
