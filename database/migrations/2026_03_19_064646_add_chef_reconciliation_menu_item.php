<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Find the restaurant business type
        $restaurant = DB::table('business_types')->where('slug', 'restaurant')->first();

        // 2. Create the "Chef Reconciliation" standalone top-level menu item
        // (shown directly in chef's sidebar as a common-slug type item)
        // We'll add it under the purchase-requests slug group so it's in COMMON_SLUGS
        // Better: add chef-reconciliation as its own standalone top-level common item

        // First, check if it already exists
        $existing = DB::table('menu_items')
            ->where('slug', 'chef-reconciliation')
            ->first();

        if (!$existing) {
            $menuItemId = DB::table('menu_items')->insertGetId([
                'name'      => 'Reconciliation',
                'slug'      => 'chef-reconciliation',
                'route'     => 'bar.chef.reconciliation',
                'icon'      => 'fa-balance-scale',
                'parent_id' => null,
                'is_active' => true,
                'sort_order'=> 50,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);

            // Link to Restaurant business type
            if ($restaurant) {
                DB::table('business_type_menu_items')->insert([
                    'business_type_id' => $restaurant->id,
                    'menu_item_id'     => $menuItemId,
                    'sort_order'       => 50,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        // 3. The "purchase-requests" slug is already in MenuService COMMON_SLUGS but only exists
        // as a child item (parent_id=30). We need a top-level item with that slug so COMMON_SLUGS
        // filtering (whereNull('parent_id')) can pick it up.
        // 3. Create a top-level standalone "Purchase Requests" common menu item
        // Needs a unique slug 'common-purchase-requests' because 'purchase-requests' is taken by the child item
        $existingTopLevelPr = DB::table('menu_items')
            ->where('slug', 'common-purchase-requests')
            ->first();

        if (!$existingTopLevelPr) {
            DB::table('menu_items')->insert([
                'name'      => 'Purchase Requests',
                'slug'      => 'common-purchase-requests',
                'route'     => 'purchase-requests.index',
                'icon'      => 'fa-shopping-cart',
                'parent_id' => null,
                'sort_order'=> 60,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $item = DB::table('menu_items')->where('slug', 'chef-reconciliation')->first();
        if ($item) {
            DB::table('business_type_menu_items')->where('menu_item_id', $item->id)->delete();
            DB::table('menu_items')->where('id', $item->id)->delete();
        }
    }
};
