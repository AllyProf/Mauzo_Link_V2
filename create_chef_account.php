<?php
/**
 * Create Chef Account for All Owners
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
echo "Create Chef Account\n";
echo "========================================\n\n";

// Get all owners
$owners = User::where('role', '!=', 'admin')->get();

if ($owners->isEmpty()) {
    echo "❌ No owners found\n";
    exit(1);
}

$restaurantType = BusinessType::where('slug', 'restaurant')->first();
if (!$restaurantType) {
    echo "❌ Restaurant business type not found\n";
    exit(1);
}

foreach ($owners as $owner) {
    echo "Processing owner: {$owner->email}\n";
    
    // Ensure Restaurant is enabled for this owner
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
        } else {
            $hasAny = UserBusinessType::where('user_id', $owner->id)->exists();
            UserBusinessType::create([
                'user_id' => $owner->id,
                'business_type_id' => $restaurantType->id,
                'is_primary' => !$hasAny,
                'is_enabled' => true,
            ]);
            echo "  ✓ Created and enabled Restaurant business type\n";
        }
    }
    
    // Create or get Chef role
    $chefRole = Role::firstOrCreate(
        [
            'user_id' => $owner->id,
            'slug' => 'chef',
        ],
        [
            'name' => 'Chef',
            'description' => 'Manage kitchen operations and food preparation',
            'is_active' => true,
        ]
    );
    
    if ($chefRole->wasRecentlyCreated) {
        echo "  ✓ Created Chef role\n";
    } else {
        echo "  ✓ Found Chef role\n";
    }
    
    // Attach Chef permissions
    $chefPermissions = Permission::where(function($q) {
        $q->where(function($q2) {
            $q2->where('module', 'bar_orders')
               ->whereIn('action', ['view', 'edit']);
        })->orWhere(function($q2) {
            $q2->where('module', 'products')
               ->whereIn('action', ['view', 'edit']);
        })->orWhere(function($q2) {
            $q2->where('module', 'inventory')
               ->whereIn('action', ['view', 'edit']);
        })->orWhere(function($q2) {
            $q2->where('module', 'stock_receipt')
               ->where('action', 'view');
        });
    })->get();
    
    if ($chefPermissions->count() > 0) {
        $chefRole->permissions()->syncWithoutDetaching($chefPermissions->pluck('id'));
        echo "  ✓ Attached {$chefPermissions->count()} permissions to Chef role\n";
    }
    
    // Create Chef staff member
    $chefEmail = 'chef@mauzo.com';
    
    // Check if email exists for another owner
    $emailExists = Staff::where('email', $chefEmail)
        ->where('user_id', '!=', $owner->id)
        ->exists();
    
    if ($emailExists) {
        // Use owner-specific email
        $ownerDomain = str_replace(['@', '.'], '', $owner->email);
        $chefEmail = "chef@{$ownerDomain}";
    }
    
    // Check if Chef staff already exists
    $chefStaff = Staff::where('email', $chefEmail)
        ->where('user_id', $owner->id)
        ->first();
    
    if (!$chefStaff) {
        // Generate unique staff_id
        $staffId = Staff::generateStaffId($owner->id);
        $attempts = 0;
        while (Staff::where('staff_id', $staffId)->exists() && $attempts < 10) {
            // If duplicate, try next number
            $lastNumber = (int) substr($staffId, -4);
            $newNumber = $lastNumber + 1;
            $year = date('Y');
            $month = date('m');
            $staffId = 'STF' . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            $attempts++;
        }
        
        $chefStaff = Staff::create([
            'email' => $chefEmail,
            'user_id' => $owner->id,
            'staff_id' => $staffId,
            'full_name' => 'Chef',
            'gender' => 'other',
            'phone_number' => '+255710000006',
            'password' => Hash::make('password'),
            'role_id' => $chefRole->id,
            'salary_paid' => 0,
            'is_active' => true,
        ]);
        echo "  ✓ Created Chef staff: {$chefEmail} / password\n";
    } else {
        // Update existing staff
        $chefStaff->password = Hash::make('password');
        $chefStaff->role_id = $chefRole->id;
        $chefStaff->is_active = true;
        $chefStaff->save();
        echo "  ✓ Updated Chef staff: {$chefEmail} / password\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Complete!\n";
echo "========================================\n";
echo "Chef accounts created for all owners.\n";
echo "Credentials: chef@mauzo.com / password\n";
echo "(or chef@{owner-domain} if email conflict)\n\n";
echo "Chef Permissions:\n";
echo "  - View and Edit Bar Orders\n";
echo "  - View and Edit Products\n";
echo "  - View and Edit Inventory\n";
echo "  - View Stock Receipts\n";

