<?php

namespace App\Services;

use App\Models\Permission;

class RoleSuggestionService
{
    /**
     * Get suggested roles for a business type
     */
    public static function getSuggestedRolesForBusinessType($businessTypeSlug)
    {
        $suggestions = [
            'bar' => [
                [
                    'name' => 'Manager',
                    'description' => 'Full management access to bar operations',
                    'permissions' => [
                        'bar_orders' => ['view', 'create', 'edit'],
                        'bar_payments' => ['view', 'create', 'edit'],
                        'bar_tables' => ['view', 'create', 'edit', 'delete'],
                        'products' => ['view', 'create', 'edit'],
                        'inventory' => ['view', 'create', 'edit'],
                        'stock_receipt' => ['view', 'create', 'edit'],
                        'stock_transfer' => ['view', 'create', 'edit'],
                        'suppliers' => ['view', 'create', 'edit'],
                        'customers' => ['view', 'create', 'edit'],
                        'reports' => ['view'],
                        'staff' => ['view'],
                    ]
                ],
                [
                    'name' => 'Stock Keeper',
                    'description' => 'Manage inventory, stock receipts, and stock transfers. Register products, receive receipts, process transfer requests, and track stock movements.',
                    'permissions' => [
                        'inventory' => ['view', 'create', 'edit'],
                        'stock_receipt' => ['view', 'create', 'edit'],
                        'stock_transfer' => ['view', 'create', 'edit'],
                        'products' => ['view', 'create', 'edit'],
                        'suppliers' => ['view', 'create', 'edit'],
                        'finance' => ['view'],
                        'reports' => ['view'],
                    ]
                ],
                [
                    'name' => 'Counter',
                    'description' => 'Handle counter operations, orders, and payments',
                    'permissions' => [
                        'bar_orders' => ['view', 'create', 'edit'],
                        'bar_payments' => ['view', 'create', 'edit'],
                        'bar_tables' => ['view'],
                        'products' => ['view'],
                        'customers' => ['view', 'create'],
                        'stock_transfer' => ['view'], // Can view stock transfer requests
                    ]
                ],
                [
                    'name' => 'Waiter',
                    'description' => 'Take orders and serve customers',
                    'permissions' => [
                        'bar_orders' => ['view', 'create'],
                        'bar_tables' => ['view'],
                        'products' => ['view'],
                        'customers' => ['view', 'create'],
                    ]
                ],
            ],
            'restaurant' => [
                [
                    'name' => 'Manager',
                    'description' => 'Full management access to restaurant operations',
                    'permissions' => [
                        'bar_orders' => ['view', 'create', 'edit'],
                        'bar_payments' => ['view', 'create', 'edit'],
                        'bar_tables' => ['view', 'create', 'edit', 'delete'],
                        'products' => ['view', 'create', 'edit'],
                        'inventory' => ['view', 'create', 'edit'],
                        'stock_receipt' => ['view', 'create', 'edit'],
                        'stock_transfer' => ['view', 'create', 'edit'],
                        'suppliers' => ['view', 'create', 'edit'],
                        'customers' => ['view', 'create', 'edit'],
                        'reports' => ['view'],
                        'staff' => ['view'],
                    ]
                ],
                [
                    'name' => 'Chef',
                    'description' => 'Manage kitchen operations and food preparation',
                    'permissions' => [
                        'bar_orders' => ['view', 'edit'],
                        'products' => ['view', 'edit'],
                        'inventory' => ['view', 'edit'],
                        'stock_receipt' => ['view'],
                    ]
                ],
                [
                    'name' => 'Waiter',
                    'description' => 'Take orders and serve customers',
                    'permissions' => [
                        'bar_orders' => ['view', 'create'],
                        'bar_tables' => ['view'],
                        'products' => ['view'],
                        'customers' => ['view', 'create'],
                    ]
                ],
                [
                    'name' => 'Cashier',
                    'description' => 'Handle payments and transactions',
                    'permissions' => [
                        'bar_orders' => ['view'],
                        'bar_payments' => ['view', 'create', 'edit'],
                        'bar_tables' => ['view'],
                        'customers' => ['view'],
                    ]
                ],
            ],
            'juice' => [
                [
                    'name' => 'Manager',
                    'description' => 'Full management access to juice point operations',
                    'permissions' => [
                        'bar_orders' => ['view', 'create', 'edit'],
                        'bar_payments' => ['view', 'create', 'edit'],
                        'products' => ['view', 'create', 'edit'],
                        'inventory' => ['view', 'create', 'edit'],
                        'stock_receipt' => ['view', 'create', 'edit'],
                        'stock_transfer' => ['view', 'create', 'edit'],
                        'suppliers' => ['view', 'create', 'edit'],
                        'customers' => ['view', 'create', 'edit'],
                        'reports' => ['view'],
                        'staff' => ['view'],
                    ]
                ],
                [
                    'name' => 'Juice Maker',
                    'description' => 'Prepare and serve juice orders',
                    'permissions' => [
                        'bar_orders' => ['view', 'create', 'edit'],
                        'products' => ['view'],
                        'inventory' => ['view'],
                    ]
                ],
                [
                    'name' => 'Counter',
                    'description' => 'Handle counter operations and payments',
                    'permissions' => [
                        'bar_orders' => ['view', 'create'],
                        'bar_payments' => ['view', 'create', 'edit'],
                        'products' => ['view'],
                        'customers' => ['view', 'create'],
                        'stock_transfer' => ['view'], // Can view stock transfer requests
                    ]
                ],
            ],
        ];

        return $suggestions[$businessTypeSlug] ?? [];
    }

    /**
     * Get permission IDs for a role suggestion
     */
    public static function getPermissionIdsForRole($roleSuggestion)
    {
        $permissionIds = [];

        foreach ($roleSuggestion['permissions'] as $module => $actions) {
            foreach ($actions as $action) {
                $permission = Permission::where('module', $module)
                    ->where('action', $action)
                    ->where('is_active', true)
                    ->first();

                if ($permission) {
                    $permissionIds[] = $permission->id;
                }
            }
        }

        return $permissionIds;
    }

    /**
     * Get all suggested roles for multiple business types
     */
    public static function getSuggestedRolesForBusinessTypes($businessTypeSlugs)
    {
        $allSuggestions = [];
        $seenRoles = [];

        foreach ($businessTypeSlugs as $slug) {
            $suggestions = self::getSuggestedRolesForBusinessType($slug);
            
            foreach ($suggestions as $suggestion) {
                // Avoid duplicates - if same role name exists, merge permissions
                $roleKey = strtolower($suggestion['name']);
                
                if (isset($seenRoles[$roleKey])) {
                    // Merge permissions
                    $existingIndex = $seenRoles[$roleKey];
                    foreach ($suggestion['permissions'] as $module => $actions) {
                        if (!isset($allSuggestions[$existingIndex]['permissions'][$module])) {
                            $allSuggestions[$existingIndex]['permissions'][$module] = [];
                        }
                        $allSuggestions[$existingIndex]['permissions'][$module] = array_unique(
                            array_merge(
                                $allSuggestions[$existingIndex]['permissions'][$module],
                                $actions
                            )
                        );
                    }
                } else {
                    $allSuggestions[] = $suggestion;
                    $seenRoles[$roleKey] = count($allSuggestions) - 1;
                }
            }
        }

        return $allSuggestions;
    }
}


