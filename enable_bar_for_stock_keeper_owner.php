<?php
/**
 * Enable Bar Business Type for Stock Keeper's Owner
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\BusinessType;
use App\Models\UserBusinessType;

$staff = Staff::where('email', 'stockkeeper@mauzo.com')->first();
if (!$staff) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

$owner = $staff->owner;
$barType = BusinessType::where('slug', 'bar')->first();

echo "========================================\n";
echo "Enable Bar for Stock Keeper Owner\n";
echo "========================================\n\n";

echo "Owner: {$owner->email}\n";
echo "Bar Type: {$barType->name}\n\n";

// Check if already enabled
$existing = UserBusinessType::where('user_id', $owner->id)
    ->where('business_type_id', $barType->id)
    ->first();

if ($existing) {
    if ($existing->is_enabled) {
        echo "✓ Bar is already enabled for this owner\n";
    } else {
        $existing->is_enabled = true;
        $existing->save();
        echo "✓ Enabled Bar for this owner\n";
    }
} else {
    // Check if owner has any business types
    $hasAny = UserBusinessType::where('user_id', $owner->id)->exists();
    $isPrimary = !$hasAny; // Make it primary if owner has no business types
    
    UserBusinessType::create([
        'user_id' => $owner->id,
        'business_type_id' => $barType->id,
        'is_primary' => $isPrimary,
        'is_enabled' => true,
    ]);
    echo "✓ Created and enabled Bar for this owner (primary: " . ($isPrimary ? 'Yes' : 'No') . ")\n";
}

// Verify
$enabledTypes = $owner->enabledBusinessTypes()->get();
echo "\nOwner's Enabled Business Types:\n";
foreach ($enabledTypes as $type) {
    $isPrimary = UserBusinessType::where('user_id', $owner->id)
        ->where('business_type_id', $type->id)
        ->value('is_primary');
    echo "  - {$type->name}" . ($isPrimary ? " (PRIMARY)" : "") . "\n";
}

echo "\n========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "Stock Keeper should now see Bar Management menu.\n";
echo "Please logout and login again as Stock Keeper.\n";

