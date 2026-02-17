<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZKTecoService;
use App\Models\BiometricDeviceMapping;
use App\Models\Attendance;
use App\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncBiometricAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:sync-biometric 
                            {--device-ip= : Device IP address}
                            {--device-port=4370 : Device port}
                            {--password=0 : Comm Key}
                            {--date= : Specific date to sync (Y-m-d), or leave empty for today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically sync attendance from biometric devices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting automatic biometric attendance sync...');
        
        // Get all owners who have biometric devices configured
        $mappings = BiometricDeviceMapping::with('owner', 'staff')
            ->where('is_registered', true)
            ->get()
            ->groupBy('user_id');
        
        if ($mappings->isEmpty()) {
            $this->warn('No registered biometric devices found.');
            return 0;
        }
        
        $totalSynced = 0;
        $totalErrors = 0;
        
        foreach ($mappings as $ownerId => $ownerMappings) {
            $this->info("Processing owner ID: {$ownerId}");
            
            // Get device configuration from first mapping
            $firstMapping = $ownerMappings->first();
            $deviceIp = $this->option('device-ip') ?: $firstMapping->device_ip;
            $devicePort = $this->option('device-port') ?: $firstMapping->device_port;
            $password = $this->option('password') ?: 0;
            $date = $this->option('date') ?: Carbon::today()->format('Y-m-d');
            
            $this->info("Device: {$deviceIp}:{$devicePort}, Date: {$date}");
            
            try {
                $service = new ZKTecoService($deviceIp, $devicePort, $password);
                
                // Get device users
                $deviceUsers = $service->getUsers();
                $this->info("Device has " . count($deviceUsers) . " users");
                
                // Create mapping
                $deviceUidToEnrollId = [];
                $deviceEnrollIds = [];
                foreach ($deviceUsers as $key => $user) {
                    $enrollId = isset($user['user_id']) ? (int)$user['user_id'] : (int)$key;
                    $uid = isset($user['uid']) ? (int)$user['uid'] : (int)$key;
                    $deviceUidToEnrollId[$uid] = $enrollId;
                    $deviceEnrollIds[] = $enrollId;
                }
                
                // Get registered enroll_ids
                $registeredEnrollIds = BiometricDeviceMapping::where('user_id', $ownerId)
                    ->pluck('enroll_id')
                    ->map(function($id) { return (int)$id; })
                    ->toArray();
                
                // Check if single user
                $singleUserEnrollId = null;
                if (count($registeredEnrollIds) === 1 && count($deviceEnrollIds) === 1) {
                    $singleUserEnrollId = $registeredEnrollIds[0];
                    $this->info("Single user device - enroll_id: {$singleUserEnrollId}");
                }
                
                // Get attendances
                $attendances = $service->getAttendances();
                $this->info("Retrieved " . count($attendances) . " attendance records");
                
                // Filter by registered users (unless single user)
                if ($singleUserEnrollId === null) {
                    $attendances = array_filter($attendances, function($att) use ($registeredEnrollIds) {
                        $enrollId = isset($att['uid']) ? (int)$att['uid'] : (isset($att['userid']) ? (int)$att['userid'] : null);
                        return $enrollId && in_array($enrollId, $registeredEnrollIds);
                    });
                }
                
                // Filter by date
                if ($date) {
                    $attendances = array_filter($attendances, function($att) use ($date) {
                        $timestamp = isset($att['record_time']) ? $att['record_time'] : (isset($att['timestamp']) ? $att['timestamp'] : null);
                        if (!$timestamp) return false;
                        try {
                            $attDate = Carbon::parse($timestamp)->format('Y-m-d');
                            return $attDate === $date;
                        } catch (\Exception $e) {
                            return false;
                        }
                    });
                }
                
                $this->info("Processing " . count($attendances) . " attendance records for date: {$date}");
                
                $synced = 0;
                $errors = 0;
                
                foreach ($attendances as $att) {
                    try {
                        // Determine enroll_id
                        $enrollId = null;
                        if ($singleUserEnrollId !== null) {
                            $enrollId = $singleUserEnrollId;
                        } elseif (isset($att['uid']) && isset($deviceUidToEnrollId[(int)$att['uid']])) {
                            $enrollId = $deviceUidToEnrollId[(int)$att['uid']];
                        } elseif (isset($att['user_id']) && in_array((int)$att['user_id'], $registeredEnrollIds)) {
                            $enrollId = (int)$att['user_id'];
                        } elseif (isset($att['uid']) && in_array((int)$att['uid'], $registeredEnrollIds)) {
                            $enrollId = (int)$att['uid'];
                        }
                        
                        if (!$enrollId) {
                            $errors++;
                            continue;
                        }
                        
                        // Find mapping
                        $mapping = BiometricDeviceMapping::where('user_id', $ownerId)
                            ->where(function($query) use ($enrollId) {
                                $query->where('enroll_id', (string)$enrollId)
                                      ->orWhere('enroll_id', $enrollId);
                            })
                            ->first();
                        
                        if (!$mapping) {
                            $errors++;
                            continue;
                        }
                        
                        $staff = $mapping->staff;
                        if (!$staff) {
                            $errors++;
                            continue;
                        }
                        
                        // Parse time
                        $punchTime = null;
                        if (isset($att['record_time'])) {
                            $punchTime = Carbon::parse($att['record_time']);
                        } elseif (isset($att['timestamp'])) {
                            $punchTime = Carbon::parse($att['timestamp']);
                        }
                        
                        if (!$punchTime) {
                            $errors++;
                            continue;
                        }
                        
                        $attendanceDate = $punchTime->format('Y-m-d');
                        
                        // Get verify mode from attendance record
                        $attType = isset($att['type']) ? (int)$att['type'] : 0;
                        $verifyMode = 'Fingerprint';
                        if ($attType === 15) {
                            $verifyMode = 'Face';
                        } elseif ($attType === 2) {
                            $verifyMode = 'Card';
                        } elseif ($attType === 1) {
                            $verifyMode = 'Password';
                        }
                        
                        // Process attendance
                        DB::transaction(function() use ($mapping, $staff, $enrollId, $punchTime, $attendanceDate, $deviceIp, $verifyMode, &$synced) {
                            $attendance = Attendance::where('user_id', $mapping->user_id)
                                ->where('staff_id', $staff->id)
                                ->where('attendance_date', $attendanceDate)
                                ->lockForUpdate()
                                ->first();
                            
                            $hasCheckIn = $attendance && !is_null($attendance->check_in_time);
                            $hasCheckOut = $attendance && !is_null($attendance->check_out_time);
                            
                            if (!$attendance) {
                                Attendance::create([
                                    'user_id' => $mapping->user_id,
                                    'staff_id' => $staff->id,
                                    'attendance_date' => $attendanceDate,
                                    'check_in_time' => $punchTime,
                                    'status' => 'present',
                                    'biometric_enroll_id' => (string)$enrollId,
                                    'verify_mode' => $verifyMode,
                                    'device_ip' => $deviceIp,
                                    'is_biometric' => true,
                                ]);
                            } elseif (!$hasCheckIn) {
                                $attendance->update([
                                    'check_in_time' => $punchTime,
                                    'status' => 'present',
                                    'biometric_enroll_id' => (string)$enrollId,
                                    'verify_mode' => $verifyMode,
                                    'device_ip' => $deviceIp,
                                    'is_biometric' => true,
                                ]);
                            } elseif (!$hasCheckOut) {
                                $checkOutTime = $punchTime;
                                if ($punchTime->lte($attendance->check_in_time)) {
                                    $checkOutTime = $attendance->check_in_time->copy()->addSecond();
                                }
                                
                                $attendance->update([
                                    'check_out_time' => $checkOutTime,
                                    'biometric_enroll_id' => (string)$enrollId,
                                    'verify_mode' => $verifyMode,
                                    'device_ip' => $deviceIp,
                                    'is_biometric' => true,
                                ]);
                            }
                            
                            $mapping->update(['last_sync_at' => now()]);
                            $synced++;
                        });
                    } catch (\Exception $e) {
                        $errors++;
                        Log::error("Sync attendance error: " . $e->getMessage());
                    }
                }
                
                $totalSynced += $synced;
                $totalErrors += $errors;
                
                $this->info("Owner {$ownerId}: Synced {$synced} records, {$errors} errors");
                
            } catch (\Exception $e) {
                $this->error("Error syncing for owner {$ownerId}: " . $e->getMessage());
                $totalErrors++;
            }
        }
        
        $this->info("Sync complete! Total: {$totalSynced} synced, {$totalErrors} errors");
        
        return 0;
    }
}

