<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Staff;
use App\Models\BusinessType;
use App\Models\Role;

echo "--- STAFF DETAILS ---\n";
$staff = Staff::with('role', 'businessType')->get();
foreach ($staff as $s) {
    echo "ID: {$s->id} | Name: {$s->full_name} | Role: " . ($s->role->name ?? 'N/A') . " | BT ID: " . ($s->business_type_id ?? 'N/A') . " (" . ($s->businessType->name ?? 'N/A') . ")\n";
}

echo "\n--- BUSINESS TYPES ---\n";
foreach (BusinessType::all() as $bt) {
    echo "ID: {$bt->id} | Name: {$bt->name} | Slug: {$bt->slug}\n";
}
