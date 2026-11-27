<?php

use App\MyApp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sin sesión del personal → login del legacy
        $middleware->redirectGuestsTo('/system/login.php');
        // Con sesión, el login manda a システム選択
        $middleware->redirectUsersTo('/system/select.php');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sesión/CSRF expirados en el sistema del personal: volver al login en vez de mostrar el error 419
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419 && ! $request->expectsJson()
                && $request->is('system/*', MyApp::ADMINS_SUBDIR . '/*')) {
                return redirect('/system/login.php')
                    ->with('status', 'セッションの有効期限が切れました。もう一度ログインしてください。');
            }
        });
    })->create();
