<?php

use App\MyApp;
use Illuminate\Foundation\Application;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        // Sesión/CSRF expirados en el área admin: volver al login en vez de mostrar el error 419
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson() && $request->is(MyApp::ADMINS_SUBDIR . '/*')) {
                return redirect()->route('login')
                    ->with('status', 'Tu sesión expiró. Inicia sesión nuevamente.');
            }
        });
    })->create();
