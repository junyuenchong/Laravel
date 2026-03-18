<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\Cors::class);

        // Allow SPA to read the CSRF cookie.
        $middleware->encryptCookies(except: [
            'XSRF-TOKEN',
        ]);

        // In automated tests we focus on business logic and auth flows; CSRF is covered in E2E.
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: [
                'api/*',
            ]);
        }

        $middleware->group('api', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtCookieAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
