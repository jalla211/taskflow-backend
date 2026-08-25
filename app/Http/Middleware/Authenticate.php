<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, return null (no redirect)
        if ($request->is('api/*')) {
            return null;
        }

        return $request->expectsJson() ? null : route('login');
    }

    /**
     * Handle unauthenticated API requests.
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*')) {
            abort(response()->json(['message' => 'Unauthenticated. Please login first.'], 401));
        }

        parent::unauthenticated($request, $guards);
    }
}