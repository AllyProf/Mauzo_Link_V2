<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Staff;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('Authorization');
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. No token provided.'
            ], 401);
        }

        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);
        $hashedToken = hash('sha256', $token);

        $staff = Staff::where('api_token', $hashedToken)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('api_token_expires_at')
                    ->orWhere('api_token_expires_at', '>', now());
            })
            ->with('role')
            ->first();

        // Verify waiter role
        if (!$staff || !$staff->role || strtolower($staff->role->name) !== 'waiter') {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Invalid or expired token.'
            ], 401);
        }

        // Attach staff to request for use in controllers
        $request->merge(['authenticated_staff' => $staff]);

        return $next($request);
    }
}




