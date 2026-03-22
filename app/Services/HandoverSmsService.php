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
        $total = number_format($handover->amount, 0);
        
        $breakdown = $handover->payment_breakdown ?? [];
        $cash = $breakdown['cash'] ?? 0;
        $digital = 0;
        foreach($breakdown as $key => $val) {
            if ($key !== 'cash') $digital += (float)$val;
        }

        $cashFormatted = number_format($cash, 0);
        $digitalFormatted = number_format($digital, 0);

        $message = "FINANCIAL HANDOVER SUBMITTED\n\n";
        $message .= "From: " . ($sender->full_name ?? 'Counter') . "\n";
        $message .= "Date: {$date}\n";
        $message .= "Total: TSh {$total}\n";
        $message .= "Cash: TSh {$cashFormatted}\n";
        $message .= "Digital: TSh {$digitalFormatted}\n";
        $message .= "\nPlease login to verify this handover.";

        $accountants = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->whereHas('role', function($q) {
                $q->where('slug', 'accountant');
            })
            ->get();

        $sentCount = 0;
        foreach ($accountants as $accountant) {
            if ($accountant->phone_number) {
                $result = $this->smsService->sendSms($accountant->phone_number, $message);
                if ($result['success']) {
                    $sentCount++;
                }
            }
        }

        return $sentCount > 0;
    }
}
