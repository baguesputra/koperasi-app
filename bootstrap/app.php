<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
        $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                return null;
            }

            $status = null;

            if ($e instanceof AuthorizationException) {
                $status = 403;
            } elseif ($e instanceof NotFoundHttpException || $e instanceof ModelNotFoundException) {
                $status = 404;
            } elseif ($e instanceof TokenMismatchException) {
                $status = 419;
            } elseif ($e instanceof AccessDeniedHttpException) {
                $status = 403;
            } elseif ($e instanceof HttpExceptionInterface) {
                $code = $e->getStatusCode();
                if ($code === 403) {
                    $status = 403;
                } elseif ($code >= 500) {
                    $status = $code;
                }
            }

            if ($status === null) {
                return null;
            }

            // Halaman 500 kustom hanya tampil saat production; di dev biarkan detail error default tampil.
            if ($status >= 500 && config('app.debug')) {
                return null;
            }

            // Navigasi Inertia: kembalikan status error agar klien menampilkan modal tanpa pindah halaman.
            if ($request->header('X-Inertia')) {
                return response()->json(['status' => $status], $status);
            }

            // Load langsung / refresh / ketik URL: tampilkan halaman error penuh.
            return Inertia::render('Errors/Error', ['status' => $status]);
        });
    })->create();