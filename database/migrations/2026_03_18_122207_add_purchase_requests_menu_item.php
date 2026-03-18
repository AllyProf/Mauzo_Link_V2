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
        DB::table('menu_items')->insert([
            'name' => 'Purchase Requests',
            'slug' => 'purchase-requests',
            'icon' => 'fa fa-shopping-cart',
            'route' => 'purchase-requests.index',
            'parent_id' => null,
            'sort_order' => 55,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('menu_items')->where('slug', 'purchase-requests')->delete();
    }
};
