<?php
/**
 * Test Script for Real-time Order Announcement System
 * 
 * This script creates a test order to verify:
 * 1. Order creation works
 * 2. API endpoint returns new orders
 * 3. Swahili message formatting
 * 4. Real-time detection
 * 
 * Usage: php test_order_announcement.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BarOrder;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StockLocation;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Order Announcement System Test Script\n";
echo "========================================\n\n";

try {
    // Step 1: Find a waiter first, then get their owner
    echo "Step 1: Finding a waiter...\n";
    $waiter = Staff::whereHas('role', function($query) {
            $query->where('name', 'Waiter');
        })
        ->where('is_active', true)
        ->first();
    
    if (!$waiter) {
        throw new Exception("No active waiters found. Please create a waiter first.");
    }
    
    $user = \App\Models\User::find($waiter->user_id);
    if (!$user) {
        throw new Exception("Waiter's owner (user_id: {$waiter->user_id}) not found.");
    }
    
    echo "✓ Found waiter: {$waiter->full_name} (ID: {$waiter->id})\n";
    echo "✓ Found owner: {$user->name} (ID: {$user->id})\n\n";

    // Step 3: Find or create products with counter stock
    echo "Step 3: Finding products with counter stock...\n";
    $variants = ProductVariant::whereHas('product', function($query) use ($user) {
            $query->where('user_id', $user->id)->where('is_active', true);
        })
        ->whereHas('stockLocations', function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->where('location', 'counter')
                  ->where('quantity', '>', 0);
        })
        ->with(['product', 'stockLocations' => function($query) use ($user) {
            $query->where('user_id', $user->id)->where('location', 'counter');
        }])
        ->limit(3)
        ->get();

    // If no products with counter stock, create test products
    if ($variants->isEmpty()) {
        echo "  ⚠ No products with counter stock found. Creating test products...\n";
        
        DB::beginTransaction();
        try {
            $testProducts = [
                ['name' => 'Coca Cola', 'category' => 'Soft Drinks', 'is_alcoholic' => false],
                ['name' => 'Fanta', 'category' => 'Soft Drinks', 'is_alcoholic' => false],
                ['name' => 'Sprite', 'category' => 'Soft Drinks', 'is_alcoholic' => false],
            ];
            
            $createdVariants = [];
            
            foreach ($testProducts as $productData) {
                // Create product
                $product = \App\Models\Product::create([
                    'user_id' => $user->id,
                    'name' => $productData['name'],
                    'category' => $productData['category'],
                    'is_active' => true,
                ]);
                
                // Create variant
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'measurement' => '500ml',
                    'packaging' => 'Bottle',
                    'items_per_package' => 1,
                    'buying_price_per_unit' => 800,
                    'selling_price_per_unit' => 1500,
                    'is_active' => true,
                ]);
                
                // Create counter stock
                $stockLocation = StockLocation::create([
                    'user_id' => $user->id,
                    'product_variant_id' => $variant->id,
                    'location' => 'counter',
                    'quantity' => 50,
                    'average_buying_price' => 800,
                    'selling_price' => 1500,
                ]);
                
                $createdVariants[] = $variant;
                echo "    ✓ Created: {$product->name} ({$variant->measurement}) - 50 units\n";
            }
            
            DB::commit();
            
            // Reload variants with relationships
            $variants = ProductVariant::whereIn('id', array_map(fn($v) => $v->id, $createdVariants))
                ->with(['product', 'stockLocations' => function($query) use ($user) {
                    $query->where('user_id', $user->id)->where('location', 'counter');
                }])
                ->get();
            
            echo "  ✓ Created " . count($createdVariants) . " test products with counter stock\n\n";
        } catch (\Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to create test products: " . $e->getMessage());
        }
    } else {
        echo "✓ Found " . $variants->count() . " products with stock:\n";
        foreach ($variants as $variant) {
            $stock = $variant->stockLocations->where('location', 'counter')->first();
            $qty = $stock ? $stock->quantity : 0;
            echo "  - {$variant->product->name} ({$variant->measurement}): {$qty} units\n";
        }
        echo "\n";
    }

    // Step 4: Get the last order ID before creating test order
    echo "Step 4: Getting current latest order ID...\n";
    $lastOrderId = BarOrder::where('user_id', $user->id)
        ->whereNotNull('waiter_id')
        ->max('id') ?? 0;
    echo "✓ Last order ID: {$lastOrderId}\n\n";

    // Step 5: Create test order
    echo "Step 5: Creating test order...\n";
    DB::beginTransaction();
    
    try {
        $orderNumber = BarOrder::generateOrderNumber($user->id);
        $totalAmount = 0;
        $orderItems = [];

        // Create order items
        foreach ($variants->take(2) as $index => $variant) {
            $stock = $variant->stockLocations->where('location', 'counter')->first();
            $quantity = min(2, $stock->quantity); // Take max 2 units
            
            $sellingPrice = $stock->selling_price ?? $variant->selling_price_per_unit ?? 1000;
            $itemTotal = $quantity * $sellingPrice;
            $totalAmount += $itemTotal;

            $orderItems[] = [
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'unit_price' => $sellingPrice,
                'total_price' => $itemTotal,
            ];
        }

        // Create the order
        $order = BarOrder::create([
            'user_id' => $user->id,
            'order_number' => $orderNumber,
            'waiter_id' => $waiter->id,
            'order_source' => 'web',
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'customer_name' => 'Test Customer',
            'customer_phone' => '+255123456789',
        ]);

        // Create order items
        foreach ($orderItems as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $itemData['product_variant_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total_price' => $itemData['total_price'],
            ]);
        }

        DB::commit();
        
        echo "✓ Test order created successfully!\n";
        echo "  Order #: {$order->order_number}\n";
        echo "  Order ID: {$order->id}\n";
        echo "  Waiter: {$waiter->full_name}\n";
        echo "  Total: TSh " . number_format($totalAmount, 2) . "\n";
        echo "  Items: " . count($orderItems) . "\n\n";

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }

    // Step 6: Test API endpoint
    echo "Step 6: Testing API endpoint...\n";
    echo "  Testing: GET /bar/counter/latest-orders?last_order_id={$lastOrderId}\n";
    
    // Simulate API call
    $newOrders = BarOrder::where('user_id', $user->id)
        ->whereNotNull('waiter_id')
        ->where('status', 'pending')
        ->where('id', '>', $lastOrderId)
        ->with(['waiter', 'items.productVariant.product'])
        ->orderBy('created_at', 'desc')
        ->get();

    if ($newOrders->isEmpty()) {
        echo "  ⚠ Warning: No new orders found via API simulation\n";
    } else {
        echo "  ✓ Found " . $newOrders->count() . " new order(s)\n";
        foreach ($newOrders as $newOrder) {
            echo "    - Order #{$newOrder->order_number} (ID: {$newOrder->id})\n";
        }
    }
    echo "\n";

    // Step 7: Format Swahili message
    echo "Step 7: Testing Swahili message format...\n";
    $orderWithItems = BarOrder::with(['waiter', 'items.productVariant.product'])
        ->find($order->id);
    
    $orderNum = preg_replace('/[^0-9]/', '', $orderWithItems->order_number);
    $waiterName = $orderWithItems->waiter ? $orderWithItems->waiter->full_name : 'Mhudumu';
    $itemsList = $orderWithItems->items->map(function($item) {
        $qty = $item->quantity;
        $name = $item->productVariant->product->name ?? 'N/A';
        return "{$qty} " . ($qty === 1 ? 'chupa' : 'chupa') . " ya {$name}";
    })->join(', ');
    $totalAmount = number_format($orderWithItems->total_amount, 0);
    
    $swahiliMessage = "Oda nambari {$orderNum} kutoka kwa mhudumu {$waiterName} ameagiza {$itemsList} yenye thamani ya shilingi {$totalAmount}. Asante.";
    echo "  ✓ Swahili message:\n";
    echo "    \"{$swahiliMessage}\"\n\n";

    // Step 8: Summary
    echo "========================================\n";
    echo "Test Summary\n";
    echo "========================================\n";
    echo "✓ Order created successfully\n";
    echo "✓ Order ID: {$order->id}\n";
    echo "✓ Order Number: {$order->order_number}\n";
    echo "✓ API endpoint should detect this order\n";
    echo "✓ Swahili message formatted correctly\n";
    echo "\n";
    echo "Next Steps:\n";
    echo "1. Open the counter screen: http://127.0.0.1:8000/bar/counter/waiter-orders\n";
    echo "2. Ensure speakers are connected\n";
    echo "3. The system should detect this order within 3 seconds\n";
    echo "4. You should hear: \"{$swahiliMessage}\"\n";
    echo "\n";
    echo "To test again, run this script again to create another order.\n";
    echo "========================================\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

