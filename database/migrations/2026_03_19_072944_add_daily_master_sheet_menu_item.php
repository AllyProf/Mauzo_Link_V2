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
        $accountantMenu = \App\Models\MenuItem::where('slug', 'accountant-parent')->first();
        if ($accountantMenu) {
            \App\Models\MenuItem::firstOrCreate([
                'name' => 'Daily Master Sheet',
                'slug' => 'daily-master-sheet',
                'route' => 'accountant.daily-master-sheet',
                'icon' => 'fa-book',
                'parent_id' => $accountantMenu->id,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\MenuItem::where('slug', 'daily-master-sheet')->delete();
    }
};
