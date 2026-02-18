<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureTwoFactorVerified;
use App\Http\Middleware\EnsureUserCanManageRoles;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'super_admin' => EnsureUserIsSuperAdmin::class,
            'can-manage-roles' => EnsureUserCanManageRoles::class,
            'active' => EnsureUserIsActive::class,
            'two-factor-verified' => EnsureTwoFactorVerified::class,
        ]);

        $middleware->web(append: [
            AddSecurityHeaders::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request): ?Response {
            if (! $request->header('X-Inertia')) {
                return null;
            }

            $status = $e instanceof HttpException ? $e->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = $e instanceof HttpException ? $e->getMessage() : '';

            return Inertia::render('Error', [
                'status' => $status,
                'message' => $message,
            ])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
