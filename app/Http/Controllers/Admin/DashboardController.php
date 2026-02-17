<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show admin dashboard with overview statistics
     */
    public function index()
    {
        // Total Users (Customers only, excluding admins)
        $totalUsers = User::customers()->count();
        $newUsersThisMonth = User::customers()
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        $newUsersLastMonth = User::customers()
            ->whereMonth('created_at', Carbon::now()->subMonth()->month)
            ->whereYear('created_at', Carbon::now()->subMonth()->year)
            ->count();
        $userGrowth = $newUsersLastMonth > 0 
            ? round((($newUsersThisMonth - $newUsersLastMonth) / $newUsersLastMonth) * 100, 1)
            : ($newUsersThisMonth > 0 ? 100 : 0);

        // Total Subscriptions
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $trialSubscriptions = Subscription::where('status', 'trial')->where('is_trial', true)->count();
        $pendingSubscriptions = Subscription::where('status', 'pending')->count();

        // Revenue Statistics
        $totalRevenue = Payment::where('status', 'verified')
            ->sum('amount');
        $monthlyRevenue = Payment::where('status', 'verified')
            ->whereMonth('verified_at', Carbon::now()->month)
            ->whereYear('verified_at', Carbon::now()->year)
            ->sum('amount');
        $lastMonthRevenue = Payment::where('status', 'verified')
            ->whereMonth('verified_at', Carbon::now()->subMonth()->month)
            ->whereYear('verified_at', Carbon::now()->subMonth()->year)
            ->sum('amount');
        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : ($monthlyRevenue > 0 ? 100 : 0);

        // Pending Payments
        $pendingPayments = Payment::where('status', 'pending')->count();
        $pendingPaymentsAmount = Payment::where('status', 'pending')->sum('amount');
        
        // Pending Payment Verifications (recently uploaded receipts)
        $pendingVerifications = Payment::with(['user', 'invoice.plan'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Plan Distribution
        $planDistribution = Plan::withCount('subscriptions')
            ->where('is_active', true)
            ->get()
            ->map(function($plan) {
                return [
                    'name' => $plan->name,
                    'count' => $plan->subscriptions_count,
                    'revenue' => Payment::whereHas('invoice', function($q) use ($plan) {
                        $q->where('plan_id', $plan->id);
                    })->where('status', 'verified')->sum('amount')
                ];
            })
            ->sortByDesc('count')
            ->values();

        // Recent Activities
        $recentUsers = User::customers()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $recentPayments = Payment::with(['user', 'invoice.plan'])
            ->where('status', 'verified')
            ->whereHas('invoice.plan', function($q) {
                $q->where('is_active', true);
            })
            ->orderBy('verified_at', 'desc')
            ->limit(5)
            ->get();

        // Recent Subscriptions
        $recentSubscriptions = Subscription::with(['user', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Monthly Revenue Chart Data (Last 6 months)
        $monthlyRevenueData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = Payment::where('status', 'verified')
                ->whereMonth('verified_at', $month->month)
                ->whereYear('verified_at', $month->year)
                ->sum('amount');
            
            $monthlyRevenueData[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue
            ];
        }

        // User Growth Chart Data (Last 6 months)
        $monthlyUserGrowthData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $users = User::customers()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
            
            $monthlyUserGrowthData[] = [
                'month' => $month->format('M Y'),
                'users' => $users
            ];
        }

        return view('admin.dashboard.index', compact(
            'totalUsers',
            'newUsersThisMonth',
            'userGrowth',
            'totalSubscriptions',
            'activeSubscriptions',
            'trialSubscriptions',
            'pendingSubscriptions',
            'totalRevenue',
            'monthlyRevenue',
            'revenueGrowth',
            'pendingPayments',
            'pendingPaymentsAmount',
            'pendingVerifications',
            'planDistribution',
            'recentUsers',
            'recentPayments',
            'recentSubscriptions',
            'monthlyRevenueData',
            'monthlyUserGrowthData'
        ));
    }
}
