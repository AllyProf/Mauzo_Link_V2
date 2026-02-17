<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CounterSettingsController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Show counter settings page
     */
    public function index()
    {
        // Check permission - allow both bar_orders and inventory permissions
        if (!$this->hasPermission('bar_orders', 'view') && !$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to access counter settings.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get current settings with defaults
        $settings = [
            'auto_refresh_interval' => SystemSetting::get('counter_auto_refresh_interval_' . $ownerId, 30), // seconds
            'show_pending_orders_badge' => SystemSetting::get('counter_show_pending_orders_badge_' . $ownerId, true),
            'show_low_stock_alerts' => SystemSetting::get('counter_show_low_stock_alerts_' . $ownerId, true),
            'enable_sound_notifications' => SystemSetting::get('counter_enable_sound_notifications_' . $ownerId, false),
            'enable_order_notifications' => SystemSetting::get('counter_enable_order_notifications_' . $ownerId, true),
            'enable_stock_transfer_notifications' => SystemSetting::get('counter_enable_stock_transfer_notifications_' . $ownerId, true),
            'items_per_page' => SystemSetting::get('counter_items_per_page_' . $ownerId, 20),
            'default_order_view' => SystemSetting::get('counter_default_order_view_' . $ownerId, 'all'), // all, pending, today
            'show_revenue_stats' => SystemSetting::get('counter_show_revenue_stats_' . $ownerId, true),
            'notification_email' => SystemSetting::get('counter_notification_email_' . $ownerId, ''),
            'notification_phone' => SystemSetting::get('counter_notification_phone_' . $ownerId, ''),
        ];

        return view('bar.counter-settings.index', compact('settings'));
    }

    /**
     * Update counter settings
     */
    public function update(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('bar_orders', 'edit') && !$this->hasPermission('inventory', 'edit')) {
            abort(403, 'You do not have permission to update counter settings.');
        }

        $ownerId = $this->getOwnerId();

        $validator = Validator::make($request->all(), [
            'auto_refresh_interval' => 'required|integer|min:10|max:300',
            'items_per_page' => 'required|integer|min:10|max:100',
            'default_order_view' => 'required|in:all,pending,today',
            'show_pending_orders_badge' => 'boolean',
            'show_low_stock_alerts' => 'boolean',
            'enable_sound_notifications' => 'boolean',
            'enable_order_notifications' => 'boolean',
            'enable_stock_transfer_notifications' => 'boolean',
            'show_revenue_stats' => 'boolean',
            'notification_email' => 'nullable|email|max:255',
            'notification_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update settings with owner-specific keys
        SystemSetting::set('counter_auto_refresh_interval_' . $ownerId, $request->auto_refresh_interval, 'number', 'counter', 'Auto refresh interval for counter dashboard (seconds)');
        SystemSetting::set('counter_show_pending_orders_badge_' . $ownerId, $request->has('show_pending_orders_badge'), 'boolean', 'counter', 'Show pending orders badge');
        SystemSetting::set('counter_show_low_stock_alerts_' . $ownerId, $request->has('show_low_stock_alerts'), 'boolean', 'counter', 'Show low stock alerts on dashboard');
        SystemSetting::set('counter_enable_sound_notifications_' . $ownerId, $request->has('enable_sound_notifications'), 'boolean', 'counter', 'Enable sound notifications for new orders');
        SystemSetting::set('counter_enable_order_notifications_' . $ownerId, $request->has('enable_order_notifications'), 'boolean', 'counter', 'Enable notifications for new orders');
        SystemSetting::set('counter_enable_stock_transfer_notifications_' . $ownerId, $request->has('enable_stock_transfer_notifications'), 'boolean', 'counter', 'Enable notifications for stock transfers');
        SystemSetting::set('counter_items_per_page_' . $ownerId, $request->items_per_page, 'number', 'counter', 'Number of items to display per page');
        SystemSetting::set('counter_default_order_view_' . $ownerId, $request->default_order_view, 'text', 'counter', 'Default order view filter');
        SystemSetting::set('counter_show_revenue_stats_' . $ownerId, $request->has('show_revenue_stats'), 'boolean', 'counter', 'Show revenue statistics on dashboard');
        SystemSetting::set('counter_notification_email_' . $ownerId, $request->notification_email ?? '', 'text', 'counter', 'Email address for counter notifications');
        SystemSetting::set('counter_notification_phone_' . $ownerId, $request->notification_phone ?? '', 'text', 'counter', 'Phone number for counter notifications');

        return redirect()->back()->with('success', 'Counter settings updated successfully.');
    }
}


