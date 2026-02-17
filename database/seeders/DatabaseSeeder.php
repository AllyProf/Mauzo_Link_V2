<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Plans
        $this->call([
            PlanSeeder::class,
        ]);

        // Seed Business Configuration Data
        $this->call([
            BusinessTypeSeeder::class,
            PermissionSeeder::class,
            MenuItemSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            StaffSeeder::class,
            BarInventorySeeder::class,
        ]);
    }
}
