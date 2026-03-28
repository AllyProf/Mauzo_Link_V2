<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Fix Staff - Link all to "Bar" (ID 1)
$staffCount = Staff::where('id', '>', 0)->update(['business_type_id' => 1]);
echo "Updated {$staffCount} staff members to Business Type 1 (Bar).\n";

// Fix Users - Link to "Bar"
$users = User::all();
foreach ($users as $u) {
    DB::table('user_business_types')->updateOrInsert(
        ['user_id' => $u->id, 'business_type_id' => 1],
        ['created_at' => now(), 'updated_at' => now()]
    );
    // Also ensure user knows he is configured
    $u->update(['is_configured' => true]);
}
echo "Linked " . $users->count() . " users to Business Type 1 (Bar).\n";

echo "\nDone. Refresh your page now.\n";
