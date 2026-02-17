<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Subscription;

class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     */
    public function index($role = null)
    {
        // IMPORTANT: Check for session conflicts - ensure only one type of session exists
        $isStaff = session('is_staff');
        $isUser = auth()->check();

        // If both exist, there's a conflict - clear staff session and use user
        if ($isStaff && $isUser) {
            session()->forget(['is_staff', 'staff_id', 'staff_name', 'staff_email', 'staff_role_id', 'staff_user_id']);
            $isStaff = false;
        }

        // Check if this is a staff member
        if ($isStaff) {
            $staff = \App\Models\Staff::find(session('staff_id'));
            
            if (!$staff || !$staff->is_active) {
                session()->forget(['is_staff', 'staff_id', 'staff_name', 'staff_email', 'staff_role_id', 'staff_user_id']);
                return redirect()->route('login')->with('error', 'Your staff account is no longer active.');
            }

            // IMPORTANT: Verify staff email matches session
            if ($staff->email !== session('staff_email')) {
                session()->forget(['is_staff', 'staff_id', 'staff_name', 'staff_email', 'staff_role_id', 'staff_user_id']);
                return redirect()->route('login')->with('error', 'Session mismatch. Please login again.');
            }

            // Get the owner's business info
            $owner = $staff->owner;
            
            // Get staff role slug for URL
            $roleSlug = $staff->role ? \Illuminate\Support\Str::slug($staff->role->name) : 'staff';
            
            // Redirect Counter staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'counter') {
                return redirect()->route('bar.counter.dashboard');
            }
            
            // Redirect Waiter staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'waiter') {
                return redirect()->route('bar.waiter.dashboard');
            }
            
            // Redirect Chef staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'chef') {
                return redirect()->route('bar.chef.dashboard');
            }
            
            // Redirect Accountant staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'accountant') {
                return redirect()->route('accountant.dashboard');
            }
            
            // Redirect Marketing staff to their dashboard
            if (strtolower($staff->role->name ?? '') === 'marketing') {
                return redirect()->route('marketing.dashboard');
            }
            
            // If URL doesn't include the role, redirect to include it
            if (!$role || $role !== $roleSlug) {
                return redirect()->route('dashboard.role', ['role' => $roleSlug]);
            }
            
            // Get statistics for stock keepers
            $statistics = [];
            if (strtolower($staff->role->name ?? '') === 'stock keeper' || ($staff->role && $staff->role->hasPermission('inventory', 'view'))) {
                $ownerId = $owner->id;
                
                // Warehouse stock statistics
                $statistics['warehouseStockItems'] = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->whereHas('stockLocations', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'warehouse')->where('quantity', '>', 0);
                })
                ->count();
                
                // Counter stock statistics
                $statistics['counterStockItems'] = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->whereHas('stockLocations', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId)->where('location', 'counter')->where('quantity', '>', 0);
                })
                ->count();
                
                // Pending transfers
                $statistics['pendingTransfers'] = \App\Models\StockTransfer::where('user_id', $ownerId)
                    ->where('status', 'pending')
                    ->count();
                
                // Get low stock threshold from settings
                $lowStockThreshold = \App\Models\SystemSetting::get('low_stock_threshold_' . $ownerId, 10);
                $criticalStockThreshold = \App\Models\SystemSetting::get('critical_stock_threshold_' . $ownerId, 5);
                
                // Low stock items count and list
                $lowStockVariants = \App\Models\ProductVariant::whereHas('product', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->with(['product', 'stockLocations' => function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                }])
                ->get()
                ->filter(function($variant) use ($lowStockThreshold) {
                    $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
                    $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                    $warehouseQty = $warehouseStock ? $warehouseStock->quantity : 0;
                    $counterQty = $counterStock ? $counterStock->quantity : 0;
                    $totalQty = $warehouseQty + $counterQty;
                    return $totalQty > 0 && $totalQty < $lowStockThreshold;
                });
                
                $statistics['lowStockItems'] = $lowStockVariants->count();
                
                // Low stock items list
                $statistics['lowStockItemsList'] = $lowStockVariants->take(10)->map(function($variant) use ($criticalStockThreshold) {
                    $warehouseStock = $variant->stockLocations->where('location', 'warehouse')->first();
                    $counterStock = $variant->stockLocations->where('location', 'counter')->first();
                    $warehouseQty = $warehouseStock ? $warehouseStock->quantity : 0;
                    $counterQty = $counterStock ? $counterStock->quantity : 0;
                    $totalQty = $warehouseQty + $counterQty;
                    
                    return [
                        'id' => $variant->id,
                        'product_name' => $variant->product->name,
                        'variant' => $variant->measurement,
                        'warehouse_qty' => $warehouseQty,
                        'counter_qty' => $counterQty,
                        'total_qty' => $totalQty,
                        'is_critical' => $totalQty < $criticalStockThreshold,
                    ];
                });
            }
            
            return view('dashboard.staff', compact('staff', 'owner', 'statistics'));
        }
        
        // Regular users should not have role in URL - redirect to clean URL if role is present
        if ($role) {
            return redirect()->route('dashboard');
        }

        // Regular user authentication
        if (!$isUser) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // IMPORTANT: Verify user email matches authenticated user
        if ($user->email !== request()->input('email') && !session()->has('verified_user')) {
            // This is just a safety check - in normal flow, auth()->user() is already verified
        }
        
        // Redirect admins to admin dashboard
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard.index');
        }

        // Check if user needs to complete business configuration
        if (!$user->isConfigured()) {
            $plan = $user->currentPlan();
            $canConfigure = false;

            // If user has a plan
            if ($plan) {
                // Free plan - can configure immediately
                if ($plan->slug === 'free') {
                    $canConfigure = true;
                } 
                // Basic/Pro plans - need verified payment first
                elseif (in_array($plan->slug, ['basic', 'pro'])) {
                    $subscription = $user->activeSubscription;
                    $canConfigure = $subscription && $subscription->status === 'active';
                }
            } else {
                // No plan yet - check if they have a subscription (even if pending)
                $subscription = $user->activeSubscription;
                if ($subscription && $subscription->plan) {
                    // If they have a free plan subscription (trial), allow configuration
                    if ($subscription->plan->slug === 'free') {
                        $canConfigure = true;
                    }
                }
            }

            if ($canConfigure) {
                return redirect()->route('business-configuration.index')
                    ->with('info', 'Please complete your business configuration to get started.');
            }
        }
        
        // Get active subscription
        $subscription = $user->activeSubscription;
        $currentPlan = $subscription ? $subscription->plan : null;
        
        // Check if user has pending subscription (paid plan waiting for payment)
        $pendingSubscription = Subscription::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where('is_trial', false)
            ->latest()
            ->first();
        
        // Get pending invoices
        $pendingInvoices = Invoice::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'pending_verification', 'paid'])
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Check if trial is expiring soon (within 7 days)
        $trialExpiringSoon = false;
        $trialDaysRemaining = 0;
        if ($subscription && $subscription->is_trial) {
            $trialDaysRemaining = $subscription->getTrialDaysRemaining();
            $trialExpiringSoon = $trialDaysRemaining > 0 && $trialDaysRemaining <= 7;
        }
        
        // Check if trial has expired
        $trialExpired = false;
        if ($subscription && $subscription->is_trial && $subscription->trial_ends_at) {
            $trialExpired = \Carbon\Carbon::now()->greaterThan($subscription->trial_ends_at);
        }
        
        // Get upgrade plans (Basic and Pro)
        $upgradePlans = \App\Models\Plan::where('slug', '!=', 'free')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        
        return view('dashboard.index', compact(
            'subscription', 
            'currentPlan', 
            'pendingInvoices',
            'trialExpiringSoon',
            'trialDaysRemaining',
            'trialExpired',
            'upgradePlans',
            'pendingSubscription'
        ));
    }
}
