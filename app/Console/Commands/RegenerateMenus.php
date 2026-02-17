<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\BusinessConfigurationController;

class RegenerateMenus extends Command
{
    protected $signature = 'menus:regenerate {--user= : Specific user email}';
    protected $description = 'Regenerate menus for configured users';

    public function handle()
    {
        $userEmail = $this->option('user');
        
        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            if (!$user) {
                $this->error("User not found: {$userEmail}");
                return;
            }
            $users = collect([$user]);
        } else {
            $users = User::where('is_configured', true)
                ->where('role', 'customer')
                ->get();
        }

        $controller = new BusinessConfigurationController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateMenuItems');
        $method->setAccessible(true);

        foreach ($users as $user) {
            $method->invoke($controller, $user);
            $this->info("Menus regenerated for: {$user->name} ({$user->email})");
        }

        $this->info("Done! Regenerated menus for " . $users->count() . " user(s).");
    }
}




