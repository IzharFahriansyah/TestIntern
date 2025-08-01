<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user sudah login
        if (!Auth::check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please login first.'
            ], 401);
        }

        $user = Auth::user();

        // Cek apakah user object valid
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid user session.'
            ], 401);
        }

        // Cek apakah user memiliki role admin
        if (!isset($user->role) || $user->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        return $next($request);
    }
}
