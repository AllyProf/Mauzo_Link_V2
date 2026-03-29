<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesStaffPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

class SystemMonitorController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * Show system logs in a user-friendly format
     */
    public function logs()
    {
        if (!$this->isSuperAdmin()) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }

        $logFile = storage_path('logs/laravel.log');
        $parsedLogs = [];
        
        if (File::exists($logFile)) {
            // Read lines and reverse to get latest first
            $content = File::get($logFile);
            $lines = explode("\n", $content);
            $lines = array_reverse($lines);
            
            $limit = 1000; // Increased limit for DataTables
            $count = 0;
            
            foreach($lines as $line) {
                if (empty(trim($line)) || strlen($line) < 20) continue;
                if ($count >= $limit) break;
                
                // Better regex: [Timestamp] Env.Level: Rest
                if (preg_match('/\[([\d\- :]+)\] (.*?)\.(.*?): (.*)/', $line, $matches)) {
                    $timestamp = $matches[1];
                    $level = strtoupper($matches[3]);
                    $rest = trim($matches[4]);
                    
                    // Separate Message and JSON data
                    $message = $rest;
                    $data = '';
                    $jsonPos = strpos($rest, ' {');
                    if ($jsonPos !== false) {
                        $message = substr($rest, 0, $jsonPos);
                        $data = substr($rest, $jsonPos + 1);
                    }
                    
                    // Humanize the technical message
                    $friendly = $this->humanizeLog(trim($message), trim($data));
                    
                    $parsedLogs[] = [
                        'time' => \Carbon\Carbon::parse($timestamp)->diffForHumans(),
                        'exact_time' => \Carbon\Carbon::parse($timestamp)->format('M d, Y H:i:s'),
                        'level' => $level,
                        'action' => $friendly['action'],
                        'details' => $friendly['details'],
                        'icon' => $friendly['icon'],
                        'class' => $friendly['class'],
                        'raw' => $line
                    ];
                    $count++;
                }
            }
        }
        
        $staff = $this->getCurrentStaff();
        
        return view('admin.system.logs', compact('parsedLogs', 'staff'));
    }

    /**
     * Helper to turn tech logs into human stories
     */
    private function humanizeLog($message, $data)
    {
        $context = json_decode($data, true) ?: [];
        
        // Default values
        $result = [
            'action' => 'System Message',
            'details' => $message,
            'icon' => 'fa-info-circle',
            'class' => 'text-info'
        ];

        $msgLower = strtolower($message);

        // Login Patterns
        if (str_contains($msgLower, 'staff session created') || str_contains($msgLower, 'staff found')) {
            $email = $context['email'] ?? 'A staff member';
            $result = [
                'action' => 'User Signed In',
                'details' => "{$email} logged in to the dashboard.",
                'icon' => 'fa-sign-in text-success',
                'class' => 'table-success'
            ];
        } 
        elseif (str_contains($msgLower, 'login attempt')) {
            $email = $context['email'] ?? 'Unknown';
            $result = [
                'action' => 'Login Attempt',
                'details' => "Sign-in attempt for account: {$email}.",
                'icon' => 'fa-user-circle-o',
                'class' => ''
            ];
        }
        elseif (str_contains($msgLower, 'user authentication failed, checking staff')) {
            $result = [
                'action' => 'Account Security Check',
                'details' => "System is verifying account details through its secure multi-database gateway (Normal Login Step).",
                'icon' => 'fa-shield text-muted',
                'class' => 'text-muted'
            ];
        }
        elseif (str_contains($msgLower, 'password correct')) {
             $result = [
                'action' => 'Success Clear',
                'details' => "The system confirmed the correct password was entered.",
                'icon' => 'fa-check-circle text-success',
                'class' => ''
            ];
        }
        elseif (str_contains($msgLower, 'password check result')) {
             $match = $context['password_match'] ?? false;
             $result = [
                'action' => 'Credential Check',
                'details' => $match ? "Password was verified successfully." : "The password provided did not match.",
                'icon' => $match ? 'fa-key text-success' : 'fa-lock text-danger',
                'class' => $match ? '' : 'table-danger'
            ];
        }
        elseif (str_contains($msgLower, 'staff found')) {
            $email = $context['email'] ?? 'A staff member';
            $result = [
                'action' => 'Account Found',
                'details' => "Staff account identified: {$email}.",
                'icon' => 'fa-id-card-o text-info',
                'class' => ''
            ];
        }
        elseif (str_contains($msgLower, 'password incorrect')) {
            $email = $context['email'] ?? 'Unknown';
            $result = [
                'action' => 'Login Failed',
                'details' => "Incorrect password entered for account: {$email}.",
                'icon' => 'fa-warning text-danger',
                'class' => 'table-danger'
            ];
        }
        // Permission Patterns
        elseif (str_contains($msgLower, 'haspermission called') || str_contains($msgLower, 'checking role')) {
            $module = str_replace('_', ' ', $context['module'] ?? 'System');
            $action = $context['action'] ?? 'view';
            $result = [
                'action' => 'Section Audit',
                'details' => "System verified permission for the " . ucwords($module) . " module.",
                'icon' => 'fa-lock text-muted',
                'class' => 'text-muted'
            ];
        }
        elseif (str_contains($msgLower, 'permission check result')) {
            $hasPerm = $context['has_permission'] ?? false;
            $module = str_replace('_', ' ', $context['module'] ?? 'System');
            $result = [
                'action' => $hasPerm ? 'Access Granted' : 'Access Denied',
                'details' => ($hasPerm ? "Approved" : "Blocked") . " access to " . ucwords($module) . ".",
                'icon' => $hasPerm ? 'fa-unlock text-success' : 'fa-ban text-danger',
                'class' => $hasPerm ? '' : 'table-danger'
            ];
        }
        elseif (str_contains($msgLower, 'manager role detected')) {
            $module = str_replace('_', ' ', $context['module'] ?? 'System');
            $result = [
                'action' => 'Admin Override',
                'details' => "Admin status granted instant access to " . ucwords($module) . ".",
                'icon' => 'fa-shield text-info',
                'class' => ''
            ];
        }
        // Error Patterns
        elseif (str_contains($msgLower, 'error') || str_contains($msgLower, 'exception') || str_contains($msgLower, 'sqlstate')) {
             $detail = "A system error occurred. Most issues are handled automatically, but you may want to refresh the page.";
             if (str_contains($msgLower, 'undefined method')) $detail = "The system encountered a minor coding hitch that it recovered from.";
             
            $result = [
                'action' => 'System Issue',
                'details' => $detail,
                'icon' => 'fa-bug text-danger',
                'class' => 'table-warning'
            ];
        }

        return $result;
    }

    /**
     * Show active user sessions
     */
    public function sessions()
    {
        if (!$this->isSuperAdmin()) {
            abort(403, 'Unauthorized. Super Admin access required.');
        }
        
        $sessions = DB::table('sessions')
            ->orderBy('last_activity', 'desc')
            ->get();
            
        // Pre-fetch Users (Shop owners/Admins)
        $userIds = $sessions->pluck('user_id')->filter()->unique();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
        
        // Enhance sessions with Staff data from payload
        foreach($sessions as $session) {
            $session->identity = "Unauthenticated Guest";
            $session->role = "Guest";
            
            // Check if it's a regular logged in user
            if ($session->user_id && isset($users[$session->user_id])) {
                $session->identity = $users[$session->user_id]->full_name . " (" . $users[$session->user_id]->email . ")";
                $session->role = "Admin/Owner";
            } else {
                // Try to decode session payload to find staff data
                try {
                    $payload = unserialize(base64_decode($session->payload));
                    if (isset($payload['staff_id'])) {
                        $staffMember = \App\Models\Staff::with('role')->find($payload['staff_id']);
                        if ($staffMember) {
                            $session->identity = $staffMember->full_name . " (" . $staffMember->email . ")";
                            $session->role = $staffMember->role ? $staffMember->role->name : "Staff";
                        }
                    }
                } catch (\Exception $e) {
                    // Ignore decoding errors
                }
            }
        }

        $staff = $this->getCurrentStaff();
        $currentSessionId = session()->getId();

        return view('admin.system.sessions', compact('sessions', 'staff', 'currentSessionId'));
    }

    /**
     * Terminate all unauthenticated guest sessions
     */
    public function clearGuestSessions()
    {
        if (!$this->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized. Super Admin required.');
        }

        // We use a chunk to be safe with large tables
        DB::table('sessions')->whereNull('user_id')->chunkById(200, function($sessions) {
            foreach ($sessions as $session) {
                try {
                    $payload = unserialize(base64_decode($session->payload));
                    // Only delete if NOT a staff member
                    if (!isset($payload['staff_id'])) {
                        DB::table('sessions')->where('id', $session->id)->delete();
                    }
                } catch (\Exception $e) {
                    // If decoding fails, it's likely a generic guest session anyway
                    DB::table('sessions')->where('id', $session->id)->delete();
                }
            }
        });
        
        return redirect()->back()->with('success', 'All inactive guest sessions have been cleared.');
    }

    /**
     * Terminate a specific session
     */
    public function killSession($id)
    {
        if (!$this->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized. Super Admin required.');
        }

        DB::table('sessions')->where('id', $id)->delete();
        
        return redirect()->back()->with('success', 'Session terminated successfully. The user will be logged out on their next action.');
    }

    /**
     * Helper to check if current user is a Super Admin
     */
    private function isSuperAdmin()
    {
        // Check if regular user (owner/admin)
        if (Auth::check()) {
            $user = Auth::user();
            // Check isAdmin method or property
            if (isset($user->isAdmin) && $user->isAdmin) return true;
            if (method_exists($user, 'isAdmin') && $user->isAdmin()) return true;
        }

        // Check if staff Super Admin
        if (session('is_staff') || session('staff_id')) {
            $staff = \App\Models\Staff::with('role')->find(session('staff_id'));
            if ($staff && $staff->role) {
                $roleName = strtolower($staff->role->name);
                $roleSlug = strtolower($staff->role->slug);
                return (str_contains($roleName, 'super admin') || str_contains($roleSlug, 'super-admin'));
            }
        }
        
        return false;
    }
}
