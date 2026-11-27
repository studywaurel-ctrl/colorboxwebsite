<?php

use App\Http\Middleware\HanyaAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => HanyaAdmin::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        // Notifikasi pembayaran dikirim server Midtrans, diperiksa memakai tanda
        // tangan (signature key), bukan token CSRF.
        $middleware->validateCsrfTokens(except: ['payment/notification']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
