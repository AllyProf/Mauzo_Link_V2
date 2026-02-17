<?php
/**
 * List All Staff and Their Credentials
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "========================================\n";
echo "All Staff Members and Login Credentials\n";
echo "========================================\n\n";

// Get all owners
$owners = User::all();

foreach ($owners as $owner) {
    if ($owner->role === 'admin') continue;
    
    echo "Owner: {$owner->name} ({$owner->email})\n";
    echo str_repeat("-", 80) . "\n";
    
    $staffMembers = Staff::where('user_id', $owner->id)
        ->with('role')
        ->get();
    
    if ($staffMembers->count() === 0) {
        echo "  No staff members found.\n\n";
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
        
        // Check marketing permissions
        if ($staff->role) {
            $hasMarketing = $staff->role->hasPermission('marketing', 'view');
            echo "  Marketing Access: " . ($hasMarketing ? "✓ YES" : "✗ NO") . "\n";
        }
        
        echo "\n  LOGIN CREDENTIALS:\n";
        echo "  --------------------\n";
        echo "  Email: {$staff->email}\n";
        echo "  Password: {$calculatedPassword}\n";
        echo "\n" . str_repeat("-", 80) . "\n\n";
    }
}

echo "========================================\n";
echo "Done! Use the credentials above to login.\n";
echo "========================================\n";







