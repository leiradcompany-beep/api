<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureCustomerAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Validate incoming bearer token & Verify authenticity
        // We use the 'sanctum' guard to check authentication explicitly.
        if (!Auth::guard('sanctum')->check()) {
             return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Valid Bearer token required.',
                'error_code' => 'AUTH_REQUIRED'
            ], 401);
        }

        $user = Auth::guard('sanctum')->user();

        // 2. Check for proper user permissions
        if ($user->role !== 'customer') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Insufficient permissions.',
                'error_code' => 'FORBIDDEN'
            ], 403);
        }

        // Set the user for the request (so $request->user() works in controllers)
        // This ensures subsequent code uses the sanctum-authenticated user
        Auth::setUser($user);

        return $next($request);
    }
}
