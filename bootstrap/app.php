<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Shared\Contracts\RendersApiEnvelope;
use Shared\Http\ApiErrorCode;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('*/api/*') || $request->is('*/rpc/*'),
        );

        // The envelope is rendered here, once, for every exception that describes one — never
        // by the exception itself. This is the application's shape, not the package's.
        $exceptions->renderable(fn (RendersApiEnvelope $e) => response()->json([
            'success' => false,
            'code' => $e->code(),
            'message' => $e->message(),
        ], $e->httpCode()));

        $exceptions->renderable(fn (AuthenticationException $e, Request $request) => $request->expectsJson()
            ? response()->json([
                'success' => false,
                'code' => ApiErrorCode::Unauthenticated->value,
                'message' => __('auth.unauthenticated'),
            ], 401)
            : null);
    })->create();
