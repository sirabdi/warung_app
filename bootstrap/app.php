<?php

use App\Domain\Shared\Exception\DomainRuleViolation;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Presentation\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'subscribed' => \App\Presentation\Http\Middleware\EnsureSubscriptionActive::class,
            'store' => \App\Presentation\Http\Middleware\EnsureStoreOwner::class,
            'admin' => \App\Presentation\Http\Middleware\EnsureAdmin::class,
        ]);

        // Payment gateways post here from their servers, without our CSRF token;
        // each webhook checks the gateway's own secret instead.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Business rule violations (not enough stock, duplicate name, …) surface
        // as ordinary form errors — the domain never needs to know about HTTP.
        $exceptions->dontReport(DomainRuleViolation::class);

        $exceptions->map(fn (DomainRuleViolation $e) => ValidationException::withMessages([
            $e->field() => $e->getMessage(),
        ]));
    })->create();
