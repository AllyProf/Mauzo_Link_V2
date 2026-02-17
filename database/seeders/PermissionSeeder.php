<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            'sales' => 'Sales',
            'products' => 'Products',
            'customers' => 'Customers',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'inventory' => 'Inventory',
            'staff' => 'Staff Management',
            'finance' => 'Finance',
            'suppliers' => 'Suppliers',
            'stock_receipt' => 'Stock Receipt',
            'stock_transfer' => 'Stock Transfer',
            'bar_orders' => 'Bar Orders',
            'bar_payments' => 'Bar Payments',
            'bar_tables' => 'Bar Tables',
            'marketing' => 'Marketing',
            'hr' => 'Human Resources',
        ];

        $actions = [
            'view' => 'View',
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ];

        foreach ($modules as $module => $moduleName) {
            foreach ($actions as $action => $actionName) {
                Permission::updateOrCreate(
                    [
                        'module' => $module,
                        'action' => $action,
                    ],
                    [
                        'name' => $actionName . ' ' . $moduleName,
                        'description' => 'Permission to ' . strtolower($actionName) . ' ' . strtolower($moduleName),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
