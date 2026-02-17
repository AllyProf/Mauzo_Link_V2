<?php
/**
 * Check Stock Keeper Supplier Permissions and Menu
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\Role;
use App\Models\Permission;
use App\Services\MenuService;
use Illuminate\Support\Facades\DB;

$stockKeeper = Staff::where('email', 'stockkeeper@mauzo.com')->first();

if (!$stockKeeper) {
    echo "❌ Stock Keeper not found\n";
    exit(1);
}

echo "========================================\n";
echo "Check Stock Keeper Supplier Permissions\n";
echo "========================================\n\n";

$role = $stockKeeper->role;
$owner = $stockKeeper->owner;

echo "Stock Keeper: {$stockKeeper->full_name}\n";
echo "Email: {$stockKeeper->email}\n";
echo "Role: {$role->name} (ID: {$role->id})\n";
echo "Owner: {$owner->email}\n\n";

// Check supplier permissions in database
echo "Supplier Permissions in Database:\n";
$supplierPerms = DB::table('role_permissions')
    ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
    ->where('role_permissions.role_id', $role->id)
    ->where('permissions.module', 'suppliers')
    ->get();

if ($supplierPerms->count() > 0) {
    foreach ($supplierPerms as $perm) {
        echo "  ✓ {$perm->module}.{$perm->action} (Permission ID: {$perm->permission_id})\n";
    }
} else {
    echo "  ✗ No supplier permissions found\n";
}

// Check using role model
echo "\nUsing Role Model:\n";
$hasSuppliersView = $role->hasPermission('suppliers', 'view');
$hasSuppliersCreate = $role->hasPermission('suppliers', 'create');
$hasSuppliersEdit = $role->hasPermission('suppliers', 'edit');
$hasSuppliersDelete = $role->hasPermission('suppliers', 'delete');

echo "  suppliers.view: " . ($hasSuppliersView ? "✓ YES" : "✗ NO") . "\n";
echo "  suppliers.create: " . ($hasSuppliersCreate ? "✓ YES" : "✗ NO") . "\n";
echo "  suppliers.edit: " . ($hasSuppliersEdit ? "✓ YES" : "✗ NO") . "\n";
echo "  suppliers.delete: " . ($hasSuppliersDelete ? "✓ YES" : "✗ NO") . "\n";

// Check menu generation
echo "\nMenu Generation Test:\n";
$menuService = new MenuService();
$menus = $menuService->getStaffMenus($role, $owner);

foreach ($menus as $menu) {
    if ($menu->slug === 'bar-management') {
        echo "Bar Management found with " . (isset($menu->children) ? $menu->children->count() : 0) . " children:\n";
        if (isset($menu->children)) {
            foreach ($menu->children as $child) {
                $hasSuppliers = ($child->slug === 'bar-suppliers' || strpos($child->name, 'Supplier') !== false);
                echo "  - {$child->name} (slug: {$child->slug}, route: " . ($child->route ?? 'none') . ")";
                if ($hasSuppliers) {
                    echo " ← SUPPLIERS MENU";
                }
                echo "\n";
            }
        }
    }
}

// Check if Suppliers menu item exists
echo "\nChecking Suppliers Menu Item:\n";
$suppliersMenu = \App\Models\MenuItem::where('slug', 'bar-suppliers')->first();
if ($suppliersMenu) {
    echo "  ✓ Suppliers menu item exists (ID: {$suppliersMenu->id})\n";
    echo "  Route: {$suppliersMenu->route}\n";
    echo "  Parent ID: {$suppliersMenu->parent_id}\n";
    
    // Check if it's attached to Bar business type
    $attached = DB::table('business_type_menu_items')
        ->where('business_type_id', 1) // Bar business type ID
        ->where('menu_item_id', $suppliersMenu->id)
        ->where('is_enabled', true)
        ->exists();
    
    echo "  Attached to Bar: " . ($attached ? "✓ YES" : "✗ NO") . "\n";
} else {
    echo "  ✗ Suppliers menu item not found\n";
}

echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n";
if ($hasSuppliersView) {
    echo "✓ Stock Keeper HAS supplier permissions\n";
    echo "✓ Suppliers menu should be visible\n";
    echo "\nIf not visible, the staff member needs to:\n";
    echo "1. Logout completely\n";
    echo "2. Login again\n";
    echo "3. Clear browser cache (Ctrl+F5)\n";
} else {
    echo "✗ Stock Keeper does NOT have supplier permissions\n";
    echo "Please add them in business configuration edit page\n";
}

