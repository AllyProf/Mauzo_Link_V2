<?php

namespace App\Services;

use App\Models\User;
use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\Role;

class MenuService
{
    /**
     * Common menu slugs that appear at the top
     */
    protected const COMMON_SLUGS = [
        'dashboard', 'sales', 'products', 'customers', 'staff', 
        'hr', 'reports', 'marketing', 'settings', 'accountant'
    ];

    /**
     * Get menu items for staff member based on their role permissions
     */
    public function getStaffMenus($staffRole, $owner)
    {
        if (!$staffRole) {
            return collect();
        }

        // Ensure permissions are loaded
        if (!$staffRole->relationLoaded('permissions')) {
            $staffRole->load('permissions');
        }

        // Get owner's business types
        $businessTypes = $owner->enabledBusinessTypes()->orderBy('user_business_types.is_primary', 'desc')->get();

        $menus = collect();
        $commonMenuIds = collect();

        // First, get ALL common menu IDs (before filtering) to exclude from business-specific menus
        $allCommonMenuIds = MenuItem::whereIn('slug', self::COMMON_SLUGS)
            ->whereNull('parent_id')
            ->pluck('id');

        // Get common menus filtered by staff role permissions
        $commonMenus = $this->getCommonMenusForStaff($staffRole, $owner);
        foreach ($commonMenus as $commonMenu) {
            $menus->push($commonMenu);
            $commonMenuIds->push($commonMenu->id);
        }

        // Role detection for exceptions
        $roleName = strtolower($staffRole->name ?? '');
        $roleSlug = strtolower($staffRole->slug ?? '');
        $isChef = in_array($roleName, ['chef', 'head chef', 'cook']) || $roleSlug === 'chef';
        $isCounter = in_array($roleName, ['counter', 'bar counter', 'waiter', 'counter supervisor']) || in_array($roleSlug, ['counter', 'waiter']);
        
        // Get business-specific menus filtered by staff role permissions
        $businessSpecificMenusByType = [];
        
        if ($businessTypes->isNotEmpty()) {
            $businessTypeNames = $businessTypes->pluck('name')->toArray();
            $businessTypeSlugs = $businessTypes->pluck('slug')->toArray();
            
            // Initialize array for all business types
            foreach ($businessTypes as $businessType) {
                // Skip business types based on role exceptions
                if ($isChef && $businessType->slug === 'bar') continue;
                if ($isCounter && $businessType->slug === 'restaurant') continue;

                $businessSpecificMenusByType[$businessType->id] = [
                    'business_type' => $businessType,
                    'menus' => collect()
                ];
            }
            
            foreach ($businessTypes as $businessType) {
                // Skip if not in our allowed types for this role
                if (!isset($businessSpecificMenusByType[$businessType->id])) continue;
                
                $typeMenus = $businessType->enabledMenuItems()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->whereNotIn('menu_items.id', $allCommonMenuIds->toArray())
                    ->orderBy('business_type_menu_items.sort_order')
                    ->get()
                    ->filter(function($menu) use ($businessTypeNames, $businessTypeSlugs, $isCounter) {
                        // Filter out menu items with business type names or slugs
                        if (in_array($menu->name, $businessTypeNames) || in_array($menu->slug ?? '', $businessTypeSlugs)) {
                            return false;
                        }

                        // For Counter role, exclude Ingredient Management menus
                        if ($isCounter && in_array($menu->slug, ['bar-ingredient-management', 'ingredient-management'])) {
                            return false;
                        }

                        return true;
                    });

                foreach ($typeMenus as $menu) {
                    // Fetch children for this menu
                    $menu->children = $this->getMenuChildrenForStaff($menu, $businessType, $staffRole);
                    
                    if (($menu->children && $menu->children->count() > 0) || ($menu->route && $this->canAccessMenuForStaff($staffRole, $menu))) {
                        $menu->business_type_name = $businessType->name;
                        $menu->business_type_icon = $businessType->icon ?? 'fa-building';
                        $menu->business_type_id = $businessType->id;
                        $businessSpecificMenusByType[$businessType->id]['menus']->push($menu);
                    }
                }
            }
            
            // Add menus and placeholders
            foreach ($businessSpecificMenusByType as $typeData) {
                $businessType = $typeData['business_type'];
                $typeMenus = $typeData['menus'];
                
                if ($typeMenus->isEmpty()) {
                    // Create a placeholder menu item to show the business type separator
                    $placeholderMenu = (object)[
                        'id' => 'placeholder_' . $businessType->id,
                        'name' => $businessType->name,
                        'slug' => $businessType->slug,
                        'icon' => $businessType->icon ?? 'fa-building',
                        'route' => null,
                        'parent_id' => null,
                        'children' => collect(),
                        'business_type_name' => $businessType->name,
                        'business_type_icon' => $businessType->icon ?? 'fa-building',
                        'business_type_id' => $businessType->id,
                        'sort_order' => 999,
                        'is_placeholder' => true,
                    ];
                    $menus->push($placeholderMenu);
                } else {
                    foreach ($typeMenus as $menu) {
                        $menus->push($menu);
                    }
                }
            }
        }

        // Sort menus: common menus first (by sort_order), then business-specific menus (grouped by business type)
        return $menus->sortBy(function($menu) {
            // Common menus get priority based on sort_order
            if (in_array($menu->slug, self::COMMON_SLUGS)) {
                return $menu->sort_order ?? 999;
            }
            // Business-specific menus come after, grouped by business_type_id
            return 1000 + ($menu->business_type_id ?? 0) * 100 + ($menu->sort_order ?? 0);
        })->values();
    }

    /**
     * Get common menus for staff
     */
    private function getCommonMenusForStaff($staffRole, $owner)
    {
        // Role detection
        $roleName = strtolower($staffRole->name ?? '');
        $roleSlug = strtolower($staffRole->slug ?? '');
        $isCounter = in_array($roleName, ['counter', 'bar counter', 'waiter', 'counter supervisor']) || in_array($roleSlug, ['counter', 'waiter']);
        $isStockKeeper = in_array($roleName, ['stock keeper', 'stockkeeper']) || in_array($roleSlug, ['stock-keeper', 'stockkeeper']);
        $isAccountant = in_array($roleName, ['accountant', 'finance manager', 'finance']) || in_array($roleSlug, ['accountant']);
        $isHR = in_array($roleName, ['hr', 'hr manager', 'human resources']) || in_array($roleSlug, ['hr-manager', 'hr']);
        
        $menus = MenuItem::whereIn('slug', self::COMMON_SLUGS)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function($menu) use ($staffRole) {
                $menu->children = $this->getCommonMenuChildrenForStaff($menu, $staffRole);
                return $menu;
            })
            ->filter(function($menu) use ($staffRole, $isCounter, $isStockKeeper, $isAccountant, $isHR) {
                // Dashboard is always shown
                if ($menu->slug === 'dashboard') {
                    return true;
                }
                
                // For Counter and Stock Keeper roles, hide the common 'Products' menu (they use specific products menus)
                // This must be checked BEFORE the children check
                if (($isCounter || $isStockKeeper) && $menu->slug === 'products') {
                    return false;
                }
                
                // If menu has children, only show if at least one child is accessible
                if ($menu->children && $menu->children->count() > 0) {
                    return true; // Show parent if it has accessible children (children are already filtered)
                }
                
                // If menu has no children, check if staff role has permission for the menu itself
                return $this->canAccessMenuForStaff($staffRole, $menu);
            })
            ->values();
            
        return $menus;
    }

    /**
     * Get common menu children for staff
     */
    private function getCommonMenuChildrenForStaff($parentMenu, $staffRole)
    {
        $children = MenuItem::where('parent_id', $parentMenu->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(function($child) use ($staffRole, $parentMenu) {
                return $this->canAccessMenuForStaff($staffRole, $child);
            })
            ->values();
            
        return $children;
    }

    /**
     * Get menu children for staff
     */
    private function getMenuChildrenForStaff($parentMenu, BusinessType $businessType, $staffRole)
    {
        $children = $businessType->enabledMenuItems()
            ->where('parent_id', $parentMenu->id)
            ->where('is_active', true)
            ->orderBy('business_type_menu_items.sort_order')
            ->get();

        // Filter by permissions - but allow children without routes if staff has related permissions
        return $children->filter(function($child) use ($staffRole) {
            // If child has a route, check permission
            if ($child->route) {
                return $this->canAccessMenuForStaff($staffRole, $child);
            }
            
            // If child has no route, check if staff has any related permissions
            // For business-specific menus, we'll show them if staff has inventory, products, sales, stock_receipt, stock_transfer, or suppliers permissions
            // This allows the menu structure to be visible even if routes aren't implemented yet
            $hasInventoryPermission = $staffRole->hasPermission('inventory', 'view');
            $hasProductsPermission = $staffRole->hasPermission('products', 'view');
            $hasSalesPermission = $staffRole->hasPermission('sales', 'view');
            $hasStockReceiptPermission = $staffRole->hasPermission('stock_receipt', 'view');
            $hasStockTransferPermission = $staffRole->hasPermission('stock_transfer', 'view');
            $hasSuppliersPermission = $staffRole->hasPermission('suppliers', 'view');
            
            // Show child if staff has any of these basic permissions
            return $hasInventoryPermission || $hasProductsPermission || $hasSalesPermission || 
                   $hasStockReceiptPermission || $hasStockTransferPermission || $hasSuppliersPermission;
        })->values();
    }

    /**
     * Check if staff role can access menu item
     */
    private function canAccessMenuForStaff($staffRole, MenuItem $menu)
    {
        // Dashboard is always accessible
        if ($menu->slug === 'dashboard' || $menu->route === 'dashboard') {
            return true;
        }
        
        // If menu has no route, it's just a parent - check if it has accessible children
        if (!$menu->route) {
            return false; // Parent visibility is handled by parent logic checking children
        }

        $roleName = strtolower($staffRole->name ?? '');
        $roleSlug = strtolower($staffRole->slug ?? '');
        
        $isCounter = in_array($roleName, ['counter', 'bar counter', 'waiter', 'counter supervisor']) || 
                     in_array($roleSlug, ['counter', 'waiter']);
        $isStockKeeper = in_array($roleName, ['stock keeper', 'stockkeeper']) || 
                         in_array($roleSlug, ['stock-keeper', 'stockkeeper']);
        $isChef = in_array($roleName, ['chef', 'head chef', 'cook']) || 
                  in_array($roleSlug, ['chef']);

        // Role-based route overrides for core functionality
        $overrides = [
            'counter' => [
                'bar.counter.dashboard', 'bar.counter.waiter-orders', 'bar.counter.reconciliation',
                'bar.counter.counter-stock', 'bar.counter.warehouse-stock', 'bar.counter.analytics',
                'bar.counter.customer-orders', 'bar.counter.verify-reconciliation', 'bar.counter.mark-paid',
                'bar.counter.mark-all-paid', 'bar.counter.update-order-status',
                'accountant.reconciliations', // Link to the new unified view
                'bar.stock-transfers.available', 'bar.stock-transfers.index', 'bar.stock-transfers.create',
                'bar.stock-transfers.history', 'bar.counter.stock-transfer-requests', 'bar.counter.request-stock-transfer',
                'bar.orders.index', 'bar.orders.drinks', 'bar.orders.food', 'bar.orders.juice', 'bar.orders.create',
                'bar.tables.index', 'bar.products.index', 'bar.products.create', 'bar.payments.index',
                'customers.index', 'customers.groups', 
                'bar.waiter.dashboard', 'bar.waiter.create-order', 'bar.waiter.order-history',
                'sales.pos', 'sales.orders', 'sales.transactions',
                'products.index', 'products.categories', 'products.inventory',
                'bar.beverage-inventory.index', 'bar.beverage-inventory.stock-levels', 'bar.beverage-inventory.warehouse-stock',
                'reports.index'
            ],
            'stock_keeper' => [
                'bar.beverage-inventory.warehouse-stock', 'bar.stock-receipts.index', 'bar.stock-receipts.create',
                'bar.stock-receipts.store', 'bar.stock-receipts.show',
                'bar.stock-transfers.index', 'bar.stock-transfers.create', 'bar.stock-transfers.store',
                'bar.products.index', 'bar.products.create', 'bar.suppliers.index',
                'products.inventory', 'products.index'
            ],
            'chef' => [
                'bar.chef.dashboard', 'bar.chef.kds', 'bar.chef.update-item-status', 'bar.chef.latest-orders',
                'bar.chef.food-items', 'bar.chef.ingredients', 'bar.chef.ingredient-receipts',
                'bar.chef.ingredient-batches', 'bar.chef.ingredient-stock-movements', 'bar.chef.reports',
                'accountant.reconciliations', // Link for chef to see their food reconciliations
                'bar.chef.reconciliation'
            ]
        ];

        if ($isCounter && in_array($menu->route, $overrides['counter'])) return true;
        if ($isStockKeeper && in_array($menu->route, $overrides['stock_keeper'])) return true;
        if ($isChef && in_array($menu->route, $overrides['chef'])) return true;

        // Map routes to permissions
        $routePermissions = $this->getRoutePermissions();

        if (isset($routePermissions[$menu->route])) {
            $permission = $routePermissions[$menu->route];
            return $staffRole->hasPermission($permission['module'], $permission['action']);
        }

        return false;
    }
    /**
     * Get menu items for user based on business types and permissions
     */
    public function getUserMenus(User $user)
    {
        $menus = collect();
        $commonMenuIds = collect();

        // Get user's business types
        $businessTypes = $user->enabledBusinessTypes()->orderBy('user_business_types.is_primary', 'desc')->get();

        if ($businessTypes->isEmpty() || !$user->isConfigured()) {
            // Return default/common menus if no business types selected or not configured
            return $this->getCommonMenus($user);
        }

        // First, get common menus (always shown first)
        $commonMenus = $this->getCommonMenus($user);
        foreach ($commonMenus as $commonMenu) {
            $menus->push($commonMenu);
            $commonMenuIds->push($commonMenu->id);
        }

        // Then, get business-specific menus organized by business type
        // Group menus by business type to avoid duplicates
        $businessSpecificMenusByType = [];
        $allBusinessMenuIds = collect();
        $businessTypeNames = $businessTypes->pluck('name')->toArray(); // Get business type names to exclude
        $businessTypeSlugs = $businessTypes->pluck('slug')->toArray(); // Get business type slugs to exclude
        
        // Initialize array for all business types (even if they have no specific menus)
        foreach ($businessTypes as $businessType) {
            $businessSpecificMenusByType[$businessType->id] = [
                'business_type' => $businessType,
                'menus' => collect()
            ];
        }
        
        foreach ($businessTypes as $businessType) {
            $typeMenus = $businessType->enabledMenuItems()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->whereNotIn('menu_items.id', $commonMenuIds->toArray()) // Exclude common menus
                ->orderBy('business_type_menu_items.sort_order')
                ->get()
                ->filter(function($menu) use ($businessTypeNames, $businessTypeSlugs) {
                    // Filter out menu items with business type names or slugs
                    return !in_array($menu->name, $businessTypeNames) && !in_array($menu->slug ?? '', $businessTypeSlugs);
                });

            foreach ($typeMenus as $menu) {
                // Skip if this menu was already added from another business type
                if ($allBusinessMenuIds->contains($menu->id)) {
                    continue;
                }
                
                // Skip if menu name or slug matches a business type name or slug
                if (in_array($menu->name, $businessTypeNames) || in_array($menu->slug ?? '', $businessTypeSlugs)) {
                    continue;
                }
                
                 // Fetch children for this menu
                 $menu->children = $this->getMenuChildren($menu, $businessType, $user);
                 
                 // Also load children for child menus that don't have routes (nested menus)
                 if ($menu->children && $menu->children->count() > 0) {
                     foreach ($menu->children as $childMenu) {
                         // If child menu has no route, it might have its own children
                         if (!$childMenu->route) {
                             $childMenu->children = $this->getMenuChildren($childMenu, $businessType, $user);
                         }
                     }
                 }
                 
                $menu->business_type_name = $businessType->name; // Tag with business type
                $menu->business_type_icon = $businessType->icon ?? 'fa-building';
                $menu->business_type_id = $businessType->id;
                
                $businessSpecificMenusByType[$businessType->id]['menus']->push($menu);
                $allBusinessMenuIds->push($menu->id);
            }
        }

        // Add business-specific menus grouped by business type
        // Maintain business type order (primary first, then by sort_order)
        $businessTypeSlugs = $businessTypes->pluck('slug')->toArray();
        
        foreach ($businessSpecificMenusByType as $typeData) {
            $businessType = $typeData['business_type'];
            $typeMenus = $typeData['menus'];
            
            // Sort menus within each business type by sort_order
            $sortedMenus = $typeMenus->sortBy(function($menu) {
                return $menu->sort_order ?? 999;
            });
            
            // If this business type has no business-specific menus, create a placeholder separator menu
            if ($sortedMenus->isEmpty()) {
                // Create a placeholder menu item to show the business type separator
                $placeholderMenu = (object)[
                    'id' => 'placeholder_' . $businessType->id,
                    'name' => $businessType->name,
                    'slug' => $businessType->slug,
                    'icon' => $businessType->icon ?? 'fa-building',
                    'route' => null,
                    'parent_id' => null,
                    'children' => collect(),
                    'business_type_name' => $businessType->name,
                    'business_type_icon' => $businessType->icon ?? 'fa-building',
                    'business_type_id' => $businessType->id,
                    'sort_order' => 999,
                    'is_placeholder' => true, // Flag to identify placeholder menus
                ];
                $menus->push($placeholderMenu);
            } else {
                foreach ($sortedMenus as $menu) {
                    // Triple-check: skip if menu name or slug matches business type name or slug
                    if (in_array($menu->name, $businessTypeNames) || in_array($menu->slug ?? '', $businessTypeSlugs)) {
                        continue;
                    }
                    
                    // Only add if menu has children or is accessible
                    if (($menu->children && $menu->children->count() > 0) || $this->canAccessMenu($user, $menu)) {
                        $menus->push($menu);
                    }
                }
            }
        }
        
        // Re-group menus by business type to ensure proper ordering
        // This ensures all menus from one business type appear together
        $groupedByBusinessType = [];
        $ungroupedMenus = collect();
        
        foreach ($menus as $menu) {
            if (isset($menu->business_type_id)) {
                if (!isset($groupedByBusinessType[$menu->business_type_id])) {
                    $groupedByBusinessType[$menu->business_type_id] = collect();
                }
                $groupedByBusinessType[$menu->business_type_id]->push($menu);
            } else {
                $ungroupedMenus->push($menu);
            }
        }
        
        // Rebuild menus: common menus first, then business-specific grouped by type
        // Include ALL business types, even if they have no specific menus (placeholders)
        $menus = $ungroupedMenus;
        foreach ($businessTypes as $businessType) {
            if (isset($groupedByBusinessType[$businessType->id])) {
                $menus = $menus->merge($groupedByBusinessType[$businessType->id]);
            }
        }

        // Final filter: Remove any menu items that match business type names or slugs
        $menus = $menus->filter(function($menu) use ($businessTypeNames, $businessTypes) {
            $businessTypeSlugs = $businessTypes->pluck('slug')->toArray();
            return !in_array($menu->name, $businessTypeNames) && 
                   !in_array($menu->slug ?? '', $businessTypeSlugs);
        });

        // Separate common menus from business-specific menus
        $commonMenusList = $menus->filter(function($menu) use ($commonMenuIds) {
            return $commonMenuIds->contains($menu->id);
        })->sortBy('sort_order');

        $businessSpecificMenusList = $menus->filter(function($menu) use ($commonMenuIds) {
            return !$commonMenuIds->contains($menu->id);
        });

        // Group business-specific menus by business type to prevent interleaving
        $groupedByBusinessType = [];
        foreach ($businessSpecificMenusList as $menu) {
            if (isset($menu->business_type_id)) {
                if (!isset($groupedByBusinessType[$menu->business_type_id])) {
                    $groupedByBusinessType[$menu->business_type_id] = collect();
                }
                $groupedByBusinessType[$menu->business_type_id]->push($menu);
            }
        }

        // Sort menus within each business type group
        foreach ($groupedByBusinessType as $businessTypeId => $typeMenus) {
            $groupedByBusinessType[$businessTypeId] = $typeMenus->sortBy(function($menu) {
                return $menu->sort_order ?? 999;
            });
        }

        // Rebuild final menu list: common menus first, then business-specific grouped by type
        $finalMenus = $commonMenusList;
        
        // Add business-specific menus in business type order (primary first, then by sort_order)
        foreach ($businessTypes as $businessType) {
            if (isset($groupedByBusinessType[$businessType->id])) {
                $finalMenus = $finalMenus->merge($groupedByBusinessType[$businessType->id]);
            }
        }

        return $finalMenus->values();
    }

    /**
     * Get menu children
     */
    private function getMenuChildren($parentMenu, BusinessType $businessType, User $user)
    {
        $children = $businessType->enabledMenuItems()
            ->where('parent_id', $parentMenu->id)
            ->where('is_active', true)
            ->orderBy('business_type_menu_items.sort_order')
            ->get();

        // Filter by permissions and return as collection
        return $children->filter(function($child) use ($user) {
            return $this->canAccessMenu($user, $child);
        })->values();
    }

    /**
     * Get common menus (always available)
     */
    private function getCommonMenus(User $user)
    {
        $menus = MenuItem::whereIn('slug', self::COMMON_SLUGS)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function($menu) use ($user) {
                $menu->children = $this->getCommonMenuChildren($menu, $user);
                return $menu;
            })
            ->filter(function($menu) use ($user) {
                return $this->canAccessMenu($user, $menu);
            })
            ->values();
            
        return $menus;
    }

    /**
     * Get common menu children
     */
    private function getCommonMenuChildren($parentMenu, User $user)
    {
        return MenuItem::where('parent_id', $parentMenu->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(function($child) use ($user) {
                return $this->canAccessMenu($user, $child);
            })
            ->values();
    }

    /**
     * Check if user can access menu item based on permissions
     */
    private function canAccessMenu(User $user, MenuItem $menu)
    {
        // If menu has no route, it's just a parent - allow access
        if (!$menu->route) {
            return true;
        }

        // Regular users (owners) always have access to everything
        // Staff members need permission checks
        if ($user->role === 'customer' || $user->role === 'admin' || $user->role === null) {
            return true; // Owner has full access
        }

        // Map routes to permissions
        $routePermissions = $this->getRoutePermissions();

        if (isset($routePermissions[$menu->route])) {
            $permission = $routePermissions[$menu->route];
            // Use User's hasPermission method
            return $user->hasPermission($permission['module'], $permission['action']);
        }

        // Default: allow access if no specific permission required
        return true;
    }

    /**
     * Map routes to permissions
     */
    private function getRoutePermissions()
    {
        return [
            'sales.pos' => ['module' => 'sales', 'action' => 'view'],
            'sales.orders' => ['module' => 'sales', 'action' => 'view'],
            'sales.transactions' => ['module' => 'sales', 'action' => 'view'],
            'products.index' => ['module' => 'products', 'action' => 'view'],
            'products.categories' => ['module' => 'products', 'action' => 'view'],
            'products.inventory' => ['module' => 'inventory', 'action' => 'view'],
            'customers.index' => ['module' => 'customers', 'action' => 'view'],
            'customers.groups' => ['module' => 'customers', 'action' => 'view'],
            'business-configuration.edit' => ['module' => 'settings', 'action' => 'edit'],
            'business-configuration.update' => ['module' => 'settings', 'action' => 'edit'],
            'location.switch' => ['module' => 'settings', 'action' => 'view'],
            
            // Staff Management
            'staff.index' => ['module' => 'staff', 'action' => 'view'],
            'staff.create' => ['module' => 'staff', 'action' => 'create'],
            'staff.store' => ['module' => 'staff', 'action' => 'create'],
            'staff.show' => ['module' => 'staff', 'action' => 'view'],
            'staff.edit' => ['module' => 'staff', 'action' => 'edit'],
            'staff.update' => ['module' => 'staff', 'action' => 'edit'],
            'staff.destroy' => ['module' => 'staff', 'action' => 'delete'],
            'staff.roles-by-business-type' => ['module' => 'staff', 'action' => 'view'],
            // Bar Operations
            'bar.suppliers.index' => ['module' => 'suppliers', 'action' => 'view'],
            'bar.suppliers.create' => ['module' => 'suppliers', 'action' => 'create'],
            'bar.suppliers.show' => ['module' => 'suppliers', 'action' => 'view'],
            'bar.suppliers.edit' => ['module' => 'suppliers', 'action' => 'edit'],
            'bar.suppliers.store' => ['module' => 'suppliers', 'action' => 'create'],
            'bar.suppliers.update' => ['module' => 'suppliers', 'action' => 'edit'],
            'bar.suppliers.destroy' => ['module' => 'suppliers', 'action' => 'delete'],
            // Products
            'bar.products.index' => ['module' => 'products', 'action' => 'view'],
            'bar.products.create' => ['module' => 'products', 'action' => 'create'],
            'bar.products.show' => ['module' => 'products', 'action' => 'view'],
            'bar.products.edit' => ['module' => 'products', 'action' => 'edit'],
            'bar.products.store' => ['module' => 'products', 'action' => 'create'],
            'bar.products.update' => ['module' => 'products', 'action' => 'edit'],
            'bar.products.destroy' => ['module' => 'products', 'action' => 'delete'],
            // Stock Receipts
            'bar.stock-receipts.index' => ['module' => 'stock_receipt', 'action' => 'view'],
            'bar.stock-receipts.create' => ['module' => 'stock_receipt', 'action' => 'create'],
            'bar.stock-receipts.show' => ['module' => 'stock_receipt', 'action' => 'view'],
            'bar.stock-receipts.store' => ['module' => 'stock_receipt', 'action' => 'create'],
            'bar.stock-receipts.edit' => ['module' => 'stock_receipt', 'action' => 'edit'],
            'bar.stock-receipts.update' => ['module' => 'stock_receipt', 'action' => 'edit'],
            'bar.stock-receipts.destroy' => ['module' => 'stock_receipt', 'action' => 'delete'],
            // Stock Transfers
            'bar.stock-transfers.index' => ['module' => 'stock_transfer', 'action' => 'view'],
            'bar.stock-transfers.available' => ['module' => 'stock_transfer', 'action' => 'view'],
            'bar.stock-transfers.create' => ['module' => 'stock_transfer', 'action' => 'create'],
            'bar.stock-transfers.show' => ['module' => 'stock_transfer', 'action' => 'view'],
            'bar.stock-transfers.store' => ['module' => 'stock_transfer', 'action' => 'create'],
            'bar.stock-transfers.edit' => ['module' => 'stock_transfer', 'action' => 'edit'],
            'bar.stock-transfers.update' => ['module' => 'stock_transfer', 'action' => 'edit'],
            'bar.stock-transfers.destroy' => ['module' => 'stock_transfer', 'action' => 'delete'],
            'bar.stock-transfers.approve' => ['module' => 'stock_transfer', 'action' => 'edit'],
            'bar.stock-transfers.reject' => ['module' => 'stock_transfer', 'action' => 'edit'],
            'bar.stock-transfers.history' => ['module' => 'stock_transfer', 'action' => 'view'],
            // Orders
            'bar.orders.index' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.orders.create' => ['module' => 'bar_orders', 'action' => 'create'],
            'bar.orders.store' => ['module' => 'bar_orders', 'action' => 'create'],
            'bar.orders.show' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.orders.edit' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.orders.update' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.orders.destroy' => ['module' => 'bar_orders', 'action' => 'delete'],
            'bar.orders.food' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.orders.drinks' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.orders.juice' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.orders.update-status' => ['module' => 'bar_orders', 'action' => 'edit'],
            // Counter Waiter Orders
            'bar.counter.waiter-orders' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.counter.update-order-status' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.counter.mark-paid' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.counter.orders-by-status' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.counter.dashboard' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.counter.customer-orders' => ['module' => 'bar_orders', 'action' => 'view'],
            // Counter Reconciliation
            'bar.counter.reconciliation' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.counter.verify-reconciliation' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.counter.mark-all-paid' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.counter.warehouse-stock' => ['module' => 'inventory', 'action' => 'view'],
            'bar.counter.counter-stock' => ['module' => 'inventory', 'action' => 'view'],
            'bar.counter.analytics' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.counter.stock-transfer-requests' => ['module' => 'stock_transfer', 'action' => 'view'],
            'bar.counter.request-stock-transfer' => ['module' => 'stock_transfer', 'action' => 'create'],
            // Waiter Routes
            'bar.waiter.dashboard' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.waiter.create-order' => ['module' => 'bar_orders', 'action' => 'create'],
            'bar.waiter.order-history' => ['module' => 'bar_orders', 'action' => 'view'],
            // Payments
            'bar.payments.index' => ['module' => 'bar_payments', 'action' => 'view'],
            'bar.payments.show' => ['module' => 'bar_payments', 'action' => 'view'],
            // Beverage Inventory
            'bar.beverage-inventory.index' => ['module' => 'inventory', 'action' => 'view'],
            'bar.beverage-inventory.add' => ['module' => 'inventory', 'action' => 'create'],
            'bar.beverage-inventory.stock-levels' => ['module' => 'inventory', 'action' => 'view'],
            'bar.beverage-inventory.low-stock-alerts' => ['module' => 'inventory', 'action' => 'view'],
            'bar.beverage-inventory.warehouse-stock' => ['module' => 'inventory', 'action' => 'view'],
            // Inventory Settings
            'bar.inventory-settings.index' => ['module' => 'inventory', 'action' => 'view'],
            'bar.inventory-settings.update' => ['module' => 'inventory', 'action' => 'edit'],
            // Counter Settings
            'bar.counter-settings.index' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.counter-settings.update' => ['module' => 'bar_orders', 'action' => 'edit'],
            // Tables
            'bar.tables.index' => ['module' => 'bar_tables', 'action' => 'view'],
            'bar.tables.create' => ['module' => 'bar_tables', 'action' => 'create'],
            'bar.tables.show' => ['module' => 'bar_tables', 'action' => 'view'],
            'bar.tables.edit' => ['module' => 'bar_tables', 'action' => 'edit'],
            'bar.tables.store' => ['module' => 'bar_tables', 'action' => 'create'],
            'bar.tables.update' => ['module' => 'bar_tables', 'action' => 'edit'],
            'bar.tables.destroy' => ['module' => 'bar_tables', 'action' => 'delete'],
            // Chef Routes
            'bar.chef.dashboard' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.chef.kds' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.chef.update-item-status' => ['module' => 'bar_orders', 'action' => 'edit'],
            'bar.chef.latest-orders' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.chef.food-items' => ['module' => 'products', 'action' => 'view'],
            'bar.chef.food-items.create' => ['module' => 'products', 'action' => 'create'],
            'bar.chef.food-items.store' => ['module' => 'products', 'action' => 'create'],
            'bar.chef.food-items.edit' => ['module' => 'products', 'action' => 'edit'],
            'bar.chef.food-items.update' => ['module' => 'products', 'action' => 'edit'],
            'bar.chef.food-items.destroy' => ['module' => 'products', 'action' => 'delete'],
            'bar.chef.food-items.recipe' => ['module' => 'products', 'action' => 'edit'],
            'bar.chef.food-items.recipe.save' => ['module' => 'products', 'action' => 'edit'],
            'bar.chef.ingredients' => ['module' => 'inventory', 'action' => 'view'],
            'bar.chef.ingredients.create' => ['module' => 'inventory', 'action' => 'create'],
            'bar.chef.ingredients.store' => ['module' => 'inventory', 'action' => 'create'],
            'bar.chef.ingredients.edit' => ['module' => 'inventory', 'action' => 'edit'],
            'bar.chef.ingredients.update' => ['module' => 'inventory', 'action' => 'edit'],
            'bar.chef.ingredients.destroy' => ['module' => 'inventory', 'action' => 'delete'],
            // Ingredient Receipts
            'bar.chef.ingredient-receipts' => ['module' => 'inventory', 'action' => 'view'],
            'bar.chef.ingredient-receipts.create' => ['module' => 'inventory', 'action' => 'create'],
            'bar.chef.ingredient-receipts.store' => ['module' => 'inventory', 'action' => 'create'],
            'bar.chef.ingredient-receipts.show' => ['module' => 'inventory', 'action' => 'view'],
            // Ingredient Batches
            'bar.chef.ingredient-batches' => ['module' => 'inventory', 'action' => 'view'],
            // Ingredient Stock Movements
            'bar.chef.ingredient-stock-movements' => ['module' => 'inventory', 'action' => 'view'],
            'bar.chef.reports' => ['module' => 'bar_orders', 'action' => 'view'],
            'bar.chef.reconciliation' => ['module' => 'bar_orders', 'action' => 'view'],
            // Stock Keeper Ingredients Management Routes
            'bar.stock-keeper.ingredients' => ['module' => 'inventory', 'action' => 'view'],
            'bar.stock-keeper.ingredients.create' => ['module' => 'inventory', 'action' => 'create'],
            'bar.stock-keeper.ingredients.store' => ['module' => 'inventory', 'action' => 'create'],
            'bar.stock-keeper.ingredients.edit' => ['module' => 'inventory', 'action' => 'edit'],
            'bar.stock-keeper.ingredients.update' => ['module' => 'inventory', 'action' => 'edit'],
            'bar.stock-keeper.ingredients.destroy' => ['module' => 'inventory', 'action' => 'delete'],
            // Ingredient Receipts
            'bar.stock-keeper.ingredient-receipts' => ['module' => 'inventory', 'action' => 'view'],
            'bar.stock-keeper.ingredient-receipts.create' => ['module' => 'inventory', 'action' => 'create'],
            'bar.stock-keeper.ingredient-receipts.store' => ['module' => 'inventory', 'action' => 'create'],
            'bar.stock-keeper.ingredient-receipts.show' => ['module' => 'inventory', 'action' => 'view'],
            // Ingredient Batches
            'bar.stock-keeper.ingredient-batches' => ['module' => 'inventory', 'action' => 'view'],
            // Ingredient Stock Movements
            'bar.stock-keeper.ingredient-stock-movements' => ['module' => 'inventory', 'action' => 'view'],
            // Accountant
            'accountant.dashboard' => ['module' => 'finance', 'action' => 'view'],
            'accountant.reconciliations' => ['module' => 'finance', 'action' => 'view'],
            'accountant.reconciliation-details' => ['module' => 'finance', 'action' => 'view'],
            'accountant.reports' => ['module' => 'reports', 'action' => 'view'],
            'reports.stock-receipts' => ['module' => 'reports', 'action' => 'view'],
            'reports.stock-transfers' => ['module' => 'reports', 'action' => 'view'],
            // HR Routes
            'hr.dashboard' => ['module' => 'hr', 'action' => 'view'],
            'hr.attendance' => ['module' => 'hr', 'action' => 'view'],
            'hr.attendance.mark' => ['module' => 'hr', 'action' => 'create'],
            'hr.biometric-devices' => ['module' => 'hr', 'action' => 'view'],
            'hr.biometric-devices.test-connection' => ['module' => 'hr', 'action' => 'edit'],
            'hr.biometric-devices.register-staff' => ['module' => 'hr', 'action' => 'edit'],
            'hr.biometric-devices.unregister-staff' => ['module' => 'hr', 'action' => 'edit'],
            'hr.biometric-devices.sync-attendance' => ['module' => 'hr', 'action' => 'edit'],
            'hr.leaves' => ['module' => 'hr', 'action' => 'view'],
            'hr.leaves.update-status' => ['module' => 'hr', 'action' => 'edit'],
            'hr.payroll' => ['module' => 'hr', 'action' => 'view'],
            'hr.payroll.generate' => ['module' => 'hr', 'action' => 'create'],
            'hr.performance-reviews' => ['module' => 'hr', 'action' => 'view'],
            'hr.performance-reviews.store' => ['module' => 'hr', 'action' => 'create'],
            // Reports
            'reports.index' => ['module' => 'reports', 'action' => 'view'],
            // Marketing
            'marketing.dashboard' => ['module' => 'marketing', 'action' => 'view'],
            'marketing.customers' => ['module' => 'marketing', 'action' => 'view'],
            'marketing.campaigns' => ['module' => 'marketing', 'action' => 'view'],
            'marketing.campaigns.create' => ['module' => 'marketing', 'action' => 'create'],
            'marketing.campaigns.store' => ['module' => 'marketing', 'action' => 'create'],
            'marketing.campaigns.show' => ['module' => 'marketing', 'action' => 'view'],
            'marketing.campaigns.send' => ['module' => 'marketing', 'action' => 'create'],
            'marketing.templates' => ['module' => 'marketing', 'action' => 'view'],
            'marketing.templates.store' => ['module' => 'marketing', 'action' => 'create'],
        ];
    }

    /**
     * Render sidebar menu HTML
     */
    public function renderSidebar(User $user)
    {
        $menus = $this->getUserMenus($user);
        $html = '';

        foreach ($menus as $menu) {
            if ($menu->children && $menu->children->count() > 0) {
                // Parent menu with children
                $html .= $this->renderParentMenu($menu);
            } else {
                // Single menu item
                $html .= $this->renderSingleMenu($menu);
            }
        }

        return $html;
    }

    /**
     * Render parent menu with children
     */
    private function renderParentMenu($menu)
    {
        $isActive = request()->routeIs($menu->route ?? '') || 
                   ($menu->children && $menu->children->contains(function($child) {
                       return request()->routeIs($child->route ?? '');
                   }));

        $html = '<li class="treeview' . ($isActive ? ' is-expanded' : '') . '">';
        $html .= '<a class="app-menu__item" href="#" data-toggle="treeview">';
        $html .= '<i class="app-menu__icon ' . ($menu->icon ?? 'fa fa-circle') . '"></i>';
        $html .= '<span class="app-menu__label">' . e($menu->name) . '</span>';
        $html .= '<i class="treeview-indicator fa fa-angle-right"></i>';
        $html .= '</a>';
        $html .= '<ul class="treeview-menu">';

        foreach ($menu->children as $child) {
            $childActive = request()->routeIs($child->route ?? '');
            $html .= '<li>';
            $html .= '<a class="treeview-item' . ($childActive ? ' active' : '') . '" href="' . $child->full_url . '">';
            $html .= '<i class="icon ' . ($child->icon ?? 'fa fa-circle-o') . '"></i> ' . e($child->name);
            $html .= '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</li>';

        return $html;
    }

    /**
     * Render single menu item
     */
    private function renderSingleMenu($menu)
    {
        $isActive = request()->routeIs($menu->route ?? '');
        
        $html = '<li>';
        $html .= '<a class="app-menu__item' . ($isActive ? ' active' : '') . '" href="' . $menu->full_url . '">';
        $html .= '<i class="app-menu__icon ' . ($menu->icon ?? 'fa fa-circle') . '"></i>';
        $html .= '<span class="app-menu__label">' . e($menu->name) . '</span>';
        $html .= '</a>';
        $html .= '</li>';

        return $html;
    }
}

