<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RequireConfiguration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow staff sessions
        if (session('is_staff')) {
            return $next($request);
        }

        // Allow access to configuration routes
        if ($request->routeIs('business-configuration.*')) {
            return $next($request);
        }

        // Allow access to payment routes
        if ($request->routeIs('payments.*') || $request->routeIs('invoices.*') || $request->routeIs('upgrade.*')) {
            return $next($request);
        }

        // Check if user is authenticated first
        if (!auth()->check()) {
            // Try to recover session if it exists but auth()->check() fails
            // This handles cases where session cookie might not be recognized immediately
            try {
                // Get the session key that Laravel uses for authentication
                $guard = Auth::guard('web');
                $sessionKey = $guard->getName();
                
                // Check if session has the authentication key
                if ($request->hasSession() && $request->session()->has($sessionKey)) {
                    $userId = $request->session()->get($sessionKey);
                    if ($userId) {
                        $user = \App\Models\User::find($userId);
                        if ($user) {
                            // Re-authenticate the user without regenerating session
                            Auth::login($user, false); // false = don't regenerate session
                            // If user is admin, allow access immediately
                            if ($user->isAdmin()) {
                                return $next($request);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // If re-authentication fails, log and continue to login redirect
                \Log::warning('Session re-authentication failed in RequireConfiguration middleware', [
                    'error' => $e->getMessage(),
                    'route' => $request->route()?->getName(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // No valid session found - redirect to login
            return redirect()->route('login')->with('error', 'Your session has expired. Please login again.');
        }

        // Allow admins to access everything (check after auth()->check() passes)
        if (auth()->user()->isAdmin()) {
            return $next($request);
        }

        $user = auth()->user();

        // Check if user has completed configuration
        if (!$user->isConfigured()) {
            // Check if user can access configuration
            $plan = $user->currentPlan();
            $canConfigure = false;

            if ($plan && $plan->slug === 'free') {
                $canConfigure = true;
            } elseif ($plan && in_array($plan->slug, ['basic', 'pro'])) {
                $subscription = $user->activeSubscription;
                $canConfigure = $subscription && $subscription->status === 'active';
            }

            if ($canConfigure) {
                return redirect()->route('business-configuration.index')
                    ->with('info', 'Please complete your business configuration to continue.');
            } else {
                // If can't configure yet, allow access but show message
                // This prevents redirect loops
                return $next($request);
            }
        }

        return $next($request);
    }
}
