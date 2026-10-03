<?php

namespace App\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Store pages need a store. An admin has none, so they go to /admin instead
 * of reaching code that expects one.
 */
class EnsureStoreOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->store_id !== null) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403);

        return redirect()->route('admin.dashboard');
    }
}
