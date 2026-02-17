<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\Subscription;

class RequirePayment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Allow staff sessions
        if (session('is_staff')) {
            return $next($request);
        }

        // Check if user is authenticated first
        if (!Auth::check()) {
            // Try to recover session if it exists but Auth::check() fails
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
                \Log::warning('Session re-authentication failed in RequirePayment middleware', [
                    'error' => $e->getMessage(),
                    'route' => $request->route()?->getName(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            // No valid session found - redirect to login
            return redirect()->route('login')->with('error', 'Your session has expired. Please login again.');
        }

        // Allow admins to access everything (check after Auth::check() passes)
        if (Auth::user()->isAdmin()) {
            return $next($request);
        }

        $user = Auth::user();

        // Check if user has an active subscription (trial or paid)
        // Get the latest subscription regardless of status
        $subscription = Subscription::where('user_id', $user->id)->latest()->first();
        
        // If user has active subscription (trial or paid), allow access
        if ($subscription && $subscription->isActive()) {
            return $next($request);
        }

        // Check if user has pending invoices that require payment
        $pendingInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'pending_verification', 'paid'])
            ->exists();

        // Check if user has pending subscription (selected plan but not paid)
        $pendingSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('is_trial', false)
            ->exists();

        // If user has pending payment or pending subscription, block access
        if ($pendingInvoices || $pendingSubscription) {
            // Allow access to payment-related routes
            $allowedRoutes = [
                'dashboard',
                'payments.',
                'invoices.',
                'upgrade.',
                'settings.',
                'logout',
            ];

            $routeName = $request->route() ? $request->route()->getName() : null;

            // Check if current route is in allowed list
            $isAllowed = false;
            if ($routeName) {
                foreach ($allowedRoutes as $allowedRoute) {
                    if (strpos($routeName, $allowedRoute) === 0) {
                        $isAllowed = true;
                        break;
                    }
                }
            }

            if (!$isAllowed) {
                return redirect()->route('dashboard')
                    ->with('error', 'Please complete your payment to access this feature. You have pending invoice(s) that require payment.');
            }
        }

        return $next($request);
    }
}

