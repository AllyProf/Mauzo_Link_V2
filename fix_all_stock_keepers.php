<?php
/**
 * Fix All Stock Keepers - Enable Bar for all their owners
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\BusinessType;
use App\Models\UserBusinessType;
use App\Models\Role;

echo "========================================\n";
echo "Fix All Stock Keepers\n";
echo "========================================\n\n";

$barType = BusinessType::where('slug', 'bar')->first();
$stockKeeperRoles = Role::where('name', 'like', '%Stock Keeper%')
    ->orWhere('slug', 'like', '%stock-keeper%')
    ->get();

echo "Found {$stockKeeperRoles->count()} Stock Keeper role(s)\n\n";

foreach ($stockKeeperRoles as $role) {
    $owner = $role->owner;
    echo "Processing owner: {$owner->email}\n";
    
    // Check if Bar is enabled
    $hasBar = UserBusinessType::where('user_id', $owner->id)
        ->where('business_type_id', $barType->id)
        ->where('is_enabled', true)
        ->exists();
    
    if (!$hasBar) {
        $existing = UserBusinessType::where('user_id', $owner->id)
            ->where('business_type_id', $barType->id)
            ->first();
        
        if ($existing) {
            $existing->is_enabled = true;
            $existing->save();
            echo "  ✓ Enabled Bar\n";
        } else {
            $hasAny = UserBusinessType::where('user_id', $owner->id)->exists();
            UserBusinessType::create([
                'user_id' => $owner->id,
                'business_type_id' => $barType->id,
                'is_primary' => !$hasAny,
                'is_enabled' => true,
            ]);
            echo "  ✓ Created and enabled Bar\n";
        }
    } else {
        echo "  ✓ Bar already enabled\n";
    }
    
    // List Stock Keepers for this owner
    $stockKeepers = Staff::where('user_id', $owner->id)
        ->where('role_id', $role->id)
        ->get();
    
    if ($stockKeepers->count() > 0) {
        echo "  Stock Keepers:\n";
        foreach ($stockKeepers as $sk) {
            echo "    - {$sk->full_name} ({$sk->email})\n";
        }
    }
    echo "\n";
}

echo "========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "All Stock Keeper owners now have Bar enabled.\n";
echo "Stock Keepers should see Bar Management menu after logout/login.\n";

