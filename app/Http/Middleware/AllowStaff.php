<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow if user is authenticated OR if staff session exists
        if (auth()->check() || session('is_staff')) {
            return $next($request);
        }

        // If neither user nor staff is authenticated, redirect to login
        return redirect()->route('login');
    }
}




