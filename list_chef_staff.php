<?php
/**
 * List All Chef Staff and Their Credentials
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "All Chef Staff Members and Login Credentials\n";
echo "========================================\n\n";

// Get all owners
$owners = User::where('role', '!=', 'admin')->get();

if ($owners->count() === 0) {
    echo "No owners found.\n";
    exit(0);
}

foreach ($owners as $owner) {
    echo "Owner: {$owner->name} ({$owner->email})\n";
    echo str_repeat("-", 80) . "\n";
    
    // Find Chef role
    $chefRole = Role::where('user_id', $owner->id)
        ->where(function($q) {
            $q->where('name', 'like', '%Chef%')
              ->orWhere('name', 'like', '%chef%')
              ->orWhere('slug', 'like', '%chef%');
        })
        ->first();
    
    if (!$chefRole) {
        echo "  ⚠️  No Chef role found for this owner.\n\n";
        continue;
    }
    
    $staffMembers = Staff::where('user_id', $owner->id)
        ->where('role_id', $chefRole->id)
        ->with('role')
        ->get();
    
    if ($staffMembers->count() === 0) {
        echo "  No chef staff members found.\n\n";
        continue;
    }
    
    foreach ($staffMembers as $staff) {
        // Calculate password
        $nameParts = explode(' ', trim($staff->full_name));
        $lastName = end($nameParts);
        $calculatedPassword = strtoupper($lastName);
        
        // Test password
        $passwordMatch = Hash::check($calculatedPassword, $staff->password);
        
        $roleName = $staff->role ? $staff->role->name : 'No Role';
        $status = $staff->is_active ? 'Active' : 'Inactive';
        
        echo "  Staff ID: {$staff->staff_id}\n";
        echo "  Name: {$staff->full_name}\n";
        echo "  Email: {$staff->email}\n";
        echo "  Phone: {$staff->phone_number}\n";
        echo "  Role: {$roleName}\n";
        echo "  Status: {$status}\n";
        echo "  Password: {$calculatedPassword} " . ($passwordMatch ? "✓" : "✗ MISMATCH") . "\n";
        
        if (!$passwordMatch) {
            echo "  ⚠️  Password mismatch detected! Resetting...\n";
            $staff->password = Hash::make($calculatedPassword);
            $staff->save();
            echo "  ✓ Password reset to: {$calculatedPassword}\n";
        }
        
        // Check bar_orders permissions
        if ($staff->role) {
            $hasBarOrdersView = $staff->role->hasPermission('bar_orders', 'view');
            $hasBarOrdersEdit = $staff->role->hasPermission('bar_orders', 'edit');
            echo "  Bar Orders View: " . ($hasBarOrdersView ? "✓ YES" : "✗ NO") . "\n";
            echo "  Bar Orders Edit: " . ($hasBarOrdersEdit ? "✓ YES" : "✗ NO") . "\n";
        }
        
        echo "\n  LOGIN CREDENTIALS:\n";
        echo "  --------------------\n";
        echo "  Email: {$staff->email}\n";
        echo "  Password: {$calculatedPassword}\n";
        echo "  Dashboard: /bar/chef/dashboard\n";
        echo "\n" . str_repeat("-", 80) . "\n\n";
    }
}

echo "========================================\n";
echo "Done! Use the credentials above to login as Chef.\n";
echo "========================================\n";





