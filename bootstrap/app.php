<?php

use App\Http\Middleware\EnsurePatientPortalAuth;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'active' => EnsureUserIsActive::class,
            'patient.auth' => EnsurePatientPortalAuth::class,
        ]);

        $middleware->redirectTo(
            guests: '/admin/login',
            users: '/admin/dashboard',
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

$deploymentPublicPath = env('LARAVEL_PUBLIC_PATH')
    ?: (dirname(__DIR__, 2).'/medicare.kennedycheroben.co.ke');

if (is_dir($deploymentPublicPath)) {
    $app->usePublicPath($deploymentPublicPath);
}

return $app;
