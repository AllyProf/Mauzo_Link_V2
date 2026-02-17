<?php
/**
 * Create Waiter Account for All Owners
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Permission;
use App\Models\BusinessType;
use App\Models\UserBusinessType;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "Create Waiter Account\n";
echo "========================================\n\n";

// Get all owners
$owners = User::where('role', '!=', 'admin')->get();

if ($owners->isEmpty()) {
    echo "❌ No owners found\n";
    exit(1);
}

$barType = BusinessType::where('slug', 'bar')->first();
$restaurantType = BusinessType::where('slug', 'restaurant')->first();

if (!$barType && !$restaurantType) {
    echo "⚠️  No Bar or Restaurant business types found\n";
}

foreach ($owners as $owner) {
    echo "Processing owner: {$owner->email}\n";
    
    // Ensure Bar or Restaurant is enabled for this owner
    $hasBar = false;
    $hasRestaurant = false;
    
    if ($barType) {
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
                echo "  ✓ Enabled Bar business type\n";
                $hasBar = true;
            } else {
                $hasAny = UserBusinessType::where('user_id', $owner->id)->exists();
                UserBusinessType::create([
                    'user_id' => $owner->id,
                    'business_type_id' => $barType->id,
                    'is_primary' => !$hasAny,
                    'is_enabled' => true,
                ]);
                echo "  ✓ Created and enabled Bar business type\n";
                $hasBar = true;
            }
        }
    }
    
    if ($restaurantType) {
        $hasRestaurant = UserBusinessType::where('user_id', $owner->id)
            ->where('business_type_id', $restaurantType->id)
            ->where('is_enabled', true)
            ->exists();
        
        if (!$hasRestaurant) {
            $existing = UserBusinessType::where('user_id', $owner->id)
                ->where('business_type_id', $restaurantType->id)
                ->first();
            
            if ($existing) {
                $existing->is_enabled = true;
                $existing->save();
                echo "  ✓ Enabled Restaurant business type\n";
                $hasRestaurant = true;
            } else {
                $hasAny = UserBusinessType::where('user_id', $owner->id)->exists();
                UserBusinessType::create([
                    'user_id' => $owner->id,
                    'business_type_id' => $restaurantType->id,
                    'is_primary' => !$hasAny && !$hasBar,
                    'is_enabled' => true,
                ]);
                echo "  ✓ Created and enabled Restaurant business type\n";
                $hasRestaurant = true;
            }
        }
    }
    
    // Create or get Waiter role
    $waiterRole = Role::firstOrCreate(
        [
            'user_id' => $owner->id,
            'slug' => 'waiter',
        ],
        [
            'name' => 'Waiter',
            'description' => 'Take orders and serve customers',
            'is_active' => true,
        ]
    );
    
    if ($waiterRole->wasRecentlyCreated) {
        echo "  ✓ Created Waiter role\n";
    } else {
        echo "  ✓ Found Waiter role\n";
    }
    
    // Attach Waiter permissions
    $waiterPermissions = Permission::where(function($q) {
        $q->where(function($q2) {
            $q2->where('module', 'bar_orders')
               ->whereIn('action', ['view', 'create']);
        })->orWhere(function($q2) {
            $q2->where('module', 'bar_tables')
               ->where('action', 'view');
        })->orWhere(function($q2) {
            $q2->where('module', 'products')
               ->where('action', 'view');
        })->orWhere(function($q2) {
            $q2->where('module', 'customers')
               ->whereIn('action', ['view', 'create']);
        });
    })->get();
    
    if ($waiterPermissions->count() > 0) {
        $waiterRole->permissions()->syncWithoutDetaching($waiterPermissions->pluck('id'));
        echo "  ✓ Attached {$waiterPermissions->count()} permissions to Waiter role\n";
    }
    
    // Create Waiter staff member
    $waiterEmail = 'waiter@mauzo.com';
    
    // Check if email exists for another owner
    $emailExists = Staff::where('email', $waiterEmail)
        ->where('user_id', '!=', $owner->id)
        ->exists();
    
    if ($emailExists) {
        // Use owner-specific email
        $ownerDomain = str_replace(['@', '.'], '', $owner->email);
        $waiterEmail = "waiter@{$ownerDomain}";
    }
    
    // Check if Waiter staff already exists
    $waiterStaff = Staff::where('email', $waiterEmail)
        ->where('user_id', $owner->id)
        ->first();
    
    if (!$waiterStaff) {
        // Generate unique staff_id
        $staffId = Staff::generateStaffId($owner->id);
        $attempts = 0;
        while (Staff::where('staff_id', $staffId)->exists() && $attempts < 10) {
            $lastNumber = (int) substr($staffId, -4);
            $newNumber = $lastNumber + 1;
            $year = date('Y');
            $month = date('m');
            $staffId = 'STF' . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        $waiterStaff = Staff::create([
            'email' => $waiterEmail,
            'user_id' => $owner->id,
            'staff_id' => $staffId,
            'full_name' => 'Waiter',
            'gender' => 'other',
            'phone_number' => '+255710000007',
            'password' => Hash::make('password'),
            'role_id' => $waiterRole->id,
            'salary_paid' => 0,
            'is_active' => true,
        ]);
        echo "  ✓ Created Waiter staff: {$waiterEmail} / password\n";
    } else {
        // Update existing staff
        $waiterStaff->password = Hash::make('password');
        $waiterStaff->role_id = $waiterRole->id;
        $waiterStaff->is_active = true;
        $waiterStaff->save();
        echo "  ✓ Updated Waiter staff: {$waiterEmail} / password\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "Waiter accounts created for all owners.\n";
echo "Credentials: waiter@mauzo.com / password\n";
echo "(or waiter@{owner-domain} if email conflict)\n\n";
echo "Waiter Permissions:\n";
echo "  - View and Create Bar Orders\n";
echo "  - View Bar Tables\n";
echo "  - View Products\n";
echo "  - View and Create Customers\n";

