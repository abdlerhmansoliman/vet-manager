<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureValidTenantToken;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->priority([
            IdentifyTenant::class,
            Authenticate::class,
            EnsureValidTenantToken::class,
            IdempotencyMiddleware::class,
        ]);

        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'tenant.token' => EnsureValidTenantToken::class,
            'idempotent' => IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
