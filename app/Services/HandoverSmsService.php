<?php

namespace App\Services;

use App\Models\FinancialHandover;
use App\Models\Staff;
use App\Models\SystemSetting;

class HandoverSmsService
{
    protected $smsService;

    public function __construct()
    {
        $this->smsService = new SmsService();
    }

    /**
     * Send handover submission notification to accountants
     */
    public function sendHandoverSubmissionSms(FinancialHandover $handover, $ownerId)
    {
        // Check if notifications are enabled
        $settingKey = 'enable_handover_notifications_' . $ownerId;
        $enableNotifications = SystemSetting::get($settingKey, true);
        if (!$enableNotifications) return false;

        $sender = $handover->staff; // The staff (Counter/Chef/Waiter) who submitted
        $date = \Carbon\Carbon::parse($handover->handover_date)->format('M d, Y');
        $total = number_format((float)$handover->amount, 0);
        
        $breakdown = $handover->payment_breakdown ?? [];
        $cash = $breakdown['cash'] ?? 0;
        $digital = 0;
        foreach($breakdown as $key => $val) {
            if ($key !== 'cash') $digital += (float)$val;
        }

        $cashFormatted = number_format($cash, 0);
        $digitalFormatted = number_format($digital, 0);
        $circulationFormatted = number_format($handover->circulation_money ?? 0, 0);
        $profitFormatted = number_format($handover->profit_amount ?? 0, 0);

        $message = "HANDOVER SUBMITTED - MauzoLink\n\n";
        $message .= "By: " . ($sender->full_name ?? 'Counter') . "\n";
        $message .= "Date: {$date}\n";
        $message .= "Total Collected: TSh {$total}\n";
        $message .= "  Cash: TSh {$cashFormatted}\n";
        $message .= "  Digital: TSh {$digitalFormatted}\n";
        if ($circulationFormatted > 0) {
            $message .= "Circulation Float: TSh {$circulationFormatted}\n";
        }
        $message .= "Est. Profit: TSh {$profitFormatted}\n";
        $message .= "\nPlease login to review & verify.";

        // Notify both managers and accountants
        $recipients = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->whereIn('slug', ['accountant', 'manager']);
            })
            ->get();

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            if ($recipient->phone_number) {
                $result = $this->smsService->sendSms($recipient->phone_number, $message);
                if ($result['success']) {
                    $sentCount++;
                }
            }
        }

        return $sentCount > 0;
    }
}
