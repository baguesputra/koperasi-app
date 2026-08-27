<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsurePasswordChanged::class,
        ]);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'idempotent' => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
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
            return Inertia::render('Errors/Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
