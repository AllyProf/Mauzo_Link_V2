<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessTypes = [
            [
                'name' => 'Bar',
                'slug' => 'bar',
                'description' => 'Bar and beverage management',
                'icon' => 'fa-glass',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Restaurant',
                'slug' => 'restaurant',
                'description' => 'Restaurant and food service management',
                'icon' => 'fa-cutlery',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Juice Point',
                'slug' => 'juice',
                'description' => 'Juice point and beverage management',
                'icon' => 'fa-tint',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Pharmacy',
                'slug' => 'pharmacy',
                'description' => 'Pharmacy and medicine management',
                'icon' => 'fa-medkit',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Retail Store',
                'slug' => 'retail',
                'description' => 'General retail store management',
                'icon' => 'fa-shopping-bag',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Supermarket',
                'slug' => 'supermarket',
                'description' => 'Supermarket and grocery management',
                'icon' => 'fa-shopping-cart',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Cafe',
                'slug' => 'cafe',
                'description' => 'Cafe and coffee shop management',
                'icon' => 'fa-coffee',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Bakery',
                'slug' => 'bakery',
                'description' => 'Bakery and pastry management',
                'icon' => 'fa-birthday-cake',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Clothing Store',
                'slug' => 'clothing',
                'description' => 'Clothing and fashion store management',
                'icon' => 'fa-tshirt',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'Electronics Store',
                'slug' => 'electronics',
                'description' => 'Electronics and gadgets store management',
                'icon' => 'fa-laptop',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'General Store',
                'slug' => 'general',
                'description' => 'General purpose store management',
                'icon' => 'fa-store',
                'is_active' => true,
                'sort_order' => 11,
            ],
        ];

        foreach ($businessTypes as $type) {
            BusinessType::updateOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
