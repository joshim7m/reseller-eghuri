<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckStatus;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'check-status' => CheckStatus::class,
            'check-role' => CheckRole::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin*') || $request->routeIs('admin.*')
                ? route('admin.login')
                : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($exception instanceof PostTooLargeException) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'The uploaded file is too large.',
                        'errors' => ['file' => 'The uploaded file exceeds the server upload limit.'],
                    ], 413);
                }

                return back()
                    ->with('error', 'The uploaded file exceeds the server upload limit.')
                    ->setStatusCode(413);
            }

            if ($exception instanceof NotFoundHttpException
                && ! $request->is('admin*')
                && ! $request->is('api/*')
                && ! $request->expectsJson()) {
                return Inertia::render('StoreFront/Errors/NotFound', ['status' => 404])
                    ->toResponse($request)
                    ->setStatusCode(404);
            }

            return $response;
        });
    })->create();
