<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::table('menu_items')->insert([
            'name' => 'Sales Targets',
            'slug' => 'targets',
            'icon' => 'fa-bullseye',
            'route' => 'manager.targets.index',
            'is_active' => true,
            'sort_order' => 12, // After Stock Audit
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('menu_items')->where('slug', 'targets')->delete();
    }
};
