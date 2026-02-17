<?php
/**
 * Test Script for Order Creation
 * This script tests the order creation endpoint to see what format Laravel expects
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Bar\WaiterController;

echo "========================================\n";
echo "Order Creation Test Script\n";
echo "========================================\n\n";

// Test different data formats
$testCases = [
    'Test 1: Form Data Format (items[0][food_item_id])' => [
        'items' => [
            ['food_item_id' => 1, 'quantity' => 1, 'price' => 1000, 'product_name' => 'Test Food'],
            ['variant_id' => 1, 'quantity' => 2]
        ],
        'order_source' => 'web',
        'order_notes' => 'Test order'
    ],
    'Test 2: Direct Array Format' => [
        'items' => [
            ['food_item_id' => 1, 'quantity' => 1, 'price' => 1000, 'product_name' => 'Test Food'],
        ],
        'order_source' => 'web'
    ],
    'Test 3: JSON Format' => [
        'items' => [
            ['food_item_id' => 1, 'quantity' => 1, 'price' => 1000, 'product_name' => 'Test Food'],
        ],
        'order_source' => 'web'
    ]
];

foreach ($testCases as $testName => $testData) {
    echo "--- {$testName} ---\n";
    echo "Input data:\n";
    print_r($testData);
    
    // Simulate request
    $request = Request::create('/bar/waiter/create-order', 'POST', $testData);
    
    // Check what Laravel receives
    $items = $request->input('items');
    echo "\nLaravel receives items:\n";
    print_r($items);
    
    if ($items) {
        foreach ($items as $index => $item) {
            echo "Item {$index}:\n";
            echo "  - Has food_item_id: " . (isset($item['food_item_id']) ? 'YES (' . $item['food_item_id'] . ')' : 'NO') . "\n";
            echo "  - Has variant_id: " . (isset($item['variant_id']) ? 'YES (' . $item['variant_id'] . ')' : 'NO') . "\n";
            echo "  - Quantity: " . ($item['quantity'] ?? 'NOT SET') . "\n";
        }
    } else {
        echo "ERROR: Items array is empty or not received!\n";
    }
    
    echo "\n" . str_repeat("-", 60) . "\n\n";
}

echo "========================================\n";
echo "Testing with actual request simulation\n";
echo "========================================\n\n";

// Get a test staff member
$staff = \App\Models\Staff::where('is_active', true)->first();
if (!$staff) {
    echo "ERROR: No active staff found. Please create a staff member first.\n";
    exit(1);
}

echo "Using staff: {$staff->full_name} (ID: {$staff->id})\n\n";

// Get a food item
$foodItem = \App\Models\FoodItem::first();
if (!$foodItem) {
    echo "WARNING: No food items found. Creating a test one...\n";
    $foodItem = \App\Models\FoodItem::create([
        'user_id' => $staff->user_id,
        'name' => 'Test Food Item',
        'price' => 1000,
        'is_active' => true,
    ]);
    echo "Created test food item: {$foodItem->id}\n\n";
}

// Get a product variant
$variant = \App\Models\ProductVariant::first();
if (!$variant) {
    echo "WARNING: No product variants found.\n\n";
}

// Test data that should work
$testOrderData = [
    'items' => [
        [
            'food_item_id' => $foodItem->id,
            'quantity' => 1,
            'price' => $foodItem->price,
            'product_name' => $foodItem->name,
        ]
    ],
    'order_source' => 'web',
    'order_notes' => 'Test order from script'
];

if ($variant) {
    $testOrderData['items'][] = [
        'variant_id' => $variant->id,
        'quantity' => 1
    ];
}

echo "Test order data:\n";
print_r($testOrderData);

echo "\n========================================\n";
echo "What Laravel would receive:\n";
echo "========================================\n\n";

$request = Request::create('/bar/waiter/create-order', 'POST', $testOrderData);
$receivedItems = $request->input('items');

echo "Items received by Laravel:\n";
print_r($receivedItems);

if ($receivedItems) {
    foreach ($receivedItems as $index => $item) {
        echo "\nItem {$index} analysis:\n";
        echo "  Keys: " . implode(', ', array_keys($item)) . "\n";
        echo "  food_item_id: " . (isset($item['food_item_id']) ? $item['food_item_id'] : 'NOT SET') . "\n";
        echo "  variant_id: " . (isset($item['variant_id']) ? $item['variant_id'] : 'NOT SET') . "\n";
        echo "  quantity: " . ($item['quantity'] ?? 'NOT SET') . "\n";
    }
} else {
    echo "\nERROR: Items not received!\n";
}

echo "\n========================================\n";
echo "Test Complete\n";
echo "========================================\n";





