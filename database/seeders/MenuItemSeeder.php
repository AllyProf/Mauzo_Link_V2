<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\BusinessType;
use App\Models\BusinessTypeMenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common menu items (available to all business types)
        $commonMenus = [
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'fa-dashboard',
                'route' => 'dashboard',
                'parent_id' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'icon' => 'fa-shopping-cart',
                'route' => null,
                'parent_id' => null,
                'sort_order' => 2,
                'children' => [
                    ['name' => 'Point of Sale', 'slug' => 'sales-pos', 'icon' => 'fa-credit-card', 'route' => 'sales.pos', 'sort_order' => 1],
                    ['name' => 'Orders', 'slug' => 'sales-orders', 'icon' => 'fa-list', 'route' => 'sales.orders', 'sort_order' => 2],
                    ['name' => 'Transactions', 'slug' => 'sales-transactions', 'icon' => 'fa-exchange', 'route' => 'sales.transactions', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Products',
                'slug' => 'products',
                'icon' => 'fa-cube',
                'route' => null,
                'parent_id' => null,
                'sort_order' => 3,
                'children' => [
                    ['name' => 'All Products', 'slug' => 'products-all', 'icon' => 'fa-list', 'route' => 'products.index', 'sort_order' => 1],
                    ['name' => 'Categories', 'slug' => 'products-categories', 'icon' => 'fa-tags', 'route' => 'products.categories', 'sort_order' => 2],
                    ['name' => 'Inventory', 'slug' => 'products-inventory', 'icon' => 'fa-archive', 'route' => 'products.inventory', 'sort_order' => 3],
                ],
            ],
            [
                'name' => 'Customers',
                'slug' => 'customers',
                'icon' => 'fa-users',
                'route' => null,
                'parent_id' => null,
                'sort_order' => 4,
                'children' => [
                    ['name' => 'All Customers', 'slug' => 'customers-all', 'icon' => 'fa-list', 'route' => 'customers.index', 'sort_order' => 1],
                    ['name' => 'Groups', 'slug' => 'customers-groups', 'icon' => 'fa-group', 'route' => 'customers.groups', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Staff',
                'slug' => 'staff',
                'icon' => 'fa-user-md',
                'route' => null,
                'parent_id' => null,
                'sort_order' => 5,
                'children' => [
                    ['name' => 'All Staff', 'slug' => 'staff-all', 'icon' => 'fa-list', 'route' => 'staff.index', 'sort_order' => 1],
                    ['name' => 'Register Staff', 'slug' => 'staff-create', 'icon' => 'fa-user-plus', 'route' => 'staff.create', 'sort_order' => 2],
                ],
            ],
            [
                'name' => 'Reports',
                'slug' => 'reports',
                'icon' => 'fa-bar-chart',
                'route' => null,
                'parent_id' => null,
                'sort_order' => 6,
            ],
            [
                'name' => 'Settings',
                'slug' => 'settings',
                'icon' => 'fa-cog',
                'route' => 'settings.index',
                'parent_id' => null,
                'sort_order' => 7,
            ],
        ];

        // Create common menus
        foreach ($commonMenus as $menu) {
            $children = $menu['children'] ?? [];
            unset($menu['children']);
            
            $parentMenu = MenuItem::updateOrCreate(
                ['slug' => $menu['slug']],
                array_merge($menu, ['is_active' => true])
            );

            // Create children
            foreach ($children as $child) {
                MenuItem::updateOrCreate(
                    ['slug' => $child['slug']],
                    array_merge($child, ['parent_id' => $parentMenu->id, 'is_active' => true])
                );
            }
        }

        // Link common menus to ALL business types
        $this->linkCommonMenusToAllBusinessTypes();

        // Business-specific menu items
        $this->createBusinessTypeMenus();
    }

    /**
     * Link common menus to all business types
     */
    private function linkCommonMenusToAllBusinessTypes()
    {
        $businessTypes = BusinessType::where('is_active', true)->get();
        $commonMenuSlugs = ['dashboard', 'sales', 'products', 'customers', 'reports', 'settings'];
        $commonMenus = MenuItem::whereIn('slug', $commonMenuSlugs)
            ->whereNull('parent_id')
            ->get();

        foreach ($businessTypes as $businessType) {
            foreach ($commonMenus as $menu) {
                BusinessTypeMenuItem::firstOrCreate(
                    [
                        'business_type_id' => $businessType->id,
                        'menu_item_id' => $menu->id,
                    ],
                    [
                        'is_enabled' => true,
                        'sort_order' => $menu->sort_order ?? 0,
                    ]
                );

                // Also link children menus
                $children = MenuItem::where('parent_id', $menu->id)->get();
                foreach ($children as $child) {
                    BusinessTypeMenuItem::firstOrCreate(
                        [
                            'business_type_id' => $businessType->id,
                            'menu_item_id' => $child->id,
                        ],
                        [
                            'is_enabled' => true,
                            'sort_order' => $child->sort_order ?? 0,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Create business-specific menu items
     */
    private function createBusinessTypeMenus()
    {
        $businessTypes = BusinessType::all();

        foreach ($businessTypes as $businessType) {
            $menus = $this->getBusinessTypeMenus($businessType->slug);
            
            foreach ($menus as $menu) {
                $children = $menu['children'] ?? [];
                // Remove children from menu array to avoid saving it to database
                if (isset($menu['children'])) {
                    unset($menu['children']);
                }
                
                // Create or update parent menu item (only include valid database columns)
                $menuData = array_intersect_key($menu, array_flip(['name', 'slug', 'icon', 'route', 'sort_order']));
                $parentMenu = MenuItem::updateOrCreate(
                    ['slug' => $menu['slug']],
                    array_merge($menuData, ['is_active' => true, 'parent_id' => null])
                );

                // Create or update children menu items (only include valid database columns)
                foreach ($children as $child) {
                    $childData = array_intersect_key($child, array_flip(['name', 'slug', 'icon', 'route', 'sort_order']));
                    MenuItem::updateOrCreate(
                        ['slug' => $child['slug']],
                        array_merge($childData, ['parent_id' => $parentMenu->id, 'is_active' => true])
                    );
                }
                
                // Link parent menu to business type
                BusinessTypeMenuItem::updateOrCreate(
                    [
                        'business_type_id' => $businessType->id,
                        'menu_item_id' => $parentMenu->id,
                    ],
                    [
                        'is_enabled' => true,
                        'sort_order' => $menu['sort_order'] ?? 0,
                    ]
                );
                
                // Link children menus to business type
                foreach ($children as $child) {
                    $childMenuItem = MenuItem::where('slug', $child['slug'])->first();
                    if ($childMenuItem) {
                        BusinessTypeMenuItem::updateOrCreate(
                            [
                                'business_type_id' => $businessType->id,
                                'menu_item_id' => $childMenuItem->id,
                            ],
                            [
                                'is_enabled' => true,
                                'sort_order' => $child['sort_order'] ?? 0,
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * Get menu items for specific business type
     */
    private function getBusinessTypeMenus($businessTypeSlug)
    {
        // First, create the menu items if they don't exist
        $menuDefinitions = [
            'bar' => [
                ['name' => 'Sales & Orders', 'slug' => 'bar-sales-orders', 'icon' => 'fa-shopping-cart', 'route' => null, 'sort_order' => 2.5, 'children' => [
                    ['name' => 'Waiter Orders', 'slug' => 'bar-counter-waiter-orders', 'icon' => 'fa-bell', 'route' => 'bar.counter.waiter-orders', 'sort_order' => 1],
                    ['name' => 'All Orders', 'slug' => 'bar-orders-all', 'icon' => 'fa-list', 'route' => 'bar.orders.index', 'sort_order' => 2],
                    ['name' => 'Payments', 'slug' => 'bar-payments', 'icon' => 'fa-money', 'route' => 'bar.payments.index', 'sort_order' => 3],
                    ['name' => 'Tables', 'slug' => 'bar-tables', 'icon' => 'fa-table', 'route' => 'bar.tables.index', 'sort_order' => 4],
                ]],
                ['name' => 'Stock Management', 'slug' => 'bar-stock-mgmt', 'icon' => 'fa-archive', 'route' => null, 'sort_order' => 3.5, 'children' => [
                    ['name' => 'Register Products', 'slug' => 'bar-products-create', 'icon' => 'fa-plus-circle', 'route' => 'bar.products.create', 'sort_order' => 1],
                    ['name' => 'Products List', 'slug' => 'bar-products', 'icon' => 'fa-list', 'route' => 'bar.products.index', 'sort_order' => 2],
                    ['name' => 'Receiving Stock', 'slug' => 'bar-stock-receipts', 'icon' => 'fa-download', 'route' => 'bar.stock-receipts.index', 'sort_order' => 3],
                    ['name' => 'Stock Transfers', 'slug' => 'bar-stock-transfers', 'icon' => 'fa-exchange', 'route' => 'bar.stock-transfers.index', 'sort_order' => 4],
                    ['name' => 'Stock Levels', 'slug' => 'bar-stock-levels', 'icon' => 'fa-bar-chart', 'route' => 'bar.beverage-inventory.stock-levels', 'sort_order' => 5],
                    ['name' => 'Warehouse Stock', 'slug' => 'bar-warehouse-stock', 'icon' => 'fa-archive', 'route' => 'bar.beverage-inventory.warehouse-stock', 'sort_order' => 6],
                ]],
                ['name' => 'Operations & Settings', 'slug' => 'bar-ops-settings', 'icon' => 'fa-gears', 'route' => null, 'sort_order' => 4.5, 'children' => [
                    ['name' => 'Suppliers', 'slug' => 'bar-suppliers', 'icon' => 'fa-truck', 'route' => 'bar.suppliers.index', 'sort_order' => 1],
                    ['name' => 'Reconciliation', 'slug' => 'bar-reconciliation', 'icon' => 'fa-balance-scale', 'route' => 'bar.counter.reconciliation', 'sort_order' => 2],
                    ['name' => 'Counter Settings', 'slug' => 'bar-counter-settings', 'icon' => 'fa-cog', 'route' => 'bar.counter-settings.index', 'sort_order' => 3],
                ]],
            ],
            'restaurant' => [
                ['name' => 'Restaurant Management', 'slug' => 'restaurant-management', 'icon' => 'fa-cutlery', 'route' => null, 'sort_order' => 2.5, 'children' => [
                    ['name' => 'Food Orders', 'slug' => 'restaurant-orders-food', 'icon' => 'fa-cutlery', 'route' => 'bar.orders.food', 'sort_order' => 1],
                ]],
                ['name' => 'Table Management', 'slug' => 'table-management', 'icon' => 'fa-table', 'route' => null, 'sort_order' => 2.6],
                ['name' => 'Kitchen Display', 'slug' => 'kitchen-display', 'icon' => 'fa-tv', 'route' => null, 'sort_order' => 2.7],
                ['name' => 'Menu Management', 'slug' => 'menu-management', 'icon' => 'fa-book', 'route' => null, 'sort_order' => 3.5],
            ],
            'pharmacy' => [
                ['name' => 'Pharmacy Management', 'slug' => 'pharmacy-management', 'icon' => 'fa-medkit', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Prescriptions', 'slug' => 'prescriptions', 'icon' => 'fa-file-text', 'route' => null, 'sort_order' => 2.6],
                ['name' => 'Medicine Inventory', 'slug' => 'medicine-inventory', 'icon' => 'fa-pills', 'route' => null, 'sort_order' => 3.5],
                ['name' => 'Expiry Tracking', 'slug' => 'expiry-tracking', 'icon' => 'fa-calendar', 'route' => null, 'sort_order' => 3.6],
            ],
            'supermarket' => [
                ['name' => 'Supermarket Management', 'slug' => 'supermarket-management', 'icon' => 'fa-shopping-basket', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Grocery Inventory', 'slug' => 'grocery-inventory', 'icon' => 'fa-cart-plus', 'route' => null, 'sort_order' => 3.5],
                ['name' => 'Department Management', 'slug' => 'department-management', 'icon' => 'fa-sitemap', 'route' => null, 'sort_order' => 3.6],
            ],
            'cafe' => [
                ['name' => 'Cafe Management', 'slug' => 'cafe-management', 'icon' => 'fa-coffee', 'route' => null, 'sort_order' => 2.5],
                ['name' => 'Menu Items', 'slug' => 'cafe-menu-items', 'icon' => 'fa-list', 'route' => null, 'sort_order' => 2.6],
                ['name' => 'Beverage Inventory', 'slug' => 'cafe-beverage-inventory', 'icon' => 'fa-mug-hot', 'route' => null, 'sort_order' => 3.5],
            ],
        ];

        $menus = $menuDefinitions[$businessTypeSlug] ?? [];
        
        // Note: Menu items are created in createBusinessTypeMenus() method
        // This method just returns the menu definitions

        return $menus;
    }
}
