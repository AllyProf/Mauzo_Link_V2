<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Deactivate any old plans that are not Free, Basic, or Pro
        Plan::whereNotIn('slug', ['free', 'basic', 'pro'])
            ->update(['is_active' => false]);
        
        $plans = [
            [
                'name' => 'Free Plan',
                'slug' => 'free',
                'description' => 'Full access to all features for 30 days. Perfect for trying out MauzoLink with no limitations.',
                'price' => 0.00,
                'trial_days' => 30,
                'features' => [
                    'All POS Features Available',
                    'Unlimited Products',
                    'Unlimited Customers',
                    'Unlimited Sales Transactions',
                    'Full Inventory Management',
                    'Advanced Reports & Analytics',
                    'Multiple Locations Support',
                    'Multi-User Support',
                    'Customer Loyalty Program',
                    'Receipt Printing',
                    'Email & SMS Support',
                    '30 Days Free Trial',
                ],
                'max_locations' => 999, // Unlimited during trial
                'max_users' => 999, // Unlimited during trial
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic Plan',
                'slug' => 'basic',
                'description' => 'Perfect for sole proprietors who run their business alone. All essential features for single-user operations.',
                'price' => 10000.00, // 10,000 TSh per month
                'trial_days' => 0, // No trial for paid plans
                'features' => [
                    'All Essential POS Features',
                    'Unlimited Products',
                    'Unlimited Customers',
                    'Unlimited Sales Transactions',
                    'Full Inventory Management',
                    'Basic Reports & Analytics',
                    'Single Location',
                    'Single User (Owner Only)',
                    'Receipt Printing',
                    'Email Support',
                    'Mobile App Access',
                    'Data Backup & Security',
                ],
                'max_locations' => 1,
                'max_users' => 1, // Sole proprietor - single user
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro Plan',
                'slug' => 'pro',
                'description' => 'Complete solution for businesses with multiple staff members. All features with unlimited users and locations.',
                'price' => 30000.00, // 30,000 TSh per month
                'trial_days' => 0, // No trial for paid plans
                'features' => [
                    'All Features Available',
                    'Unlimited Products',
                    'Unlimited Customers',
                    'Unlimited Sales Transactions',
                    'Full Inventory Management',
                    'Advanced Reports & Analytics',
                    'Unlimited Locations',
                    'Unlimited Users & Staff',
                    'Role-Based Access Control',
                    'Customer Loyalty Program',
                    'Priority Email & SMS Support',
                    'Receipt Printing',
                    'Mobile App Access',
                    'API Access',
                    'Custom Integrations',
                    'Data Backup & Security',
                    '24/7 Support',
                ],
                'max_locations' => 999, // Unlimited
                'max_users' => 999, // Unlimited
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
