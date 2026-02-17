<?php

/**
 * ZKTeco Device Connection Test Script
 * Run this from command line: php test_zkteco_connection.php
 */

require __DIR__ . '/vendor/autoload.php';

use CodingLibs\ZktecoPhp\Libs\ZKTeco;

// Configuration
$deviceIp = '192.168.100.118';  // Change this to your device IP
$devicePort = 4370;              // Default ZKTeco port
$commKey = 0;                     // Default Comm Key (change if your device uses different)

echo "========================================\n";
echo "ZKTeco Device Connection Test\n";
echo "========================================\n";
echo "Device IP: {$deviceIp}\n";
echo "Port: {$devicePort}\n";
echo "Comm Key: {$commKey}\n";
echo "========================================\n\n";

// Test 1: Network Connectivity
echo "Test 1: Network Connectivity Test...\n";
$socket = @fsockopen($deviceIp, $devicePort, $errno, $errstr, 5);
if ($socket) {
    echo "✓ Network connectivity: OK (can reach {$deviceIp}:{$devicePort})\n";
    fclose($socket);
} else {
    echo "✗ Network connectivity: FAILED\n";
    echo "  Error Code: {$errno}\n";
    echo "  Error Message: " . ($errstr ?: 'Connection timeout or refused') . "\n";
    echo "\n";
    echo "Troubleshooting:\n";
    echo "  1. Verify device IP address is correct\n";
    echo "  2. Check if device is powered on\n";
    echo "  3. Ensure device is on the same network\n";
    echo "  4. Check firewall settings\n";
    echo "  5. Try pinging: ping {$deviceIp}\n";
    exit(1);
}
echo "\n";

// Test 2: ZKTeco SDK Connection
echo "Test 2: ZKTeco SDK Connection Test...\n";
try {
    $client = new ZKTeco(
        ip: $deviceIp,
        port: $devicePort,
        shouldPing: true,
        timeout: 30,
        password: $commKey
    );
    
    echo "Attempting to connect...\n";
    $startTime = microtime(true);
    $connected = $client->connect();
    $connectionTime = microtime(true) - $startTime;
    
    if ($connected) {
        echo "✓ Connection successful! (took " . round($connectionTime, 2) . " seconds)\n\n";
        
        // Test 3: Get Device Info
        echo "Test 3: Device Information...\n";
        try {
            $deviceName = $client->deviceName();
            echo "  Device Name: {$deviceName}\n";
        } catch (\Exception $e) {
            echo "  ✗ Cannot get device name: " . $e->getMessage() . "\n";
            echo "    This may indicate Comm Key is incorrect!\n";
        }
        
        try {
            $serial = $client->serialNumber();
            echo "  Serial Number: {$serial}\n";
        } catch (\Exception $e) {
            echo "  ✗ Cannot get serial number: " . $e->getMessage() . "\n";
        }
        
        try {
            $version = $client->version();
            echo "  Firmware Version: {$version}\n";
        } catch (\Exception $e) {
            echo "  ✗ Cannot get version: " . $e->getMessage() . "\n";
        }
        
        try {
            $time = $client->getTime();
            echo "  Device Time: {$time}\n";
        } catch (\Exception $e) {
            echo "  ✗ Cannot get device time: " . $e->getMessage() . "\n";
        }
        
        try {
            $users = $client->getUsers();
            echo "  Users on Device: " . count($users) . "\n";
        } catch (\Exception $e) {
            echo "  ✗ Cannot get users: " . $e->getMessage() . "\n";
        }
        
        $client->disconnect();
        echo "\n✓ All tests passed!\n";
        
    } else {
        echo "✗ Connection failed\n";
        echo "  Connection attempt took " . round($connectionTime, 2) . " seconds\n";
        echo "\nPossible causes:\n";
        echo "  1. Device is not powered on\n";
        echo "  2. IP address is incorrect\n";
        echo "  3. Port is incorrect\n";
        echo "  4. Comm Key is incorrect\n";
        echo "  5. Firewall is blocking\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "✗ Connection error: " . $e->getMessage() . "\n";
    echo "\nError details:\n";
    echo "  " . get_class($e) . "\n";
    exit(1);
}

echo "\n========================================\n";
echo "Test Complete!\n";
echo "========================================\n";

