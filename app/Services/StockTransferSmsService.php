<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\Staff;
use App\Models\SystemSetting;

class StockTransferSmsService
{
    protected $smsService;

    public function __construct()
    {
        $this->smsService = new SmsService();
    }

    /**
     * Send stock transfer request notification SMS to stock keeper
     */
    public function sendTransferRequestNotification(StockTransfer $stockTransfer, $ownerId)
    {
        \Log::info('sendTransferRequestNotification called', [
            'transfer_id' => $stockTransfer->id,
            'owner_id' => $ownerId,
            'transfer_number' => $stockTransfer->transfer_number ?? 'N/A'
        ]);
        
        // Check if notifications are enabled
        $settingKey = 'enable_auto_transfer_notification_' . $ownerId;
        $enableNotifications = SystemSetting::get($settingKey, true);
        
        \Log::info('Notification setting check', [
            'setting_key' => $settingKey,
            'enabled' => $enableNotifications ? 'true' : 'false',
            'transfer_id' => $stockTransfer->id
        ]);
        
        if (!$enableNotifications) {
            \Log::info('Stock transfer SMS notifications are disabled', [
                'transfer_id' => $stockTransfer->id,
                'owner_id' => $ownerId,
                'setting_key' => $settingKey
            ]);
            return false;
        }

        // Ensure relationships are loaded
        if (!$stockTransfer->relationLoaded('productVariant')) {
            $stockTransfer->load('productVariant');
        }
        
        if (!$stockTransfer->productVariant) {
            \Log::error('Product variant not found for stock transfer SMS', [
                'transfer_id' => $stockTransfer->id,
                'product_variant_id' => $stockTransfer->product_variant_id
            ]);
            return false;
        }
        
        if (!$stockTransfer->productVariant->relationLoaded('product')) {
            $stockTransfer->productVariant->load('product');
        }
        
        $product = $stockTransfer->productVariant->product;
        $variant = $stockTransfer->productVariant;
        
        if (!$product || !$variant) {
            \Log::error('Product or variant not found for stock transfer SMS', [
                'transfer_id' => $stockTransfer->id,
                'product_variant_id' => $stockTransfer->product_variant_id,
                'has_variant' => $stockTransfer->productVariant ? 'yes' : 'no',
                'has_product' => $product ? 'yes' : 'no'
            ]);
            return false;
        }
        
        // Try to get the staff member who requested (if it was a staff member)
        $requestedByStaff = null;
        if (session('is_staff') && session('staff_id')) {
            $requestedByStaff = \App\Models\Staff::find(session('staff_id'));
        }
        
        $requestedByName = 'Counter';
        if ($requestedByStaff) {
            $requestedByName = $requestedByStaff->full_name;
        } elseif ($stockTransfer->requestedBy) {
            $requestedByName = $stockTransfer->requestedBy->name;
        }

        // Build message for stock keeper
        $message = "NEW STOCK TRANSFER REQUEST\n\n";
        $message .= "Transfer #: {$stockTransfer->transfer_number}\n";
        $message .= "Product: {$product->name}\n";
        $message .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $message .= "Quantity: {$stockTransfer->quantity_requested} {$variant->packaging}\n";
        $message .= "Total Units: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
        $message .= "Requested By: {$requestedByName}\n";
        $message .= "Date: " . $stockTransfer->created_at->format('M d, Y H:i') . "\n";
        if ($stockTransfer->notes) {
            $message .= "Notes: {$stockTransfer->notes}\n";
        }
        $message .= "\nPlease review and approve/reject this transfer request.";

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
        
        \Log::info('Stock transfer request SMS - Stock keepers found', [
            'transfer_id' => $stockTransfer->id,
            'stock_keepers_count' => $stockKeepers->count(),
            'owner_id' => $ownerId,
            'message_preview' => substr($message, 0, 100),
            'notification_enabled' => $enableNotifications
        ]);

        if ($stockKeepers->count() === 0) {
            \Log::warning('No stock keepers found for SMS notification', [
                'transfer_id' => $stockTransfer->id,
                'owner_id' => $ownerId
            ]);
        }

        foreach ($stockKeepers as $stockKeeper) {
            if ($stockKeeper->phone_number) {
                \Log::info('Attempting to send SMS to stock keeper', [
                    'stock_keeper_id' => $stockKeeper->id,
                    'phone' => $stockKeeper->phone_number,
                    'transfer_id' => $stockTransfer->id
                ]);
                
                $result = $this->smsService->sendSms($stockKeeper->phone_number, $message);
                
                if ($result['success']) {
                    $sentCount++;
                    \Log::info('Stock transfer request SMS sent to stock keeper', [
                        'stock_keeper_id' => $stockKeeper->id,
                        'transfer_id' => $stockTransfer->id,
                        'phone' => $stockKeeper->phone_number,
                        'http_code' => $result['http_code'] ?? 'N/A'
                    ]);
                } else {
                    $failedCount++;
                    \Log::error('Failed to send stock transfer request SMS to stock keeper', [
                        'stock_keeper_id' => $stockKeeper->id,
                        'transfer_id' => $stockTransfer->id,
                        'phone' => $stockKeeper->phone_number,
                        'error' => $result['error'] ?? 'Unknown error',
                        'http_code' => $result['http_code'] ?? 'N/A',
                        'response' => $result['response'] ?? 'N/A'
                    ]);
                }
            } else {
                \Log::warning('Stock keeper has no phone number', [
                    'stock_keeper_id' => $stockKeeper->id,
                    'transfer_id' => $stockTransfer->id
                ]);
            }
        }

        // Also send to additional phone numbers from settings
        $additionalPhones = SystemSetting::get('low_stock_notification_phones_' . $ownerId, '');
        if ($additionalPhones) {
            $phones = array_map('trim', explode(',', $additionalPhones));
            foreach ($phones as $phone) {
                if (!empty($phone)) {
                    $result = $this->smsService->sendSms($phone, $message);
                    
                    if ($result['success']) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }
        }

        \Log::info('Stock transfer request SMS notifications completed', [
            'transfer_id' => $stockTransfer->id,
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);

        return $sentCount > 0;
    }

    /**
     * Send stock transfer approval/rejection notification SMS to counter staff
     */
    public function sendTransferStatusNotification(StockTransfer $stockTransfer, $status, $ownerId, $reason = null)
    {
        // Check if notifications are enabled
        $enableNotifications = SystemSetting::get('enable_auto_transfer_notification_' . $ownerId, true);
        
        if (!$enableNotifications) {
            return false;
        }

        $product = $stockTransfer->productVariant->product;
        $variant = $stockTransfer->productVariant;
        $approvedBy = $stockTransfer->approvedBy;

        if ($status === 'approved') {
            // Build approval message for counter staff
            $message = "STOCK TRANSFER APPROVED\n\n";
            $message .= "Transfer #: {$stockTransfer->transfer_number}\n";
            $message .= "Product: {$product->name}\n";
            $message .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
            $message .= "Quantity: {$stockTransfer->quantity_requested} {$variant->packaging}\n";
            $message .= "Total Units: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
            $message .= "Approved By: " . ($approvedBy ? $approvedBy->name : 'Stock Keeper') . "\n";
            $message .= "Date: " . ($stockTransfer->approved_at ? $stockTransfer->approved_at->format('M d, Y H:i') : now()->format('M d, Y H:i')) . "\n";
            $message .= "\nStock is ready for transfer to counter.";
        } else if ($status === 'prepared') {
            // Build prepared message for counter staff
            $message = "STOCK TRANSFER PREPARED\n\n";
            $message .= "Transfer #: {$stockTransfer->transfer_number}\n";
            $message .= "Product: {$product->name}\n";
            $message .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
            $message .= "Quantity: {$stockTransfer->quantity_requested} {$variant->packaging}\n";
            $message .= "Total Units: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
            $message .= "Date: " . now()->format('M d, Y H:i') . "\n";
            $message .= "\nStock is prepared and ready to be moved to counter.";
        } else if ($status === 'rejected') {
            // Build rejection message for counter staff
            $message = "STOCK TRANSFER REJECTED\n\n";
            $message .= "Transfer #: {$stockTransfer->transfer_number}\n";
            $message .= "Product: {$product->name}\n";
            $message .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
            $message .= "Quantity: {$stockTransfer->quantity_requested} {$variant->packaging}\n";
            $message .= "Rejected By: " . ($approvedBy ? $approvedBy->name : 'Stock Keeper') . "\n";
            if ($reason) {
                $message .= "Reason: {$reason}\n";
            }
            $message .= "Date: " . ($stockTransfer->approved_at ? $stockTransfer->approved_at->format('M d, Y H:i') : now()->format('M d, Y H:i')) . "\n";
            $message .= "\nPlease contact stock keeper for more information.";
        } else {
            return false;
        }

        $sentCount = 0;
        $failedCount = 0;

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
                $result = $this->smsService->sendSms($counter->phone_number, $message);
                
                if ($result['success']) {
                    $sentCount++;
                    \Log::info("Stock transfer {$status} SMS sent to counter staff", [
                        'counter_id' => $counter->id,
                        'transfer_id' => $stockTransfer->id,
                        'phone' => $counter->phone_number,
                        'status' => $status
                    ]);
                } else {
                    $failedCount++;
                    \Log::error("Failed to send stock transfer {$status} SMS to counter staff", [
                        'counter_id' => $counter->id,
                        'transfer_id' => $stockTransfer->id,
                        'error' => $result['error'] ?? 'Unknown error'
                    ]);
                }
            }
        }

        // Also send to the person who requested the transfer if they have a phone number
        if ($stockTransfer->requestedBy && $stockTransfer->requestedBy->phone_number) {
            $result = $this->smsService->sendSms($stockTransfer->requestedBy->phone_number, $message);
            if ($result['success']) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        \Log::info("Stock transfer {$status} SMS notifications completed", [
            'transfer_id' => $stockTransfer->id,
            'status' => $status,
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);

        return $sentCount > 0;
    }

    /**
     * Send stock transfer completion notification SMS
     */
    public function sendTransferCompletedNotification(StockTransfer $stockTransfer, $ownerId)
    {
        // Check if notifications are enabled
        $enableNotifications = SystemSetting::get('enable_auto_transfer_notification_' . $ownerId, true);
        
        if (!$enableNotifications) {
            return false;
        }

        $product = $stockTransfer->productVariant->product;
        $variant = $stockTransfer->productVariant;

        // Build message
        $message = "STOCK TRANSFER COMPLETED\n\n";
        $message .= "Transfer #: {$stockTransfer->transfer_number}\n";
        $message .= "Product: {$product->name}\n";
        $message .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $message .= "Quantity: {$stockTransfer->quantity_requested} {$variant->packaging}\n";
        $message .= "Total Units: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
        $message .= "Date: " . now()->format('M d, Y H:i') . "\n";
        $message .= "\nStock has been successfully transferred to counter.";

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
                $result = $this->smsService->sendSms($stockKeeper->phone_number, $message);
                
                if ($result['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
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
                $result = $this->smsService->sendSms($counter->phone_number, $message);
                
                if ($result['success']) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        return $sentCount > 0;
    }

    /**
     * Send notification when stock transfer is fully sold
     */
    public function sendTransferFullySoldNotification(StockTransfer $stockTransfer, $ownerId)
    {
        // Check if notifications are enabled
        $enableNotifications = SystemSetting::get('enable_auto_transfer_notification_' . $ownerId, true);
        
        if (!$enableNotifications) {
            return false;
        }

        // Ensure relationships are loaded
        if (!$stockTransfer->relationLoaded('productVariant')) {
            $stockTransfer->load('productVariant');
        }
        
        if (!$stockTransfer->productVariant || !$stockTransfer->productVariant->product) {
            \Log::error('Product variant or product not found for fully sold SMS', [
                'transfer_id' => $stockTransfer->id,
            ]);
            return false;
        }

        $product = $stockTransfer->productVariant->product;
        $variant = $stockTransfer->productVariant;

        // Build message for Counter Staff
        $counterMessage = "STOCK TRANSFER FULLY SOLD\n\n";
        $counterMessage .= "Transfer #: {$stockTransfer->transfer_number}\n";
        $counterMessage .= "Product: {$product->name}\n";
        $counterMessage .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $counterMessage .= "Quantity Sold: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
        $counterMessage .= "Date: " . now()->format('M d, Y H:i') . "\n";
        $counterMessage .= "\nAll stock from this transfer has been sold.";
        $counterMessage .= "\nPlease submit payment for accountant reconciliation.";

        // Build message for Accountant
        $accountantMessage = "STOCK TRANSFER READY FOR RECONCILIATION\n\n";
        $accountantMessage .= "Transfer #: {$stockTransfer->transfer_number}\n";
        $accountantMessage .= "Product: {$product->name}\n";
        $accountantMessage .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $accountantMessage .= "Quantity Sold: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
        $accountantMessage .= "Date: " . now()->format('M d, Y H:i') . "\n";
        $accountantMessage .= "\nAll stock from this transfer has been sold.";
        $accountantMessage .= "\nReady for reconciliation verification.";

        // Build message for Stock Keeper
        $stockKeeperMessage = "STOCK TRANSFER FULLY SOLD\n\n";
        $stockKeeperMessage .= "Transfer #: {$stockTransfer->transfer_number}\n";
        $stockKeeperMessage .= "Product: {$product->name}\n";
        $stockKeeperMessage .= "Variant: {$variant->measurement} - {$variant->packaging}\n";
        $stockKeeperMessage .= "Quantity Sold: " . number_format($stockTransfer->total_units) . " bottle(s)\n";
        $stockKeeperMessage .= "Date: " . now()->format('M d, Y H:i') . "\n";
        $stockKeeperMessage .= "\nAll stock from this transfer has been sold.";
        $stockKeeperMessage .= "\nCounter will submit payment for reconciliation.";

        $sentCount = 0;
        $failedCount = 0;

        // Send SMS to Counter Staff
        $counterStaff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->where(function($q) {
                    $q->where('name', 'Counter')
                      ->orWhere('name', 'Bar Counter');
                });
            })
            ->get();

        foreach ($counterStaff as $counter) {
            if ($counter->phone_number) {
                $result = $this->smsService->sendSms($counter->phone_number, $counterMessage);
                
                if ($result['success']) {
                    $sentCount++;
                    \Log::info('Transfer fully sold SMS sent to counter staff', [
                        'counter_id' => $counter->id,
                        'transfer_id' => $stockTransfer->id,
                    ]);
                } else {
                    $failedCount++;
                }
            }
        }

        // Send SMS to Accountants
        $accountants = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($query) {
                $query->where('name', 'Accountant');
            })
            ->get();

        foreach ($accountants as $accountant) {
            if ($accountant->phone_number) {
                $result = $this->smsService->sendSms($accountant->phone_number, $accountantMessage);
                
                if ($result['success']) {
                    $sentCount++;
                    \Log::info('Transfer fully sold SMS sent to accountant', [
                        'accountant_id' => $accountant->id,
                        'transfer_id' => $stockTransfer->id,
                    ]);
                } else {
                    $failedCount++;
                }
            }
        }

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
                    \Log::info('Transfer fully sold SMS sent to stock keeper', [
                        'stock_keeper_id' => $stockKeeper->id,
                        'transfer_id' => $stockTransfer->id,
                    ]);
                } else {
                    $failedCount++;
                }
            }
        }

        \Log::info('Transfer fully sold SMS notifications completed', [
            'transfer_id' => $stockTransfer->id,
            'sent' => $sentCount,
            'failed' => $failedCount
        ]);

        return $sentCount > 0;
    }
}

