<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\BiometricDeviceMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class BiometricPushController extends Controller
{
    /**
     * Handle device ping/check-in and command polling (ADMS Protocol)
     * GET /iclock/getrequest?SN=XXXXXXXXXX
     */
    public function getRequest(Request $request)
    {
        $sn = $request->get('SN', 'UNKNOWN');
        
        Log::info("=== ZKTECO DEVICE PING/COMMAND REQUEST ===");
        Log::info("Serial Number: {$sn}");
        Log::info("Request URL: " . $request->fullUrl());
        Log::info("IP: " . $request->ip());
        Log::info("All Params: " . json_encode($request->all()));
        Log::info("Headers: " . json_encode($request->headers->all()));
        
        // Device expects simple "OK" response or commands in text/plain
        return Response::make('OK', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Handle device data push (ADMS Protocol)
     * POST /iclock/cdata?SN=XXXXXXXXXX&table=ATTLOG&c=log
     */
    public function cdata(Request $request)
    {
        $sn = $request->get('SN', 'UNKNOWN');
        $table = $request->get('table', 'UNKNOWN');
        $command = $request->get('c', '');
        
        Log::info("=== ZKTECO DEVICE DATA PUSH (ADMS) ===");
        Log::info("Serial Number: {$sn}");
        Log::info("Table: {$table}");
        Log::info("Command: {$command}");
        Log::info("IP: " . $request->ip());
        Log::info("Request URL: " . $request->fullUrl());
        Log::info("All Params: " . json_encode($request->all()));
        Log::info("Headers: " . json_encode($request->headers->all()));
        
        $rawData = $request->getContent();
        Log::info("Raw Data Length: " . strlen($rawData));
        Log::info("Raw Data (first 500 chars): " . substr($rawData, 0, 500));
        Log::info("Raw Data (full): " . $rawData);
        
        try {
            if ($table === 'ATTLOG' || ($table === 'ATTLOG' && $command === 'log')) {
                // Attendance log (ADMS format)
                Log::info("Processing ATTLOG data...");
                $this->handleAttendanceLogADMS($sn, $rawData, $request->ip());
            } elseif ($table === 'USER' && $command === 'data') {
                // User data (ADMS format) - just acknowledge
                Log::info("User data received from device");
            } else {
                Log::warning("Unknown table/command: table={$table}, command={$command}");
                Log::warning("This might indicate the device is using a different format. Check device settings.");
            }
            
            // Device expects "OK" response
            return Response::make('OK', 200, ['Content-Type' => 'text/plain']);
        } catch (\Exception $e) {
            Log::error("Error processing push data: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            // Still return OK to device to prevent retries
            return Response::make('OK', 200, ['Content-Type' => 'text/plain']);
        }
    }

    /**
     * Handle attendance log in ADMS format
     * Format: PIN=1001\tDateTime=2025-12-15 08:00:00\tVerified=15\tStatus=0\n
     */
    private function handleAttendanceLogADMS($sn, $rawData, $deviceIp)
    {
        Log::info("=== Processing attendance log (ADMS format) ===");
        Log::info("Raw data received: " . $rawData);
        
        if (empty(trim($rawData))) {
            Log::warning("Empty raw data received from device");
            return;
        }
        
        $lines = explode("\n", trim($rawData));
        Log::info("Number of lines: " . count($lines));
        
        foreach ($lines as $lineIndex => $line) {
            if (empty(trim($line))) {
                Log::info("Skipping empty line {$lineIndex}");
                continue;
            }
            
            Log::info("Processing line {$lineIndex}: " . $line);
            
            // Parse tab-separated key=value pairs
            $parts = explode("\t", $line);
            $attData = [];
            
            foreach ($parts as $part) {
                $part = trim($part);
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', $part, 2);
                    $attData[trim($key)] = trim($value);
                }
            }
            
            Log::info("Parsed attendance data: " . json_encode($attData));
            
            $pin = isset($attData['PIN']) ? (int)$attData['PIN'] : null;
            $dateTime = isset($attData['DateTime']) ? trim($attData['DateTime']) : null;
            $verified = isset($attData['Verified']) ? (int)$attData['Verified'] : 0;
            
            Log::info("Extracted: PIN={$pin}, DateTime={$dateTime}, Verified={$verified}");
            
            if ($pin === null || $dateTime === null) {
                Log::warning("Attendance log missing PIN or DateTime. PIN={$pin}, DateTime={$dateTime}");
                continue;
            }
            
            // Parse datetime
            try {
                $punchTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateTime);
            } catch (\Exception $e) {
                try {
                    $punchTime = Carbon::parse($dateTime);
                } catch (\Exception $e2) {
                    Log::warning("Invalid datetime format: {$dateTime}");
                    continue;
                }
            }
            
            // Find mapping by enroll_id
            Log::info("Looking for mapping with enroll_id: {$pin}");
            $mapping = BiometricDeviceMapping::where('enroll_id', (string)$pin)->first();
            
            if (!$mapping) {
                // Try with integer comparison as well
                $mapping = BiometricDeviceMapping::where('enroll_id', $pin)->first();
            }
            
            if (!$mapping) {
                Log::warning("No mapping found for enroll_id: {$pin}");
                Log::warning("Available enroll_ids in database: " . json_encode(BiometricDeviceMapping::pluck('enroll_id')->toArray()));
                continue;
            }
            
            Log::info("Found mapping: staff_id={$mapping->staff_id}, user_id={$mapping->user_id}");
            
            $staff = $mapping->staff;
            if (!$staff) {
                Log::warning("Staff not found for mapping enroll_id: {$pin}, staff_id: {$mapping->staff_id}");
                continue;
            }
            
            Log::info("Found staff: {$staff->full_name} (ID: {$staff->id})");
            
            // Process attendance with transaction
            DB::transaction(function() use ($staff, $pin, $punchTime, $verified, $deviceIp, $mapping) {
                $attendanceDate = $punchTime->format('Y-m-d');
                
                // Find existing attendance record
                $attendance = Attendance::where('user_id', $mapping->user_id)
                    ->where('staff_id', $staff->id)
                    ->where('attendance_date', $attendanceDate)
                    ->lockForUpdate()
                    ->first();
                
                // Determine verify mode
                $verifyMode = 'Fingerprint';
                if ($verified >= 15) {
                    $verifyMode = 'Face';
                } elseif ($verified >= 10) {
                    $verifyMode = 'Card';
                }
                
                if (!$attendance) {
                    // First scan of the day = Check In
                    Attendance::create([
                        'user_id' => $mapping->user_id,
                        'staff_id' => $staff->id,
                        'attendance_date' => $attendanceDate,
                        'check_in_time' => $punchTime,
                        'status' => 'present',
                        'biometric_enroll_id' => (string)$pin,
                        'verify_mode' => $verifyMode,
                        'device_ip' => $deviceIp,
                        'is_biometric' => true,
                    ]);
                    
                    Log::info("✓ Staff {$staff->full_name} (ID: {$staff->id}) checked IN at {$punchTime->format('Y-m-d H:i:s')}");
                } else {
                    // Check if already has check-in and check-out
                    $hasCheckIn = !is_null($attendance->check_in_time);
                    $hasCheckOut = !is_null($attendance->check_out_time);
                    
                    if (!$hasCheckIn) {
                        // Set check-in
                        $attendance->update([
                            'check_in_time' => $punchTime,
                            'status' => 'present',
                            'biometric_enroll_id' => (string)$pin,
                            'verify_mode' => $verifyMode,
                            'device_ip' => $deviceIp,
                            'is_biometric' => true,
                        ]);
                        
                        Log::info("✓ Staff {$staff->full_name} (ID: {$staff->id}) checked IN at {$punchTime->format('Y-m-d H:i:s')}");
                    } elseif (!$hasCheckOut) {
                        // Set check-out (ensure it's after check-in)
                        $checkOutTime = $punchTime;
                        if ($punchTime->lte($attendance->check_in_time)) {
                            $checkOutTime = $attendance->check_in_time->copy()->addSecond();
                        }
                        
                        $attendance->update([
                            'check_out_time' => $checkOutTime,
                            'biometric_enroll_id' => (string)$pin,
                            'verify_mode' => $verifyMode,
                            'device_ip' => $deviceIp,
                            'is_biometric' => true,
                        ]);
                        
                        Log::info("✓ Staff {$staff->full_name} (ID: {$staff->id}) checked OUT at {$checkOutTime->format('Y-m-d H:i:s')}");
                    } else {
                        // Already has both check-in and check-out
                        Log::info("Staff {$staff->full_name} already checked in and out today");
                    }
                }
                
                // Update last sync time
                $mapping->update(['last_sync_at' => now()]);
            });
        }
    }
}
