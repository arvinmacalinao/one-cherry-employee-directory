<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Signed-in staff hitting /login go to their own landing page, not the
        // framework default (which would 403 an Admin).
        $middleware->redirectUsersTo(fn ($request) => $request->user()->homeUrl());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
