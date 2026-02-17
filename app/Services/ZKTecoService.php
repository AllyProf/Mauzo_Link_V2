<?php

namespace App\Services;

use CodingLibs\ZktecoPhp\Libs\ZKTeco;
use CodingLibs\ZktecoPhp\Libs\Services\Util;
use Illuminate\Support\Facades\Log;
use Exception;

class ZKTecoService
{
    protected ?ZKTeco $client = null;
    private string $ip;
    private int $port;
    private $password;

    public function __construct($ip = null, $port = null, $password = null)
    {
        $this->ip = $ip ?? config('zkteco.ip', env('ZKTECO_IP', '192.168.100.107'));
        $this->port = (int) ($port ?? config('zkteco.port', env('ZKTECO_PORT', 4370)));
        $configPassword = config('zkteco.password', env('ZKTECO_PASSWORD', 0));
        $this->password = $password ?? $configPassword;
        
        if ($this->password !== null && is_numeric($this->password)) {
            $this->password = (int)$this->password;
        } else {
            $this->password = 0;
        }
    }

    private function getClient(): ZKTeco
    {
        if ($this->client === null) {
            // Convert password to integer (library expects int, default is 0)
            $password = 0;
            if ($this->password !== null) {
                if (is_numeric($this->password)) {
                    $password = (int)$this->password;
                } else {
                    Log::warning("ZKTeco password is not numeric: {$this->password}. Using 0 (default).");
                    $password = 0;
                }
            }
            
            Log::info("Creating ZKTeco client: IP={$this->ip}, Port={$this->port}, Password={$password} (Comm Key)");
            
            $this->client = new ZKTeco(
                ip: $this->ip,
                port: $this->port,
                shouldPing: true,
                timeout: 30, // Increased timeout for better reliability
                password: $password
            );
        }
        return $this->client;
    }

    public function connect(): bool
    {
        try {
            Log::info("Attempting to connect to ZKTeco device at {$this->ip}:{$this->port}");
            
            $client = $this->getClient();
            
            // Try to connect with timeout awareness
            $startTime = microtime(true);
            $result = $client->connect();
            $connectionTime = microtime(true) - $startTime;
            
            if ($result) {
                Log::info("Successfully connected to ZKTeco device at {$this->ip}:{$this->port} (took " . round($connectionTime, 2) . " seconds)");
                
                // CRITICAL: Verify authentication by testing a command that requires auth
                // If Comm Key is wrong, connection might succeed but commands will fail
                try {
                    // Try to get device name - this requires authentication
                    $deviceName = $client->deviceName();
                    Log::info("Authentication verified - Device name: {$deviceName}");
                } catch (\Throwable $e) {
                    Log::error("Authentication check failed: " . $e->getMessage());
                    Log::error("This usually means Comm Key is wrong. Current Comm Key: {$this->password}");
                    Log::error("Please check device settings (System → Communication → Comm Key) and update Comm Key in the form");
                    // Don't throw here - let the actual command fail with better error
                }
            } else {
                Log::error("Failed to connect to ZKTeco device at {$this->ip}:{$this->port}");
                Log::error("Connection attempt took " . round($connectionTime, 2) . " seconds before failing");
                Log::error("Possible causes:");
                Log::error("1. Device is not powered on or not on the network");
                Log::error("2. IP address is incorrect (current: {$this->ip})");
                Log::error("3. Port is incorrect (current: {$this->port})");
                Log::error("4. Firewall is blocking the connection");
                Log::error("5. Device is busy with another operation");
            }
            
            return $result;
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::error('ZKTeco connect error: ' . $errorMessage);
            Log::error('Connection attempt to: ' . $this->ip . ':' . $this->port);
            Log::error('Error class: ' . get_class($e));
            
            // Provide more helpful error message
            if (strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'timed out') !== false) {
                throw new Exception("Connection timeout: Device at {$this->ip}:{$this->port} did not respond. Check if device is powered on and network is working.");
            } elseif (strpos($errorMessage, 'refused') !== false || strpos($errorMessage, 'No route') !== false) {
                throw new Exception("Cannot reach device at {$this->ip}:{$this->port}. Check IP address and network connectivity.");
            } else {
                throw new Exception("Connection failed: {$errorMessage}. Check device IP ({$this->ip}), port ({$this->port}), and network connectivity.");
            }
        }
    }

    public function disconnect(): void
    {
        try {
            if ($this->client) {
                $this->client->disconnect();
            }
        } catch (\Throwable $e) {
            Log::error('ZKTeco disconnect error: ' . $e->getMessage());
        }
    }

    public function testConnection(): array
    {
        try {
            $connected = $this->connect();
            
            if (!$connected) {
                return [
                    'success' => false,
                    'message' => 'Failed to connect to device',
                    'troubleshooting' => [
                        '1. Device is not powered on or not on the network',
                        '2. IP address is incorrect (current: ' . $this->ip . ')',
                        '3. Port is incorrect (current: ' . $this->port . ')',
                        '4. Firewall is blocking the connection',
                        '5. Device is busy with another operation',
                        '6. Try pinging the device: ping ' . $this->ip
                    ]
                ];
            }

            $deviceInfo = null;
            $time = null;
            $errors = [];

            // Try to get device info
            try {
                $client = $this->getClient();
                $deviceInfo = [
                    'name' => $client->deviceName(),
                    'serial' => $client->serialNumber(),
                    'version' => $client->version(),
                ];
            } catch (\Exception $e) {
                $errors[] = 'Device Info: ' . $e->getMessage();
            }

            // Try to get time
            try {
                $client = $this->getClient();
                $time = $client->getTime();
            } catch (\Exception $e) {
                $errors[] = 'Get Time: ' . $e->getMessage();
            }

            // Try to get users count
            try {
                $client = $this->getClient();
                $users = $client->getUsers();
                if ($deviceInfo) {
                    $deviceInfo['users_count'] = count($users);
                }
            } catch (\Exception $e) {
                $errors[] = 'Get Users: ' . $e->getMessage();
            }

            $this->disconnect();

            if ($deviceInfo || $time) {
                return [
                    'success' => true,
                    'device_info' => $deviceInfo,
                    'device_time' => $time,
                    'message' => 'Device connection successful!',
                    'warnings' => $errors
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connected but failed to retrieve data. ' . implode(' | ', $errors),
                    'troubleshooting' => [
                        'This usually means Comm Key is incorrect',
                        'Check device settings: System → Communication → Comm Key',
                        'Default Comm Key is usually 0',
                        'Update Comm Key in the form if device uses a different value'
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->disconnect();
            $message = $e->getMessage();
            
            $troubleshooting = [];
            if (strpos($message, 'timeout') !== false) {
                $troubleshooting = [
                    '1. Device may be powered off',
                    '2. Network connectivity issue',
                    '3. Firewall blocking connection',
                    '4. Wrong IP address',
                    '5. Device is not responding on port ' . $this->port
                ];
            } elseif (strpos($message, 'refused') !== false || strpos($message, 'No route') !== false) {
                $troubleshooting = [
                    '1. Check IP address is correct: ' . $this->ip,
                    '2. Verify device is on the same network',
                    '3. Check network cables',
                    '4. Try pinging the device: ping ' . $this->ip,
                    '5. Verify device network settings'
                ];
            } else {
                $troubleshooting = [
                    '1. Verify device IP: ' . $this->ip,
                    '2. Verify port: ' . $this->port,
                    '3. Check Comm Key (default is 0)',
                    '4. Ensure device firmware supports TCP/IP communication',
                    '5. Check device network settings',
                    '6. Try accessing device web interface (if available)'
                ];
            }
            
            return [
                'success' => false,
                'message' => $message,
                'troubleshooting' => $troubleshooting
            ];
        }
    }

    public function registerUser($enrollId, $name, $password = '', $role = 0, $cardno = 0): bool
    {
        try {
            if (!$this->connect()) {
                throw new Exception('Failed to connect to device');
            }
            
            $client = $this->getClient();
            
            // Verify authentication by testing if we can read from device
            try {
                $testUsers = $client->getUsers();
                Log::info("Authentication verified - Can get users (found " . count($testUsers) . ")");
            } catch (\Throwable $e) {
                Log::error("Authentication check failed: " . $e->getMessage());
                throw new Exception("Authentication failed. Check Comm Key. Error: " . $e->getMessage());
            }
            
            // Enable device (many devices require this)
            try {
                $client->enableDevice();
                usleep(500000); // 500ms delay
                Log::info("Device enabled");
            } catch (\Throwable $e) {
                Log::warning("Could not enable device: " . $e->getMessage());
                // Continue anyway
            }
            
            // Ensure enrollId is valid
            $uid = (int)$enrollId;
            $userid = (string)$enrollId;
            
            if ($uid < 1 || $uid > 65535) {
                throw new Exception('Enroll ID must be between 1 and 65535');
            }
            
            // Truncate name to 24 characters (device limit)
            $name = substr(trim($name), 0, 24);
            
            // Check if user already exists on device
            $existingUsers = $client->getUsers();
            foreach ($existingUsers as $key => $deviceUser) {
                if ((string)$key === $userid || 
                    (isset($deviceUser['uid']) && (int)$deviceUser['uid'] === $uid)) {
                    throw new Exception("User with Enroll ID '{$enrollId}' already exists on device. Please use a different Enroll ID or remove the existing user first.");
                }
            }
            
            // Get user count before registration
            $userCountBefore = count($existingUsers);
            Log::info("Pre-registration: Device has {$userCountBefore} users");
            
            // Call setUser command
            // Use Util::LEVEL_USER (0) as default role if not specified
            $userRole = $role !== null ? (int)$role : Util::LEVEL_USER;
            Log::info("Registering user: UID={$uid}, UserID='{$userid}', Name='{$name}', Role={$userRole}");
            $result = $client->setUser($uid, $userid, $name, $password, $userRole, $cardno);
            
            // Wait for device to process
            usleep(1000000); // 1 second
            
            // Verify user was actually added
            $usersAfter = $client->getUsers();
            $userCountAfter = count($usersAfter);
            $userFound = false;
            
            foreach ($usersAfter as $key => $deviceUser) {
                if ((string)$key === $userid || 
                    (isset($deviceUser['uid']) && (int)$deviceUser['uid'] === $uid)) {
                    $userFound = true;
                    Log::info("✓ User verified on device: UID={$uid}, UserID='{$userid}'");
                    break;
                }
            }
            
            $this->disconnect();
            
            if ($userFound) {
                Log::info("User registered successfully: Enroll ID {$enrollId}, Name: {$name}");
                return true;
            } elseif ($result !== false) {
                // setUser didn't return false, but user not found - might be a timing issue
                Log::warning("setUser returned non-false but user not immediately found. This might be a timing issue.");
                throw new Exception("User registration may have failed. User not found on device after registration. Please try again or check device logs.");
            } else {
                Log::error("setUser returned false - registration failed");
                throw new Exception("Failed to register user on device. Device rejected the command. Possible reasons: Invalid data, device memory full, or Comm Key incorrect.");
            }
        } catch (\Exception $e) {
            Log::error("Register user error: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function deleteUser($enrollId): bool
    {
        try {
            if (!$this->connect()) {
                throw new Exception('Failed to connect to device');
            }
            
            $client = $this->getClient();
            // SDK method is removeUser, not deleteUser
            $result = $client->removeUser((int)$enrollId);
            $this->disconnect();
            
            if ($result) {
                Log::info("User deleted successfully: Enroll ID {$enrollId}");
            } else {
                Log::warning("User deletion may have failed: Enroll ID {$enrollId}");
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Delete user error: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function getUsers(): array
    {
        try {
            if (!$this->connect()) {
                throw new Exception('Failed to connect to device');
            }
            
            $client = $this->getClient();
            $users = $client->getUsers();
            $this->disconnect();
            
            return $users;
        } catch (\Exception $e) {
            Log::error("Get users error: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function getAttendances($startDate = null, $endDate = null): array
    {
        try {
            if (!$this->connect()) {
                throw new Exception('Failed to connect to device');
            }
            
            $client = $this->getClient();
            $attendances = $client->getAttendances();
            $this->disconnect();
            
            // Filter by date if provided
            if ($startDate || $endDate) {
                $attendances = array_filter($attendances, function($att) use ($startDate, $endDate) {
                    // SDK returns 'record_time' field
                    $timestamp = isset($att['record_time']) ? $att['record_time'] : (isset($att['timestamp']) ? $att['timestamp'] : null);
                    if (!$timestamp) return false;
                    
                    $attDate = date('Y-m-d', strtotime($timestamp));
                    if ($startDate && $attDate < $startDate) return false;
                    if ($endDate && $attDate > $endDate) return false;
                    return true;
                });
            }
            
            return array_values($attendances);
        } catch (\Exception $e) {
            Log::error("Get attendances error: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function clearAttendance(): bool
    {
        try {
            if (!$this->connect()) {
                throw new Exception('Failed to connect to device');
            }
            
            $client = $this->getClient();
            $result = $client->clearAttendance();
            $this->disconnect();
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Clear attendance error: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }

    public function getDeviceInfo(): array
    {
        try {
            if (!$this->connect()) {
                throw new Exception('Failed to connect to device');
            }
            
            $client = $this->getClient();
            $info = [
                'name' => $client->deviceName(),
                'serial' => $client->serialNumber(),
                'version' => $client->version(),
                'time' => $client->getTime(),
            ];
            $this->disconnect();
            
            return $info;
        } catch (\Exception $e) {
            Log::error("Get device info error: " . $e->getMessage());
            $this->disconnect();
            throw $e;
        }
    }
}

