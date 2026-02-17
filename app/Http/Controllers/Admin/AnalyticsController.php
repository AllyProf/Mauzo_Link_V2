<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Show analytics and reports
     */
    public function index(Request $request)
    {
        // Date range (default: last 30 days)
        $dateFrom = $request->get('date_from', Carbon::now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::now()->format('Y-m-d'));

        // Revenue Analytics
        $totalRevenue = Payment::where('status', 'verified')
            ->whereBetween('verified_at', [$dateFrom, $dateTo])
            ->sum('amount');
        
        $monthlyRevenue = Payment::where('status', 'verified')
            ->whereMonth('verified_at', Carbon::now()->month)
            ->whereYear('verified_at', Carbon::now()->year)
            ->sum('amount');

        // User Analytics (Customers only, excluding admins)
        $totalUsers = User::customers()->count();
        $newUsers = User::customers()
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();
        
        $activeUsers = User::customers()
            ->whereHas('subscriptions', function($q) {
                $q->where('status', 'active')
                  ->orWhere(function($query) {
                      $query->where('status', 'trial')->where('is_trial', true);
                  });
            })
            ->count();

        // Subscription Analytics
        $totalSubscriptions = Subscription::whereBetween('created_at', [$dateFrom, $dateTo])->count();
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $trialSubscriptions = Subscription::where('status', 'trial')->where('is_trial', true)->count();

        // Plan Performance
        $planPerformance = Plan::withCount('subscriptions')
            ->where('is_active', true)
            ->get()
            ->map(function($plan) use ($dateFrom, $dateTo) {
                $revenue = Payment::whereHas('invoice', function($q) use ($plan) {
                    $q->where('plan_id', $plan->id);
                })
                ->where('status', 'verified')
                ->whereBetween('verified_at', [$dateFrom, $dateTo])
                ->sum('amount');
                
                return [
                    'name' => $plan->name,
                    'subscriptions' => $plan->subscriptions_count,
                    'revenue' => $revenue,
                    'price' => $plan->price
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // Daily Revenue Chart (Last 30 days)
        $dailyRevenueData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $revenue = Payment::where('status', 'verified')
                ->whereDate('verified_at', $date->format('Y-m-d'))
                ->sum('amount');
            
            $dailyRevenueData[] = [
                'date' => $date->format('M d'),
                'revenue' => $revenue
            ];
        }

        // Monthly Revenue Chart (Last 12 months)
        $monthlyRevenueChart = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenue = Payment::where('status', 'verified')
                ->whereMonth('verified_at', $month->month)
                ->whereYear('verified_at', $month->year)
                ->sum('amount');
            
            $monthlyRevenueChart[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue
            ];
        }

        // User Growth Chart (Last 12 months)
        $userGrowthChart = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $users = User::customers()
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->count();
            
            $userGrowthChart[] = [
                'month' => $month->format('M Y'),
                'users' => $users
            ];
        }

        // Top Customers by Revenue
        $topCustomers = User::customers()
            ->withSum(['payments as total_paid' => function($q) {
                $q->where('status', 'verified');
            }], 'amount')
            ->orderBy('total_paid', 'desc')
            ->limit(10)
            ->get();

        return view('admin.analytics.index', compact(
            'dateFrom',
            'dateTo',
            'totalRevenue',
            'monthlyRevenue',
            'totalUsers',
            'newUsers',
            'activeUsers',
            'totalSubscriptions',
            'activeSubscriptions',
            'trialSubscriptions',
            'planPerformance',
            'dailyRevenueData',
            'monthlyRevenueChart',
            'userGrowthChart',
            'topCustomers'
        ));
    }
}
