<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only application: never redirect unauthenticated requests to a
        // non-existent named 'login' web route. The exception renderer below
        // returns a JSON 401 for all /api/* requests, even without Accept.
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Always return JSON from /api, even if a client forgets the Accept header.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $exception) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'The given data was invalid.',
                    'errors' => $exception->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'This action is unauthorized.'], 403);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Request failed.',
                ], $exception->getStatusCode());
            }
        });
    })
    ->create();
