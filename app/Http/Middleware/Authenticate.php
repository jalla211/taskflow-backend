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
        // Always return null for API – we handle it manually
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        // For web, redirect to login (but we don't have a login route)
        return null; // Prevent the "Route [login] not defined" error
    }

    /**
     * Handle unauthenticated API requests directly.
     */
    protected function unauthenticated($request, array $guards)
    {
        // Return JSON 401 for any unauthenticated request
        abort(response()->json([
            'message' => 'Unauthenticated. Please login first.'
        ], 401));
    }
}