<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ApiAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'message' => 'No token provided. Please login first.'
            ], 401);
        }

        // Find the token with the user relationship
        $accessToken = PersonalAccessToken::with('tokenable')
            ->where('token', hash('sha256', $token))
            ->first();
        
        if (!$accessToken) {
            return response()->json([
                'message' => 'Invalid token. Please login again.'
            ], 401);
        }

        // Get the user from the token
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found. Please login again.'
            ], 401);
        }

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is deactivated.'
            ], 403);
        }

        // Login the user
        auth()->login($user);

        return $next($request);
    }
}