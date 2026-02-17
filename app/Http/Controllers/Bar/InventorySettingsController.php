<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventorySettingsController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Show inventory settings page
     */
    public function index()
    {
        // Check permission - allow both bar_orders and inventory permissions
        if (!$this->hasPermission('bar_orders', 'view') && !$this->hasPermission('inventory', 'view')) {
            abort(403, 'You do not have permission to access inventory settings.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get current settings with defaults
        $settings = [
            'low_stock_threshold' => SystemSetting::get('low_stock_threshold_' . $ownerId, 10),
            'enable_low_stock_sms' => SystemSetting::get('enable_low_stock_sms_' . $ownerId, false),
            'low_stock_notification_phones' => SystemSetting::get('low_stock_notification_phones_' . $ownerId, ''),
            'enable_auto_transfer_notification' => SystemSetting::get('enable_auto_transfer_notification_' . $ownerId, true), // Default to true
            'critical_stock_threshold' => SystemSetting::get('critical_stock_threshold_' . $ownerId, 5),
            'enable_critical_stock_sms' => SystemSetting::get('enable_critical_stock_sms_' . $ownerId, true),
            'enable_stock_receipt_sms' => SystemSetting::get('enable_stock_receipt_sms_' . $ownerId, true),
        ];

        return view('bar.inventory-settings.index', compact('settings'));
    }

    /**
     * Update inventory settings
     */
    public function update(Request $request)
    {
        // Check permission
        if (!$this->hasPermission('inventory', 'edit') && !$this->hasPermission('bar_orders', 'edit')) {
            abort(403, 'You do not have permission to update inventory settings.');
        }

        $ownerId = $this->getOwnerId();

        $validator = Validator::make($request->all(), [
            'low_stock_threshold' => 'required|integer|min:1|max:1000',
            'critical_stock_threshold' => 'required|integer|min:1|max:1000',
            'enable_low_stock_sms' => 'boolean',
            'enable_critical_stock_sms' => 'boolean',
            'enable_auto_transfer_notification' => 'boolean',
            'enable_stock_receipt_sms' => 'boolean',
            'low_stock_notification_phones' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update settings with owner-specific keys
        SystemSetting::set('low_stock_threshold_' . $ownerId, $request->low_stock_threshold, 'number', 'inventory', 'Low stock threshold for inventory alerts');
        SystemSetting::set('critical_stock_threshold_' . $ownerId, $request->critical_stock_threshold, 'number', 'inventory', 'Critical stock threshold for urgent alerts');
        SystemSetting::set('enable_low_stock_sms_' . $ownerId, $request->has('enable_low_stock_sms'), 'boolean', 'inventory', 'Enable SMS notifications for low stock');
        SystemSetting::set('enable_critical_stock_sms_' . $ownerId, $request->has('enable_critical_stock_sms'), 'boolean', 'inventory', 'Enable SMS notifications for critical stock');
        SystemSetting::set('enable_auto_transfer_notification_' . $ownerId, $request->has('enable_auto_transfer_notification'), 'boolean', 'inventory', 'Enable notifications for stock transfers');
        SystemSetting::set('enable_stock_receipt_sms_' . $ownerId, $request->has('enable_stock_receipt_sms'), 'boolean', 'inventory', 'Enable SMS notifications when stock is received');
        SystemSetting::set('low_stock_notification_phones_' . $ownerId, $request->low_stock_notification_phones ?? '', 'text', 'inventory', 'Phone numbers to receive low stock alerts (comma-separated)');

        return redirect()->back()->with('success', 'Inventory settings updated successfully.');
    }
}

