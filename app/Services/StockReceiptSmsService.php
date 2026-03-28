<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\StockReceipt;
use App\Models\Staff;
use App\Models\SystemSetting;

class StockReceiptSmsService
{
    protected $smsService;

    public function __construct()
    {
        $this->smsService = new SmsService();
    }

    /**
     * Send stock receipt notification SMS to stock keeper and counter staff
     */
    public function sendStockReceiptNotification(StockReceipt $stockReceipt, $ownerId)
    {
        // Check if notifications are enabled
        $enableNotifications = SystemSetting::get('enable_stock_receipt_sms_' . $ownerId, true);
        
        if (!$enableNotifications) {
            Log::info('Stock receipt SMS notifications are disabled', [
                'receipt_id' => $stockReceipt->id,
                'owner_id' => $ownerId
            ]);
            return false;
        }

        $product = $stockReceipt->productVariant->product;
        $variant = $stockReceipt->productVariant;
        $supplier = $stockReceipt->supplier;

        // Build simple message for admin / Manager
        $stockMessage = "STOCK RECEIVED!\n\n";
        $stockMessage .= "{$product->name} ({$variant->measurement})\n";
        $stockMessage .= "Qty: {$stockReceipt->quantity_received} {$variant->packaging}\n";
        $stockMessage .= "Total: " . number_format($stockReceipt->total_units) . " Btls\n";
        $stockMessage .= "Supplier: {$supplier->company_name}\n";
        $stockMessage .= "\nLogin to your account for full receipt details.";

        // Build simple message for counter
        $counterMessage = "NEW STOCK ADDED!\n\n";
        $counterMessage .= "{$product->name} ({$variant->measurement})\n";
        $counterMessage .= "Added: {$stockReceipt->quantity_received} {$variant->packaging}\n";
        $counterMessage .= "Total: " . number_format($stockReceipt->total_units) . " Btls\n";
        $counterMessage .= "\nCheck your counter stock list for details.";

        $sentCount = 0;
        $failedCount = 0;

        // Send SMS to Stock Keepers
        $stockKeepers = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->whereIn('slug', ['stock-keeper', 'stockkeeper']);
            })
            ->get();

        foreach ($stockKeepers as $stockKeeper) {
            if ($stockKeeper->phone_number) {
                $result = $this->smsService->sendSms($stockKeeper->phone_number, $stockMessage);
                
                if ($result['success']) {
                    $sentCount++;
                    Log::info('Stock receipt SMS sent to stock keeper', [
                        'stock_keeper_id' => $stockKeeper->id,
                        'receipt_id' => $stockReceipt->id,
                        'phone' => $stockKeeper->phone_number
                    ]);
                } else {
                    $failedCount++;
                    Log::error('Failed to send stock receipt SMS to stock keeper', [
                        'stock_keeper_id' => $stockKeeper->id,
                        'receipt_id' => $stockReceipt->id,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            }
        }

        // Send SMS to Counter Staff
        $counterStaff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->whereIn('slug', ['counter', 'bar-counter', 'bar_counter']);
            })
            ->get();

        foreach ($counterStaff as $counter) {
            if ($counter->phone_number) {
                $this->smsService->sendSms($counter->phone_number, $counterMessage);
                $sentCount++;
            }
        }

        // Send SMS to Managers & Super Admins
        $admins = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->whereIn('slug', ['manager', 'super-admin', 'superadmin']);
            })
            ->get();

        foreach ($admins as $admin) {
            if ($admin->phone_number) {
                $this->smsService->sendSms($admin->phone_number, $stockMessage);
                $sentCount++;
                Log::info('Stock receipt SMS sent to admin/manager', [
                    'staff_id' => $admin->id,
                    'role' => $admin->role->name,
                    'phone' => $admin->phone_number
                ]);
            }
        }

        // Also send to additional phone numbers from settings
        $additionalPhones = SystemSetting::get('low_stock_notification_phones_' . $ownerId, '');
        if ($additionalPhones) {
            $phones = array_map('trim', explode(',', $additionalPhones));
            foreach ($phones as $phone) {
                if (!empty($phone)) {
                    $result = $this->smsService->sendSms($phone, $stockMessage);
                    
                    if ($result['success']) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }
        }

        Log::info('Stock receipt SMS notifications completed', [
            'receipt_id' => $stockReceipt->id,
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);

        return $sentCount > 0;
    }
}

