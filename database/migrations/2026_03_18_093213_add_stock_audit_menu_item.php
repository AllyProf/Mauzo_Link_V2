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
        // Add the 'Stock-to-Cash Audit' menu item
        DB::table('menu_items')->insert([
            'name' => 'Stock-to-Cash Audit',
            'slug' => 'stock-audit',
            'route' => 'manager.stock-audit',
            'icon' => 'fa-line-chart',
            'parent_id' => null,
            'sort_order' => 11, // Just after Financial Reconciliation
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
        DB::table('menu_items')->where('slug', 'stock-audit')->delete();
    }
};
