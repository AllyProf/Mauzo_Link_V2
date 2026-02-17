<?php

namespace App\Services;

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
            \Log::info('Stock receipt SMS notifications are disabled', [
                'receipt_id' => $stockReceipt->id,
                'owner_id' => $ownerId
            ]);
            return false;
        }

        $product = $stockReceipt->productVariant->product;
        $variant = $stockReceipt->productVariant;
        $supplier = $stockReceipt->supplier;

        // Build message for stock keeper
        $stockKeeperMessage = "STOCK RECEIPT NOTIFICATION\n\n";
        $stockKeeperMessage .= "Receipt #: {$stockReceipt->receipt_number}\n";
        $stockKeeperMessage .= "Product: {$product->name}\n";
        $stockKeeperMessage .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $stockKeeperMessage .= "Supplier: {$supplier->company_name}\n";
        $stockKeeperMessage .= "Quantity: {$stockReceipt->quantity_received} {$variant->packaging}\n";
        $stockKeeperMessage .= "Total Units: " . number_format($stockReceipt->total_units) . " bottle(s)\n";
        $stockKeeperMessage .= "Date: " . $stockReceipt->received_date->format('M d, Y') . "\n";
        $stockKeeperMessage .= "\nStock has been added to warehouse.";

        // Build message for counter staff
        $counterMessage = "NEW STOCK AVAILABLE FOR REQUEST\n\n";
        $counterMessage .= "Product: {$product->name}\n";
        $counterMessage .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $counterMessage .= "Available: {$stockReceipt->quantity_received} {$variant->packaging}\n";
        $counterMessage .= "Total Units: " . number_format($stockReceipt->total_units) . " bottle(s)\n";
        $counterMessage .= "Receipt #: {$stockReceipt->receipt_number}\n";
        $counterMessage .= "\nYou can now request stock transfer from warehouse to counter.";

        $sentCount = 0;
        $failedCount = 0;

        // Send SMS to Stock Keepers
        $stockKeepers = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->where(function($q) {
                    $q->where('name', 'Stock Keeper')
                      ->orWhere('name', 'Stockkeeper');
                });
            })
            ->get();

        foreach ($stockKeepers as $stockKeeper) {
            if ($stockKeeper->phone_number) {
                $result = $this->smsService->sendSms($stockKeeper->phone_number, $stockKeeperMessage);
                
                if ($result['success']) {
                    $sentCount++;
                    \Log::info('Stock receipt SMS sent to stock keeper', [
                        'stock_keeper_id' => $stockKeeper->id,
                        'receipt_id' => $stockReceipt->id,
                        'phone' => $stockKeeper->phone_number
                    ]);
                } else {
                    $failedCount++;
                    \Log::error('Failed to send stock receipt SMS to stock keeper', [
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
                $query->where('name', 'Counter')
                      ->orWhere('name', 'Bar Counter');
            })
            ->get();

        foreach ($counterStaff as $counter) {
            if ($counter->phone_number) {
                $result = $this->smsService->sendSms($counter->phone_number, $counterMessage);
                
                if ($result['success']) {
                    $sentCount++;
                    \Log::info('Stock receipt SMS sent to counter staff', [
                        'counter_id' => $counter->id,
                        'receipt_id' => $stockReceipt->id,
                        'phone' => $counter->phone_number
                    ]);
                } else {
                    $failedCount++;
                    \Log::error('Failed to send stock receipt SMS to counter staff', [
                        'counter_id' => $counter->id,
                        'receipt_id' => $stockReceipt->id,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            }
        }

        // Also send to additional phone numbers from settings
        $additionalPhones = SystemSetting::get('low_stock_notification_phones_' . $ownerId, '');
        if ($additionalPhones) {
            $phones = array_map('trim', explode(',', $additionalPhones));
            foreach ($phones as $phone) {
                if (!empty($phone)) {
                    $result = $this->smsService->sendSms($phone, $stockKeeperMessage);
                    
                    if ($result['success']) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }
        }

        \Log::info('Stock receipt SMS notifications completed', [
            'receipt_id' => $stockReceipt->id,
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);

        return $sentCount > 0;
    }
}

