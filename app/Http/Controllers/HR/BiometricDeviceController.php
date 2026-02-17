<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\Staff;
use App\Models\BiometricDeviceMapping;
use App\Models\Attendance;
use App\Services\ZKTecoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BiometricDeviceController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Display biometric device management page
     */
    public function index()
    {
        if (!$this->hasPermission('hr', 'view')) {
            abort(403, 'You do not have permission to access biometric devices.');
        }

        $ownerId = $this->getOwnerId();
        
        // Get all staff
        $staff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->with('biometricMapping')
            ->orderBy('full_name')
            ->get();

        // Get device configuration
        $deviceIp = config('zkteco.ip');
        $devicePort = config('zkteco.port');
        
        // Count registered staff
        $registeredCount = BiometricDeviceMapping::where('user_id', $ownerId)
            ->where('is_registered', true)
            ->count();

        return view('hr.biometric-devices.index', compact('staff', 'deviceIp', 'devicePort', 'registeredCount'));
    }

    /**
     * Test device connection
     */
    public function testConnection(Request $request)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to test device connection.'], 403);
        }

        $request->validate([
            'ip' => 'required|ip',
            'port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|integer',
        ]);

        $ip = $request->ip;
        $port = $request->port;
        $password = $request->password ?? 0;
        
        // First, test basic network connectivity
        $networkTest = $this->testNetworkConnectivity($ip, $port);
        
        try {
            $service = new ZKTecoService($ip, $port, $password);
            $result = $service->testConnection();
            
            // Add network test results
            if (isset($result['troubleshooting'])) {
                $result['troubleshooting'] = array_merge($networkTest['troubleshooting'] ?? [], $result['troubleshooting']);
            } else {
                $result['troubleshooting'] = $networkTest['troubleshooting'] ?? [];
            }
            
            $result['network_test'] = $networkTest;
            
            return response()->json($result);
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'message' => $e->getMessage(),
                'troubleshooting' => $networkTest['troubleshooting'] ?? []
            ];
            
            return response()->json($result, 500);
        }
    }
    
    /**
     * Test basic network connectivity to device
     */
    private function testNetworkConnectivity($ip, $port)
    {
        $troubleshooting = [];
        $canReach = false;
        
        // Test if we can reach the IP (ping equivalent - socket connection test)
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        
        if ($socket) {
            fclose($socket);
            $canReach = true;
            $troubleshooting[] = '✓ Network connectivity: OK (can reach ' . $ip . ':' . $port . ')';
        } else {
            $troubleshooting[] = '✗ Network connectivity: FAILED (cannot reach ' . $ip . ':' . $port . ')';
            $troubleshooting[] = 'Error: ' . ($errstr ?: 'Connection timeout or refused');
            
            // Additional troubleshooting based on error
            if ($errno == 110 || $errstr && strpos($errstr, 'timeout') !== false) {
                $troubleshooting[] = '→ Device may be powered off or not responding';
                $troubleshooting[] = '→ Check if device is on the same network';
            } elseif ($errno == 111 || ($errstr && strpos($errstr, 'refused') !== false)) {
                $troubleshooting[] = '→ Port ' . $port . ' is closed or device is not listening';
                $troubleshooting[] = '→ Verify port number (default is 4370)';
            } elseif ($errno == 113 || ($errstr && strpos($errstr, 'No route') !== false)) {
                $troubleshooting[] = '→ Cannot route to device - check IP address';
                $troubleshooting[] = '→ Verify device is on the same network segment';
            }
        }
        
        return [
            'can_reach' => $canReach,
            'troubleshooting' => $troubleshooting
        ];
    }

    /**
     * Register staff to biometric device
     */
    public function registerStaff(Request $request, Staff $staff)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to register staff.'], 403);
        }

        $ownerId = $this->getOwnerId();
        if ($staff->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'enroll_id' => 'nullable|integer|min:1|max:65535',
            'device_ip' => 'required|ip',
            'device_port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            // Generate enroll_id if not provided
            // Extract numeric part from staff_id to use as primary identifier
            // Example: STF2025120001 -> extract numeric suffix (0001 = 1)
            if ($request->enroll_id) {
                $enrollId = $request->enroll_id;
            } else {
                // Extract numeric suffix from staff_id (last digits)
                // For STF2025120001, we want to get the unique numeric part
                $staffId = $staff->staff_id;
                
                // Try to extract the last numeric sequence (typically 4 digits)
                // Pattern: extract digits at the end (e.g., "0001" from "STF2025120001")
                preg_match('/(\d{1,4})$/', $staffId, $matches);
                if (!empty($matches[1])) {
                    // Use the numeric suffix (last 1-4 digits)
                    $enrollId = (int)$matches[1]; // Convert to integer (removes leading zeros)
                } else {
                    // If no numeric suffix found, try to extract all numeric parts
                    preg_match_all('/\d+/', $staffId, $allMatches);
                    if (!empty($allMatches[0])) {
                        // Use the last numeric sequence found
                        $lastNumeric = end($allMatches[0]);
                        $enrollId = (int)$lastNumeric;
                        // If too large, use modulo to fit in range
                        if ($enrollId > 65535) {
                            $enrollId = $enrollId % 65535;
                            if ($enrollId == 0) $enrollId = 1; // Ensure it's at least 1
                        }
                    } else {
                        // Fallback to database ID if staff_id has no numeric part
                        $enrollId = $staff->id;
                    }
                }
            }
            
            // Ensure enroll_id is numeric (ZKTeco requires numeric PIN)
            if (!is_numeric($enrollId)) {
                return response()->json([
                    'success' => false,
                    'message' => "Enroll ID must be numeric. ZKTeco devices require numeric PINs (1-65535)."
                ], 422);
            }
            
            $enrollId = (int)$enrollId;
            
            // Validate range (ZKTeco typically supports 1-65535)
            if ($enrollId < 1 || $enrollId > 65535) {
                return response()->json([
                    'success' => false,
                    'message' => "Enroll ID must be between 1 and 65535. Extracted value: {$enrollId}. Please specify manually."
                ], 422);
            }
            
            // Check if enroll_id already exists
            $existingMapping = BiometricDeviceMapping::where('enroll_id', $enrollId)
                ->where('user_id', $ownerId)
                ->where('staff_id', '!=', $staff->id)
                ->first();
            
            if ($existingMapping) {
                return response()->json([
                    'success' => false,
                    'message' => "Enroll ID {$enrollId} is already assigned to another staff member."
                ], 422);
            }

            // Register to device
            $service = new ZKTecoService($request->device_ip, $request->device_port, $request->password ?? 0);
            
            if (!$service->connect()) {
                throw new \Exception('Failed to connect to device');
            }

            $registered = $service->registerUser(
                $enrollId,
                $staff->full_name,
                '',
                0,
                0
            );

            $service->disconnect();

            if (!$registered) {
                throw new \Exception('Failed to register user on device');
            }

            // Create or update mapping
            $mapping = BiometricDeviceMapping::updateOrCreate(
                [
                    'user_id' => $ownerId,
                    'staff_id' => $staff->id,
                ],
                [
                    'enroll_id' => $enrollId,
                    'device_ip' => $request->device_ip,
                    'device_port' => $request->device_port,
                    'is_registered' => true,
                    'registered_at' => now(),
                ]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staff registered to device successfully',
                'mapping' => $mapping
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Register staff error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unregister staff from device
     */
    public function unregisterStaff(Staff $staff)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to unregister staff.'], 403);
        }

        $ownerId = $this->getOwnerId();
        if ($staff->user_id !== $ownerId) {
            abort(403, 'Unauthorized');
        }

        try {
            DB::beginTransaction();

            $mapping = BiometricDeviceMapping::where('user_id', $ownerId)
                ->where('staff_id', $staff->id)
                ->first();

            if (!$mapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff is not registered to any device'
                ], 404);
            }

            // Delete from device
            try {
                $service = new ZKTecoService($mapping->device_ip, $mapping->device_port);
                if ($service->connect()) {
                    $service->deleteUser($mapping->enroll_id);
                    $service->disconnect();
                }
            } catch (\Exception $e) {
                Log::warning("Failed to delete user from device: " . $e->getMessage());
                // Continue with database deletion even if device deletion fails
            }

            // Delete mapping
            $mapping->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Staff unregistered from device successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Unregister staff error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync attendance from device
     */
    public function syncAttendance(Request $request)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to sync attendance.'], 403);
        }

        $request->validate([
            'device_ip' => 'required|ip',
            'device_port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|integer',
            'date' => 'nullable|date',
        ]);

            $ownerId = $this->getOwnerId();
            // Allow empty date to sync all records, or specific date
            // If date is provided, sync that date + today to catch latest scans
            $requestDate = $request->date ?: null;
            $date = $requestDate;
            
            // Also sync today's date to ensure we get the latest scans
            $today = Carbon::today()->format('Y-m-d');

            try {
                $service = new ZKTecoService($request->device_ip, $request->device_port, $request->password ?? 0);
                
                // First, get all users currently on the device to verify
                Log::info("Fetching users from device: IP={$request->device_ip}, Port={$request->device_port}");
                $deviceUsers = $service->getUsers();
                Log::info("Device has " . count($deviceUsers) . " users: " . json_encode(array_map(function($u) {
                    return ['uid' => $u['uid'] ?? 'N/A', 'user_id' => $u['user_id'] ?? 'N/A', 'name' => $u['name'] ?? 'N/A'];
                }, $deviceUsers)));
                
                // Create a mapping of device UID to user_id (enroll_id) for attendance matching
                $deviceUidToEnrollId = [];
                $deviceEnrollIds = [];
                foreach ($deviceUsers as $key => $user) {
                    // The key is usually the user_id (enroll_id), and uid is the internal ID
                    $enrollId = isset($user['user_id']) ? (int)$user['user_id'] : (int)$key;
                    $uid = isset($user['uid']) ? (int)$user['uid'] : (int)$key;
                    $deviceUidToEnrollId[$uid] = $enrollId;
                    $deviceEnrollIds[] = $enrollId;
                    Log::info("Device user mapping: UID={$uid} -> Enroll ID={$enrollId}, Name=" . ($user['name'] ?? 'N/A'));
                }
                
                // Get registered enroll_ids for this owner
                // Get both as integers and strings for flexible matching
                $registeredEnrollIds = BiometricDeviceMapping::where('user_id', $ownerId)
                    ->pluck('enroll_id')
                    ->map(function($id) { 
                        // Try to convert to int, but keep original if conversion fails
                        $intId = (int)$id;
                        return $intId > 0 ? $intId : $id;
                    })
                    ->toArray();
                // Also get as strings for comparison
                $registeredEnrollIdsStrings = BiometricDeviceMapping::where('user_id', $ownerId)
                    ->pluck('enroll_id')
                    ->map(function($id) { return (string)$id; })
                    ->toArray();
                Log::info("Registered enroll_ids in system (int): " . json_encode($registeredEnrollIds));
                Log::info("Registered enroll_ids in system (string): " . json_encode($registeredEnrollIdsStrings));
                
                // If there's only one registered user on the device, we can assign all attendance to them
                // This handles cases where the device stores attendance with sequential record IDs
                $singleUserEnrollId = null;
                if (count($registeredEnrollIds) === 1 && count($deviceEnrollIds) === 1) {
                    $singleUserEnrollId = $registeredEnrollIds[0];
                    Log::info("Only one user on device (enroll_id: {$singleUserEnrollId}), will assign all attendance to this user");
                }
                
                // Get all attendances (don't filter by date in SDK, we'll filter manually)
                // Some devices might not support date filtering
                Log::info("Fetching attendance from device: IP={$request->device_ip}, Port={$request->device_port}");
                $attendances = $service->getAttendances();
                Log::info("Retrieved " . count($attendances) . " attendance records from device");
                
                // Don't filter by enroll_id here - we'll check during processing
                // This allows us to provide better error messages for unregistered users
                $originalCount = count($attendances);
                $enrollIdBreakdown = [];
                foreach ($attendances as $att) {
                    $enrollId = isset($att['user_id']) ? (int)$att['user_id'] : (isset($att['uid']) ? (int)$att['uid'] : (isset($att['userid']) ? (int)$att['userid'] : null));
                    if ($enrollId) {
                        $enrollIdBreakdown[$enrollId] = ($enrollIdBreakdown[$enrollId] ?? 0) + 1;
                    }
                }
                Log::info("Attendance records by enroll_id: " . json_encode($enrollIdBreakdown));
                Log::info("Registered enroll_ids: " . json_encode($registeredEnrollIds));
                
                // Log unregistered enroll_ids for better debugging
                $unregisteredEnrollIds = array_diff(array_keys($enrollIdBreakdown), $registeredEnrollIds);
                if (!empty($unregisteredEnrollIds)) {
                    Log::warning("Found attendance from unregistered enroll_ids: " . implode(', ', $unregisteredEnrollIds));
                    Log::warning("These staff members need to be registered on the Biometric Devices page");
                }
                
                if ($singleUserEnrollId !== null) {
                    Log::info("Single user device detected - keeping all {$originalCount} attendance records (will assign all to enroll_id {$singleUserEnrollId})");
                } else {
                    Log::info("Processing all {$originalCount} attendance records (will filter during mapping)");
                }
                
                // Log all attendance records for debugging
                Log::info("Sample attendance records (first 5):");
                foreach (array_slice($attendances, 0, 5) as $idx => $att) {
                    Log::info("Record #{$idx}: " . json_encode($att));
                }
                
                // Filter by date manually (but also show records from other dates for debugging)
                $allDates = [];
                foreach ($attendances as $att) {
                    $timestamp = isset($att['record_time']) ? $att['record_time'] : (isset($att['timestamp']) ? $att['timestamp'] : null);
                    if ($timestamp) {
                        try {
                            $attDate = Carbon::parse($timestamp)->format('Y-m-d');
                            $allDates[$attDate] = ($allDates[$attDate] ?? 0) + 1;
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                }
                Log::info("Attendance records by date: " . json_encode($allDates));
                Log::info("Requested date: {$date}");
                
                // Filter by date - include both requested date and today to catch latest scans
                $datesToSync = [];
                if ($date) {
                    $datesToSync[] = $date;
                }
                // Always include today to catch latest scans
                $today = Carbon::today()->format('Y-m-d');
                if ($today && !in_array($today, $datesToSync)) {
                    $datesToSync[] = $today;
                }
                
                if (!empty($datesToSync)) {
                    $beforeFilter = count($attendances);
                    $attendances = array_filter($attendances, function($att) use ($datesToSync) {
                        $timestamp = isset($att['record_time']) ? $att['record_time'] : (isset($att['timestamp']) ? $att['timestamp'] : null);
                        if (!$timestamp) return false;
                        try {
                            $attDate = Carbon::parse($timestamp)->format('Y-m-d');
                            return in_array($attDate, $datesToSync);
                        } catch (\Exception $e) {
                            Log::warning("Date parsing error: " . $e->getMessage() . " for timestamp: " . $timestamp);
                            return false;
                        }
                    });
                    Log::info("Filtered from {$beforeFilter} to " . count($attendances) . " records for dates: " . implode(', ', $datesToSync));
                }

            $errors = 0;
            $groupedRecords = []; // Group records by staff and date
            $unregisteredEnrollIdsFound = []; // Track unregistered enroll_ids for better error messages

            foreach ($attendances as $index => $att) {
                try {
                    Log::info("Processing attendance record #{$index}: " . json_encode($att));
                    
                    // SDK returns 'uid' as the user's UID (internal device ID), 'user_id' as badge ID
                    // But based on test output, 'uid' in attendance might be sequential record number
                    // We need to map device UID to enroll_id using device users list
                    $enrollId = null;
                    $attUid = isset($att['uid']) ? (int)$att['uid'] : null;
                    $attUserId = isset($att['user_id']) ? (int)$att['user_id'] : null;
                    
                    Log::info("Processing attendance: uid={$attUid}, user_id={$attUserId}");
                    
                    // Special case: If there's only one user on the device, assign all attendance to them
                    // This handles devices that store attendance with sequential record IDs
                    if ($singleUserEnrollId !== null) {
                        $enrollId = $singleUserEnrollId;
                        Log::info("Single user device - assigning attendance to enroll_id: {$enrollId}");
                    }
                    // Method 1: Try user_id directly FIRST (most reliable - user_id is usually the enroll_id)
                    // This should be checked before UID mapping since user_id is more reliable
                    elseif ($attUserId) {
                        // Try integer match first
                        $attUserIdInt = (int)$attUserId;
                        if (in_array($attUserIdInt, $registeredEnrollIds)) {
                            $enrollId = $attUserIdInt;
                            Log::info("Using user_id as enroll_id (int match): {$enrollId}");
                        }
                        // Try string match
                        elseif (isset($registeredEnrollIdsStrings) && in_array((string)$attUserId, $registeredEnrollIdsStrings)) {
                            $enrollId = (string)$attUserId;
                            Log::info("Using user_id as enroll_id (string match): {$enrollId}");
                        }
                        // Try direct match (in case types match)
                        elseif (in_array($attUserId, $registeredEnrollIds) || (isset($registeredEnrollIdsStrings) && in_array($attUserId, $registeredEnrollIdsStrings))) {
                            $enrollId = $attUserId;
                            Log::info("Using user_id as enroll_id (direct match): {$enrollId}");
                        } else {
                            Log::warning("user_id {$attUserId} not found in registered enroll_ids. Registered: " . json_encode($registeredEnrollIds));
                        }
                    }
                    // Method 2: Check if uid matches a device user's UID, then get their enroll_id
                    elseif ($attUid && isset($deviceUidToEnrollId[$attUid])) {
                        $enrollId = $deviceUidToEnrollId[$attUid];
                        Log::info("Mapped device UID {$attUid} to enroll_id {$enrollId}");
                    }
                    // Method 3: Check if uid itself is a registered enroll_id (fallback)
                    elseif ($attUid) {
                        // Try integer match
                        $attUidInt = (int)$attUid;
                        if (in_array($attUidInt, $registeredEnrollIds)) {
                            $enrollId = $attUidInt;
                            Log::info("Using uid as enroll_id (int match): {$enrollId}");
                        }
                        // Try string match
                        elseif (isset($registeredEnrollIdsStrings) && in_array((string)$attUid, $registeredEnrollIdsStrings)) {
                            $enrollId = (string)$attUid;
                            Log::info("Using uid as enroll_id (string match): {$enrollId}");
                        }
                    }
                    
                    if (!$enrollId) {
                        Log::warning("Attendance record #{$index} - cannot determine enroll_id. Full record: " . json_encode($att));
                        Log::warning("uid: " . ($att['uid'] ?? 'N/A') . ", user_id: " . ($att['user_id'] ?? 'N/A'));
                        $errors++;
                        continue;
                    }
                    
                    Log::info("Looking for mapping with enroll_id: {$enrollId}");
                    
                    // Try both string and integer matching
                    $mapping = BiometricDeviceMapping::where('user_id', $ownerId)
                        ->where(function($query) use ($enrollId) {
                            $query->where('enroll_id', (string)$enrollId)
                                  ->orWhere('enroll_id', $enrollId);
                        })
                        ->first();

                    if (!$mapping) {
                        $availableEnrollIds = BiometricDeviceMapping::where('user_id', $ownerId)
                            ->pluck('enroll_id', 'staff_id')
                            ->toArray();
                        
                        // Track this unregistered enroll_id
                        if (!in_array($enrollId, $unregisteredEnrollIdsFound)) {
                            $unregisteredEnrollIdsFound[] = $enrollId;
                        }
                        
                        Log::warning("No mapping found for enroll_id: {$enrollId} (owner_id: {$ownerId})");
                        Log::warning("Device returned enroll_id: {$enrollId}, but registered enroll_ids are: " . json_encode($availableEnrollIds));
                        Log::warning("This means the device has users with enroll_ids that don't match your registered staff.");
                        Log::warning("Solution: Either register these enroll_ids to staff members, or remove them from the device.");
                        $errors++;
                        continue;
                    }
                    
                    Log::info("Found mapping: staff_id={$mapping->staff_id}, enroll_id={$mapping->enroll_id}");

                    $staff = $mapping->staff;
                    if (!$staff) {
                        Log::warning("Staff not found for mapping enroll_id: {$enrollId}");
                        $errors++;
                        continue;
                    }

                    // Parse punch time - SDK returns 'record_time' field
                    $punchTime = null;
                    if (isset($att['record_time'])) {
                        try {
                            $punchTime = Carbon::parse($att['record_time']);
                        } catch (\Exception $e) {
                            Log::warning("Invalid record_time format: " . $att['record_time']);
                            $errors++;
                            continue;
                        }
                    } elseif (isset($att['timestamp'])) {
                        try {
                            $punchTime = Carbon::parse($att['timestamp']);
                        } catch (\Exception $e) {
                            Log::warning("Invalid timestamp format: " . $att['timestamp']);
                            $errors++;
                            continue;
                        }
                    } elseif (isset($att['time'])) {
                        try {
                            $punchTime = Carbon::parse($att['time']);
                        } catch (\Exception $e) {
                            Log::warning("Invalid time format: " . $att['time']);
                            $errors++;
                            continue;
                        }
                    } else {
                        Log::warning("Attendance record missing timestamp: " . json_encode($att));
                        $errors++;
                        continue;
                    }

                    $attendanceDate = $punchTime->format('Y-m-d');
                    
                    // Group by staff_id and date
                    $key = $mapping->staff_id . '_' . $attendanceDate;
                    if (!isset($groupedRecords[$key])) {
                        $groupedRecords[$key] = [
                            'mapping' => $mapping,
                            'staff' => $staff,
                            'enrollId' => $enrollId,
                            'date' => $attendanceDate,
                            'records' => []
                        ];
                    }
                    
                    $groupedRecords[$key]['records'][] = [
                        'punchTime' => $punchTime,
                        'att' => $att
                    ];
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Error collecting attendance record: " . $e->getMessage());
                    continue;
                }
            }
            
            // Sort records by time for each group and deduplicate
            foreach ($groupedRecords as &$group) {
                usort($group['records'], function($a, $b) {
                    return $a['punchTime']->timestamp <=> $b['punchTime']->timestamp;
                });
                
                // Log all records before deduplication
                Log::info("Before deduplication - Staff: {$group['staff']->full_name}, Date: {$group['date']}, Records: " . count($group['records']));
                foreach ($group['records'] as $idx => $rec) {
                    Log::info("  Record #{$idx}: {$rec['punchTime']->format('Y-m-d H:i:s')}");
                }
                
                // Deduplicate: remove records that are within 30 seconds of each other
                // BUT: Always keep at least 2 records if they exist (for check-in and check-out)
                $deduplicated = [];
                foreach ($group['records'] as $record) {
                    $isDuplicate = false;
                    foreach ($deduplicated as $existing) {
                        $timeDiff = abs($record['punchTime']->diffInSeconds($existing['punchTime']));
                        if ($timeDiff < 30) { // Less than 30 seconds apart = duplicate
                            $isDuplicate = true;
                            Log::info("Deduplicating record: {$record['punchTime']->format('Y-m-d H:i:s')} (too close to {$existing['punchTime']->format('Y-m-d H:i:s')})");
                            break;
                        }
                    }
                    if (!$isDuplicate) {
                        $deduplicated[] = $record;
                    }
                }
                
                // Ensure we keep at least the first 2 distinct records (for check-in and check-out)
                // This handles cases where deduplication might remove the second scan
                if (count($group['records']) >= 2 && count($deduplicated) < 2) {
                    // If we have 2+ original records but deduplication left us with less than 2,
                    // take the first 2 distinct records (sorted by time)
                    $deduplicated = array_slice($group['records'], 0, 2);
                    Log::info("Keeping first 2 records for check-in/check-out: " . count($deduplicated) . " record(s)");
                }
                
                $group['records'] = $deduplicated;
                
                // Log after deduplication
                Log::info("After deduplication - Staff: {$group['staff']->full_name}, Date: {$group['date']}, Records: " . count($group['records']));
                foreach ($group['records'] as $idx => $rec) {
                    Log::info("  Record #{$idx}: {$rec['punchTime']->format('Y-m-d H:i:s')}");
                }
            }
            
            // Now process grouped records (one check-in, one check-out per staff per day)
            $synced = 0;
            foreach ($groupedRecords as $group) {
                try {
                    $mapping = $group['mapping'];
                    $staff = $group['staff'];
                    $enrollId = $group['enrollId'];
                    $attendanceDate = $group['date'];
                    $records = $group['records'];
                    
                    if (empty($records)) {
                        continue;
                    }
                    
                    // Process attendance with check-in/check-out logic
                    DB::transaction(function() use ($mapping, $staff, $enrollId, $attendanceDate, $records, $request, &$synced) {
                        // Find existing attendance record for this date
                        $attendance = Attendance::where('user_id', $mapping->user_id)
                            ->where('staff_id', $staff->id)
                            ->where('attendance_date', $attendanceDate)
                            ->lockForUpdate()
                            ->first();
                        
                        $hasCheckIn = $attendance && !is_null($attendance->check_in_time);
                        $hasCheckOut = $attendance && !is_null($attendance->check_out_time);
                        
                        // Get first record for check-in, second for check-out
                        // Only use the first TWO records (check-in and check-out)
                        // Ignore all subsequent scans
                        $firstRecord = $records[0];
                        $secondRecord = isset($records[1]) ? $records[1] : null;
                        
                        // Log how many records we have
                        Log::info("Processing {$staff->full_name} for {$attendanceDate}: " . count($records) . " scan(s) found. Using first for check-in" . ($secondRecord ? " and second for check-out" : " only"));
                        
                        // If there are more than 2 records, log that we're ignoring the rest
                        if (count($records) > 2) {
                            Log::info("Ignoring " . (count($records) - 2) . " additional scan(s) after check-in and check-out");
                        }
                        
                        // Determine verify mode from first record
                        $verifyMode = 'Fingerprint';
                        if (isset($firstRecord['att']['type'])) {
                            $type = (int)$firstRecord['att']['type'];
                            if ($type === 15) {
                                $verifyMode = 'Face';
                            } elseif ($type === 2) {
                                $verifyMode = 'Card';
                            } elseif ($type === 1) {
                                $verifyMode = 'Password';
                            }
                        } elseif (isset($firstRecord['att']['verify'])) {
                            $verified = (int)$firstRecord['att']['verify'];
                            if ($verified >= 15) {
                                $verifyMode = 'Face';
                            } elseif ($verified >= 10) {
                                $verifyMode = 'Card';
                            }
                        }
                        
                        if (!$attendance) {
                            // First scan of the day = Check In (use first record)
                            Attendance::create([
                                'user_id' => $mapping->user_id,
                                'staff_id' => $staff->id,
                                'attendance_date' => $attendanceDate,
                                'check_in_time' => $firstRecord['punchTime'],
                                'status' => 'present',
                                'biometric_enroll_id' => (string)$enrollId,
                                'verify_mode' => $verifyMode,
                                'device_ip' => $request->device_ip,
                                'is_biometric' => true,
                            ]);
                            Log::info("✓ Synced Check In: {$staff->full_name} at {$firstRecord['punchTime']->format('Y-m-d H:i:s')}");
                            
                            // Second scan = Check Out (use second record if exists)
                            if ($secondRecord) {
                                // Ensure check-out is at least 1 second after check-in
                                $checkOutTime = $secondRecord['punchTime'];
                                if ($checkOutTime->lte($firstRecord['punchTime'])) {
                                    $checkOutTime = $firstRecord['punchTime']->copy()->addSecond();
                                }
                                
                                Attendance::where('user_id', $mapping->user_id)
                                    ->where('staff_id', $staff->id)
                                    ->where('attendance_date', $attendanceDate)
                                    ->update([
                                        'check_out_time' => $checkOutTime,
                                    ]);
                                Log::info("✓ Synced Check Out: {$staff->full_name} at {$checkOutTime->format('Y-m-d H:i:s')}");
                            } else {
                                Log::info("Only one scan recorded for {$staff->full_name} - check-out will be set on second scan");
                            }
                        } elseif (!$hasCheckIn) {
                            // Set check-in (use first record)
                            $attendance->update([
                                'check_in_time' => $firstRecord['punchTime'],
                                'status' => 'present',
                                'biometric_enroll_id' => (string)$enrollId,
                                'verify_mode' => $verifyMode,
                                'device_ip' => $request->device_ip,
                                'is_biometric' => true,
                            ]);
                            Log::info("✓ Synced Check In: {$staff->full_name} at {$firstRecord['punchTime']->format('Y-m-d H:i:s')}");
                            
                            // Second scan = Check Out (use second record if exists and no check-out yet)
                            if ($secondRecord && !$hasCheckOut) {
                                $checkOutTime = $secondRecord['punchTime'];
                                if ($checkOutTime->lte($firstRecord['punchTime'])) {
                                    $checkOutTime = $firstRecord['punchTime']->copy()->addSecond();
                                }
                                
                                $attendance->update([
                                    'check_out_time' => $checkOutTime,
                                ]);
                                Log::info("✓ Synced Check Out: {$staff->full_name} at {$checkOutTime->format('Y-m-d H:i:s')}");
                            }
                        } elseif (!$hasCheckOut && $secondRecord) {
                            // Already has check-in, set check-out from second record
                            $checkOutTime = $secondRecord['punchTime'];
                            if ($checkOutTime->lte($attendance->check_in_time)) {
                                $checkOutTime = $attendance->check_in_time->copy()->addSecond();
                            }
                            
                            $attendance->update([
                                'check_out_time' => $checkOutTime,
                                'biometric_enroll_id' => (string)$enrollId,
                                'device_ip' => $request->device_ip,
                                'is_biometric' => true,
                            ]);
                            Log::info("✓ Synced Check Out: {$staff->full_name} at {$checkOutTime->format('Y-m-d H:i:s')}");
                        } else {
                            // Already has both check-in and check-out - ignore all subsequent scans
                            if ($hasCheckOut) {
                                Log::info("Staff {$staff->full_name} already has check-in and check-out for {$attendanceDate} - ignoring " . count($records) . " scan(s)");
                            } else {
                                Log::info("Staff {$staff->full_name} has check-in but no second scan for check-out");
                            }
                        }
                        
                        // Update last sync time
                        $mapping->update(['last_sync_at' => now()]);
                        $synced++;
                    });
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Sync attendance error for enroll_id " . (isset($att['uid']) ? $att['uid'] : 'unknown') . ": " . $e->getMessage());
                    Log::error("Stack trace: " . $e->getTraceAsString());
                }
            }

            // Get list of enroll_ids that failed (should be empty now since we filtered)
            $failedEnrollIds = [];
            $allDeviceEnrollIds = [];
            
            // Get all enroll_ids from device attendance (including unregistered ones for info)
            try {
                $allAttendances = $service->getAttendances();
                foreach ($allAttendances as $att) {
                    $enrollId = isset($att['uid']) ? (int)$att['uid'] : (isset($att['userid']) ? (int)$att['userid'] : null);
                    if ($enrollId) {
                        $allDeviceEnrollIds[] = $enrollId;
                        $mapping = BiometricDeviceMapping::where('user_id', $ownerId)
                            ->where(function($query) use ($enrollId) {
                                $query->where('enroll_id', (string)$enrollId)
                                      ->orWhere('enroll_id', $enrollId);
                            })
                            ->first();
                        if (!$mapping) {
                            $failedEnrollIds[] = $enrollId;
                        }
                    }
                }
                $failedEnrollIds = array_unique($failedEnrollIds);
                $allDeviceEnrollIds = array_unique($allDeviceEnrollIds);
            } catch (\Exception $e) {
                Log::warning("Could not get all attendances for analysis: " . $e->getMessage());
            }
            
            $message = "Synced {$synced} attendance records.";
            
            // Add information about unregistered enroll_ids if any were found
            if (!empty($unregisteredEnrollIdsFound)) {
                $message .= "\n\n⚠️  Attendance found for unregistered enroll_ids: " . implode(', ', array_unique($unregisteredEnrollIdsFound));
                $message .= "\n\nTo sync these employees' attendance:";
                $message .= "\n1. Go to HR → Biometric Devices";
                $message .= "\n2. Find the staff members with these enroll_ids";
                $message .= "\n3. Click 'Register' to register them on the device";
                $message .= "\n4. Sync again";
            }
            
            // Add information about unregistered enroll_ids if any were found
            if (!empty($unregisteredEnrollIdsFound)) {
                $message .= "\n\n⚠️  Attendance found for unregistered enroll_ids: " . implode(', ', array_unique($unregisteredEnrollIdsFound));
                $message .= "\n\nTo sync these employees' attendance:";
                $message .= "\n1. Go to HR → Biometric Devices";
                $message .= "\n2. Find the staff members with these enroll_ids";
                $message .= "\n3. Click 'Register' to register them on the device";
                $message .= "\n4. Sync again";
            }
            
            // Get detailed info for debugging
            $totalRecords = count($allAttendances ?? []);
            $recordsForDate = 0;
            $recordsForRegisteredUsers = 0;
            
            if ($date) {
                foreach ($allAttendances ?? [] as $att) {
                    $timestamp = isset($att['record_time']) ? $att['record_time'] : (isset($att['timestamp']) ? $att['timestamp'] : null);
                    if ($timestamp) {
                        try {
                            $attDate = Carbon::parse($timestamp)->format('Y-m-d');
                            if ($attDate === $date) {
                                $recordsForDate++;
                                $enrollId = isset($att['uid']) ? (int)$att['uid'] : (isset($att['userid']) ? (int)$att['userid'] : null);
                                if ($enrollId && in_array($enrollId, $registeredEnrollIds)) {
                                    $recordsForRegisteredUsers++;
                                }
                            }
                        } catch (\Exception $e) {
                            // Ignore
                        }
                    }
                }
            }
            
            if ($synced === 0) {
                $message = "No new attendance records found.";
                $message .= "\n\nDebug Info:";
                $message .= "\n- Total attendance records on device: {$totalRecords}";
                if ($date) {
                    $message .= "\n- Records for date {$date}: {$recordsForDate}";
                    $message .= "\n- Records from registered users for date {$date}: {$recordsForRegisteredUsers}";
                }
                $message .= "\n- Registered enroll_ids: " . (empty($registeredEnrollIds) ? 'None' : implode(', ', $registeredEnrollIds));
                if (!empty($allDeviceEnrollIds)) {
                    $message .= "\n- Enroll_ids found in attendance: " . implode(', ', array_unique($allDeviceEnrollIds));
                    
                    // Check if there's a mismatch
                    $unregisteredIds = array_diff(array_unique($allDeviceEnrollIds), $registeredEnrollIds);
                    if (!empty($unregisteredIds)) {
                        $message .= "\n\n⚠️  Found attendance from unregistered enroll_ids: " . implode(', ', $unregisteredIds);
                        $message .= "\n\nSolution: Register these staff members with their enroll_ids on the Biometric Devices page.";
                    }
                }
                $message .= "\n\nPossible reasons:";
                if ($totalRecords === 0) {
                    $message .= "\n1. No attendance records on device (no one has scanned yet)";
                } else {
                    $message .= "\n1. Attendance records exist but staff members are not registered";
                    $message .= "\n2. Enroll_id mismatch (device enroll_id doesn't match registered enroll_id)";
                    if ($date) {
                        $message .= "\n3. Attendance records are from a different date (not {$date})";
                    }
                }
            }
            
            if ($errors > 0) {
                $message .= "\n\n{$errors} error(s) occurred.";
                if (!empty($unregisteredEnrollIdsFound)) {
                    $message .= " Most errors are due to unregistered enroll_ids listed above.";
                }
            }
            
            if (!empty($failedEnrollIds)) {
                $message .= "\nFound attendance records from unregistered enroll_ids: " . implode(', ', $failedEnrollIds);
                $message .= ". These are old attendance records from deleted users. You can ignore them or clear attendance logs on the device.";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'synced' => $synced,
                'errors' => $errors,
                'failed_enroll_ids' => $failedEnrollIds,
                'registered_enroll_ids' => $registeredEnrollIds,
                'device_users' => array_map(function($u) {
                    return ['uid' => $u['uid'] ?? 'N/A', 'name' => $u['name'] ?? 'N/A'];
                }, $deviceUsers ?? []),
                'all_attendance_enroll_ids' => $allDeviceEnrollIds ?? [],
                'total_records' => $totalRecords,
                'records_for_date' => $recordsForDate,
                'records_for_registered_users' => $recordsForRegisteredUsers,
            ]);
        } catch (\Exception $e) {
            Log::error("Sync attendance error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove unregistered users from device
     */
    public function removeUnregisteredUsers(Request $request)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to remove users from device.'], 403);
        }

        $request->validate([
            'device_ip' => 'required|ip',
            'device_port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|integer',
            'enroll_ids' => 'required|array',
            'enroll_ids.*' => 'required|integer',
        ]);

        $ownerId = $this->getOwnerId();
        $removed = 0;
        $errors = 0;
        $errorMessages = [];

        try {
            $service = new ZKTecoService($request->device_ip, $request->device_port, $request->password ?? 0);
            
            foreach ($request->enroll_ids as $enrollId) {
                try {
                    Log::info("Removing user from device: enroll_id={$enrollId}");
                    $result = $service->deleteUser($enrollId);
                    
                    if ($result) {
                        $removed++;
                        Log::info("✓ Successfully removed user: enroll_id={$enrollId}");
                    } else {
                        $errors++;
                        $errorMessages[] = "Failed to remove enroll_id {$enrollId}";
                        Log::warning("✗ Failed to remove user: enroll_id={$enrollId}");
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $errorMessages[] = "Error removing enroll_id {$enrollId}: " . $e->getMessage();
                    Log::error("Error removing user enroll_id {$enrollId}: " . $e->getMessage());
                }
            }

            $message = "Removed {$removed} user(s) from device.";
            if ($errors > 0) {
                $message .= " {$errors} error(s) occurred: " . implode(', ', $errorMessages);
            }

            return response()->json([
                'success' => $removed > 0,
                'message' => $message,
                'removed' => $removed,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            Log::error("Remove unregistered users error: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get device users
     */
    public function getDeviceUsers(Request $request)
    {
        if (!$this->hasPermission('hr', 'view')) {
            return response()->json(['error' => 'You do not have permission to view device users.'], 403);
        }

        $request->validate([
            'device_ip' => 'required|ip',
            'device_port' => 'required|integer|min:1|max:65535',
            'password' => 'nullable|integer',
        ]);

        try {
            $service = new ZKTecoService($request->device_ip, $request->device_port, $request->password ?? 0);
            $users = $service->getUsers();
            
            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
