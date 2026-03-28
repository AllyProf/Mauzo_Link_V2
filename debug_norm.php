<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$h = \App\Models\FinancialHandover::find(2);
$normalizeKey = function(string $channel): string {
    $c = strtolower(trim(str_replace([' ', '-'], '_', $channel)));
    if (str_contains($c, 'mpesa') || str_contains($c, 'm_pesa')) return 'mpesa';
    if (str_contains($c, 'tigo')) return 'tigo_pesa';
    if (str_contains($c, 'airtel')) return 'airtel_money';
    if (str_contains($c, 'halo')) return 'halopesa';
    if (str_contains($c, 'mixx')) return 'mixx';
    if (str_contains($c, 'crdb')) return 'crdb';
    if (str_contains($c, 'nmb')) return 'nmb';
    if (str_contains($c, 'nbc')) return 'nbc';
    if (str_contains($c, 'kcb')) return 'kcb';
    if (str_contains($c, 'stanbic')) return 'stanbic';
    if (str_contains($c, 'equity')) return 'equity';
    if (str_contains($c, 'dtb') || str_contains($c, 'diamond')) return 'dtb';
    if (str_contains($c, 'exim')) return 'exim';
    if (str_contains($c, 'azania')) return 'azania';
    if (str_contains($c, 'visa')) return 'visa';
    if (str_contains($c, 'mastercard')) return 'mastercard';
    if (str_contains($c, 'cash')) return 'cash';
    if (str_contains($c, 'bank') || str_contains($c, 'transfer')) return 'bank_transfer';
    return $c;
};

$submittedPlatformBreakdown = [];
foreach ($h->payment_breakdown as $channel => $amt) {
    $key = $normalizeKey($channel);
    $submittedPlatformBreakdown[$key] = ($submittedPlatformBreakdown[$key] ?? 0) + (float)$amt;
}

echo "Normalized Breakdown: " . json_encode($submittedPlatformBreakdown) . "\n";
echo "Total: " . array_sum($submittedPlatformBreakdown) . "\n";
