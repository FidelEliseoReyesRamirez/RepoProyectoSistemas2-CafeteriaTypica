<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use App\Http\Middleware\IsAdminOrKitchen;


use Inertia\Inertia;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'no_direct_access' => \App\Http\Middleware\NoDirectAccess::class,
            'is_admin' => \App\Http\Middleware\IsAdmin::class,
            'can_manage_productos' => \App\Http\Middleware\CanManageProductos::class,
            'is_mesero_or_admin' => \App\Http\Middleware\IsMeseroOrAdmin::class,
            'is_admin_or_kitchen' => IsAdminOrKitchen::class,
        ]);
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // 🟥 Error 404: Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->inertia()) {
                return Inertia::location($request->headers->get('referer') ?? url()->previous() ?? '/');
            }
            return redirect()->back();
        });

        // 🟨 Error 405: Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->inertia()) {
                return Inertia::location($request->headers->get('referer') ?? url()->previous() ?? '/');
            }
            return redirect()->back();
        });
    })
    ->create();
