<?php
/**
 * Food Items API Test Script
 * 
 * This script tests the food items API endpoint
 * Usage: php test_food_items_api.php
 */

// Configuration
$baseUrl = 'http://10.143.103.160:8000'; // Change to your server URL
$email = 'waiter@mauzo.com';
$password = 'NANCY'; // Change to actual password

// Colors for terminal output
$colors = [
    'reset' => "\033[0m",
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
];

function printColor($message, $color = 'reset') {
    global $colors;
    echo $colors[$color] . $message . $colors['reset'] . "\n";
}

function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => $error,
            'http_code' => 0,
            'data' => null
        ];
    }
    
    $decoded = json_decode($response, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'data' => $decoded,
        'raw_response' => $response
    ];
}

printColor("=" . str_repeat("=", 60) . "=", 'cyan');
printColor("Food Items API Test Script", 'cyan');
printColor("=" . str_repeat("=", 60) . "=", 'cyan');
echo "\n";

// Step 1: Login
printColor("\n[Step 1] Logging in...", 'blue');
$loginUrl = $baseUrl . '/api/waiter/login';
$loginData = [
    'email' => $email,
    'password' => $password
];

$loginResult = makeRequest($loginUrl, 'POST', $loginData);

if (!$loginResult['success']) {
    printColor("❌ Login failed!", 'red');
    printColor("HTTP Code: " . $loginResult['http_code'], 'red');
    if ($loginResult['data']) {
        printColor("Error: " . json_encode($loginResult['data'], JSON_PRETTY_PRINT), 'red');
    } else {
        printColor("Raw Response: " . $loginResult['raw_response'], 'red');
    }
    exit(1);
}

if (!isset($loginResult['data']['token'])) {
    printColor("❌ Login response missing token!", 'red');
    printColor("Response: " . json_encode($loginResult['data'], JSON_PRETTY_PRINT), 'red');
    exit(1);
}

$token = $loginResult['data']['token'];
printColor("✅ Login successful!", 'green');
printColor("Token: " . substr($token, 0, 20) . "...", 'green');
printColor("Waiter: " . ($loginResult['data']['waiter']['name'] ?? 'N/A'), 'green');
echo "\n";

// Step 2: Get Food Items
printColor("[Step 2] Fetching food items...", 'blue');
$foodItemsUrl = $baseUrl . '/api/waiter/food-items';
$foodItemsResult = makeRequest($foodItemsUrl, 'GET', null, $token);

if (!$foodItemsResult['success']) {
    printColor("❌ Failed to fetch food items!", 'red');
    printColor("HTTP Code: " . $foodItemsResult['http_code'], 'red');
    if ($foodItemsResult['data']) {
        printColor("Error: " . json_encode($foodItemsResult['data'], JSON_PRETTY_PRINT), 'red');
    } else {
        printColor("Raw Response: " . $foodItemsResult['raw_response'], 'red');
    }
    exit(1);
}

if (!isset($foodItemsResult['data']['success']) || !$foodItemsResult['data']['success']) {
    printColor("❌ API returned unsuccessful response!", 'red');
    printColor("Response: " . json_encode($foodItemsResult['data'], JSON_PRETTY_PRINT), 'red');
    exit(1);
}

printColor("✅ Food items fetched successfully!", 'green');
echo "\n";

// Step 3: Analyze Response
printColor("[Step 3] Analyzing food items...", 'blue');
$foodItems = $foodItemsResult['data']['food_items'] ?? [];

if (empty($foodItems)) {
    printColor("⚠️  No food items found!", 'yellow');
    printColor("This might be normal if no food items are configured.", 'yellow');
} else {
    printColor("Found " . count($foodItems) . " food item(s)", 'green');
    echo "\n";
    
    // Detailed analysis
    $issues = [];
    $itemNumber = 1;
    
    foreach ($foodItems as $item) {
        printColor("--- Food Item #{$itemNumber} ---", 'cyan');
        printColor("ID: " . ($item['id'] ?? 'N/A'), 'reset');
        printColor("Name: " . ($item['name'] ?? 'N/A'), 'reset');
        printColor("Description: " . ($item['description'] ?? 'N/A'), 'reset');
        printColor("Image: " . ($item['image'] ?? 'null'), 'reset');
        
        // Check variants
        if (!isset($item['variants']) || !is_array($item['variants'])) {
            $issues[] = "Item #{$itemNumber} ({$item['name']}): Missing or invalid variants array";
            printColor("⚠️  Variants: Missing or invalid!", 'yellow');
        } else {
            $variantCount = count($item['variants']);
            printColor("Variants: {$variantCount}", 'reset');
            
            if ($variantCount === 0) {
                $issues[] = "Item #{$itemNumber} ({$item['name']}): No variants available";
                printColor("⚠️  No variants available!", 'yellow');
            } else {
                $variantNumber = 1;
                foreach ($item['variants'] as $variant) {
                    printColor("  Variant #{$variantNumber}:", 'reset');
                    printColor("    Name: " . ($variant['name'] ?? 'N/A'), 'reset');
                    
                    // Check price
                    if (!isset($variant['price'])) {
                        $issues[] = "Item #{$itemNumber} ({$item['name']}), Variant #{$variantNumber}: Missing price";
                        printColor("    ⚠️  Price: MISSING!", 'yellow');
                    } else {
                        $price = $variant['price'];
                        $priceType = gettype($price);
                        
                        // Check if price is a number
                        if (!is_numeric($price)) {
                            $issues[] = "Item #{$itemNumber} ({$item['name']}), Variant #{$variantNumber}: Price is not numeric (type: {$priceType}, value: " . var_export($price, true) . ")";
                            printColor("    ⚠️  Price: NOT NUMERIC! (Type: {$priceType}, Value: " . var_export($price, true) . ")", 'yellow');
                        } else {
                            // Check if price is a string number
                            if (is_string($price)) {
                                $issues[] = "Item #{$itemNumber} ({$item['name']}), Variant #{$variantNumber}: Price is a string (should be number)";
                                printColor("    ⚠️  Price: STRING NUMBER (should be number) - Value: {$price}", 'yellow');
                            } else {
                                printColor("    ✅ Price: {$price} (Type: {$priceType})", 'green');
                            }
                            
                            // Check if price is valid (greater than 0)
                            if ($price <= 0) {
                                $issues[] = "Item #{$itemNumber} ({$item['name']}), Variant #{$variantNumber}: Price is zero or negative ({$price})";
                                printColor("    ⚠️  Price: ZERO OR NEGATIVE!", 'yellow');
                            }
                        }
                    }
                    
                    $variantNumber++;
                }
            }
        }
        
        echo "\n";
        $itemNumber++;
    }
    
    // Summary
    echo "\n";
    printColor("=" . str_repeat("=", 60) . "=", 'cyan');
    printColor("Summary", 'cyan');
    printColor("=" . str_repeat("=", 60) . "=", 'cyan');
    
    if (empty($issues)) {
        printColor("✅ All food items are valid!", 'green');
        printColor("✅ All prices are properly formatted!", 'green');
    } else {
        printColor("⚠️  Found " . count($issues) . " issue(s):", 'yellow');
        foreach ($issues as $issue) {
            printColor("  - {$issue}", 'yellow');
        }
    }
    
    // JSON Response Preview
    echo "\n";
    printColor("=" . str_repeat("=", 60) . "=", 'cyan');
    printColor("Full JSON Response", 'cyan');
    printColor("=" . str_repeat("=", 60) . "=", 'cyan');
    printColor(json_encode($foodItemsResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 'reset');
}

// Step 4: Test Price Formatting
echo "\n";
printColor("[Step 4] Testing price formatting...", 'blue');

if (!empty($foodItems)) {
    $priceIssues = [];
    foreach ($foodItems as $item) {
        if (isset($item['variants']) && is_array($item['variants'])) {
            foreach ($item['variants'] as $variant) {
                if (isset($variant['price'])) {
                    $price = $variant['price'];
                    $rawPrice = $price;
                    $finalPrice = is_numeric($price) ? (float)$price : null;
                    
                    if ($finalPrice === null) {
                        $priceIssues[] = [
                            'item' => $item['name'] ?? 'Unknown',
                            'variant' => $variant['name'] ?? 'Unknown',
                            'rawPrice' => var_export($rawPrice, true),
                            'issue' => 'Not numeric'
                        ];
                    } elseif (is_string($price)) {
                        $priceIssues[] = [
                            'item' => $item['name'] ?? 'Unknown',
                            'variant' => $variant['name'] ?? 'Unknown',
                            'rawPrice' => $rawPrice,
                            'finalPrice' => $finalPrice,
                            'issue' => 'String number (should be number)'
                        ];
                    }
                }
            }
        }
    }
    
    if (empty($priceIssues)) {
        printColor("✅ All prices are properly formatted as numbers!", 'green');
    } else {
        printColor("⚠️  Price formatting issues found:", 'yellow');
        foreach ($priceIssues as $issue) {
            printColor("  Item: {$issue['item']}, Variant: {$issue['variant']}", 'yellow');
            printColor("    Raw Price: {$issue['rawPrice']}", 'yellow');
            if (isset($issue['finalPrice'])) {
                printColor("    Final Price: {$issue['finalPrice']}", 'yellow');
            }
            printColor("    Issue: {$issue['issue']}", 'yellow');
            echo "\n";
        }
    }
}

echo "\n";
printColor("=" . str_repeat("=", 60) . "=", 'cyan');
printColor("Test Complete!", 'cyan');
printColor("=" . str_repeat("=", 60) . "=", 'cyan');
echo "\n";

// Database Query Suggestions
printColor("💡 Database Check Suggestions:", 'blue');
printColor("Run this SQL query to check for price issues:", 'reset');
printColor("  SELECT id, name, variants FROM food_items WHERE variants IS NULL OR variants = '[]' OR JSON_LENGTH(variants) = 0;", 'reset');
echo "\n";
printColor("Check for variants with missing or zero prices:", 'reset');
printColor("  SELECT id, name, variants FROM food_items;", 'reset');
printColor("  (Then manually check the JSON variants array for price fields)", 'reset');
echo "\n";




