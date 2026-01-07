<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SPA の /login /logout も CORS 対象に含めるため、全体に CORS を適用する。
        // api だけだと web ルートの preflight が弾かれ、ブラウザ側でブロックされる。
        $middleware->append(HandleCors::class);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn ($request, $e) => $request->is('api/*')
        );

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => $exception->getMessage()], 401);
            }
        });
    })->create();
