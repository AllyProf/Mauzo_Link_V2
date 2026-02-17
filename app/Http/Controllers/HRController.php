<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HandlesStaffPermissions;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\PerformanceReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HRController extends Controller
{
    use HandlesStaffPermissions;

    /**
     * HR Dashboard
     */
    public function dashboard()
    {
        if (!$this->hasPermission('hr', 'view')) {
            abort(403, 'You do not have permission to access HR dashboard.');
        }

        $ownerId = $this->getOwnerId();
        $today = Carbon::today();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Statistics
        $totalStaff = Staff::where('user_id', $ownerId)->where('is_active', true)->count();
        $todayAttendance = Attendance::where('user_id', $ownerId)
            ->where('attendance_date', $today)
            ->where('status', 'present')
            ->count();
        $pendingLeaves = Leave::where('user_id', $ownerId)
            ->where('status', 'pending')
            ->count();
        $thisMonthPayrolls = Payroll::where('user_id', $ownerId)
            ->where('payroll_month', $currentMonth)
            ->where('payroll_year', $currentYear)
            ->count();

        // Recent attendance
        $recentAttendance = Attendance::where('user_id', $ownerId)
            ->with('staff')
            ->where('attendance_date', $today)
            ->orderBy('check_in_time', 'desc')
            ->limit(10)
            ->get();

        // Pending leave requests
        $pendingLeaveRequests = Leave::where('user_id', $ownerId)
            ->with('staff')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Attendance summary for this month
        $attendanceSummary = Attendance::where('user_id', $ownerId)
            ->whereMonth('attendance_date', $currentMonth)
            ->whereYear('attendance_date', $currentYear)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Upcoming birthdays (next 30 days)
        // Note: We'll need to add birth_date to staff table or use a different approach

        return view('hr.dashboard', compact(
            'totalStaff',
            'todayAttendance',
            'pendingLeaves',
            'thisMonthPayrolls',
            'recentAttendance',
            'pendingLeaveRequests',
            'attendanceSummary'
        ));
    }

    /**
     * Attendance Management
     */
    public function attendance(Request $request)
    {
        if (!$this->hasPermission('hr', 'view')) {
            abort(403, 'You do not have permission to view attendance.');
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $staffId = $request->get('staff_id');

        $query = Attendance::where('user_id', $ownerId)
            ->with('staff')
            ->where('attendance_date', $date);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendances = $query->orderBy('check_in_time', 'desc')->get();

        $staff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view('hr.attendance', compact('attendances', 'staff', 'date', 'staffId'));
    }

    /**
     * Get Attendance Records as JSON (for AJAX refresh)
     */
    public function getAttendanceJson(Request $request)
    {
        if (!$this->hasPermission('hr', 'view')) {
            return response()->json(['error' => 'You do not have permission to view attendance.'], 403);
        }

        $ownerId = $this->getOwnerId();
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $staffId = $request->get('staff_id');

        $query = Attendance::where('user_id', $ownerId)
            ->with('staff')
            ->where('attendance_date', $date);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $attendances = $query->orderBy('check_in_time', 'desc')->get();

        return response()->json([
            'success' => true,
            'attendances' => $attendances->map(function($attendance) {
                // Format working hours without decimals
                $hours = $attendance->working_hours;
                $wholeHours = floor($hours);
                $minutes = round(($hours - $wholeHours) * 60);
                $displayHours = '';
                if ($wholeHours > 0 && $minutes > 0) {
                    $displayHours = $wholeHours . 'h ' . $minutes . 'm';
                } elseif ($wholeHours > 0) {
                    $displayHours = $wholeHours . 'h';
                } elseif ($minutes > 0) {
                    $displayHours = $minutes . 'm';
                } else {
                    $displayHours = '0h';
                }
                
                return [
                    'id' => $attendance->id,
                    'staff_name' => $attendance->staff->full_name,
                    'check_in_time' => $attendance->check_in_time ? $attendance->check_in_time->format('H:i:s') : '-',
                    'check_out_time' => $attendance->check_out_time ? $attendance->check_out_time->format('H:i:s') : '-',
                    'working_hours' => $displayHours,
                    'status' => $attendance->status,
                    'is_biometric' => $attendance->is_biometric ?? false,
                ];
            }),
            'count' => $attendances->count()
        ]);
    }

    /**
     * Mark Attendance
     */
    public function markAttendance(Request $request)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to mark attendance.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'attendance_date' => 'required|date',
            'check_in_time' => 'nullable|date',
            'check_out_time' => 'nullable|date|after:check_in_time',
            'status' => 'required|in:present,absent,late,half_day,leave',
            'notes' => 'nullable|string',
        ]);

        // Verify staff belongs to owner
        $staff = Staff::where('id', $validated['staff_id'])
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $ownerId,
                'staff_id' => $validated['staff_id'],
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'check_in_time' => $validated['check_in_time'] ?? now(),
                'check_out_time' => $validated['check_out_time'] ?? null,
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
                'ip_address' => $request->ip(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully.',
            'attendance' => $attendance->load('staff')
        ]);
    }

    /**
     * Leave Management
     */
    public function leaves(Request $request)
    {
        if (!$this->hasPermission('hr', 'view')) {
            abort(403, 'You do not have permission to view leaves.');
        }

        $ownerId = $this->getOwnerId();
        $status = $request->get('status');
        $staffId = $request->get('staff_id');

        $query = Leave::where('user_id', $ownerId)
            ->with(['staff', 'approver']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $leaves = $query->orderBy('created_at', 'desc')->paginate(20);

        $staff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view('hr.leaves', compact('leaves', 'staff', 'status', 'staffId'));
    }

    /**
     * Approve/Reject Leave
     */
    public function updateLeaveStatus(Request $request, Leave $leave)
    {
        if (!$this->hasPermission('hr', 'edit')) {
            return response()->json(['error' => 'You do not have permission to update leave status.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        if ($leave->user_id !== $ownerId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $leave->update([
            'status' => $validated['status'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Leave status updated successfully.',
            'leave' => $leave->load(['staff', 'approver'])
        ]);
    }

    /**
     * Payroll Management
     */
    public function payroll(Request $request)
    {
        if (!$this->hasPermission('hr', 'view')) {
            abort(403, 'You do not have permission to view payroll.');
        }

        $ownerId = $this->getOwnerId();
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        $staffId = $request->get('staff_id');

        $query = Payroll::where('user_id', $ownerId)
            ->where('payroll_month', $month)
            ->where('payroll_year', $year)
            ->with('staff');

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $payrolls = $query->orderBy('staff_id')->get();

        $staff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        // Calculate totals
        $totalGross = $payrolls->sum('gross_salary');
        $totalDeductions = $payrolls->sum('total_deductions');
        $totalNet = $payrolls->sum('net_salary');

        return view('hr.payroll', compact('payrolls', 'staff', 'month', 'year', 'staffId', 'totalGross', 'totalDeductions', 'totalNet'));
    }

    /**
     * Generate Payroll
     */
    public function generatePayroll(Request $request)
    {
        if (!$this->hasPermission('hr', 'create')) {
            return response()->json(['error' => 'You do not have permission to generate payroll.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'payroll_month' => 'required|integer|min:1|max:12',
            'payroll_year' => 'required|integer|min:2020|max:2100',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|array',
            'deductions' => 'nullable|array',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'advance_payment' => 'nullable|numeric|min:0',
        ]);

        // Verify staff belongs to owner
        $staff = Staff::where('id', $validated['staff_id'])
            ->where('user_id', $ownerId)
            ->firstOrFail();

        // Check if payroll already exists
        $existingPayroll = Payroll::where('user_id', $ownerId)
            ->where('staff_id', $validated['staff_id'])
            ->where('payroll_month', $validated['payroll_month'])
            ->where('payroll_year', $validated['payroll_year'])
            ->first();

        if ($existingPayroll) {
            return response()->json(['error' => 'Payroll already exists for this staff and period.'], 400);
        }

        // Calculate overtime amount
        $overtimeAmount = ($validated['overtime_hours'] ?? 0) * ($validated['overtime_rate'] ?? 0);

        // Calculate gross salary
        $allowancesTotal = 0;
        if (isset($validated['allowances']) && is_array($validated['allowances'])) {
            $allowancesTotal = array_sum(array_column($validated['allowances'], 'amount'));
        }

        $grossSalary = $validated['basic_salary'] 
            + $allowancesTotal 
            + $overtimeAmount 
            + ($validated['bonus'] ?? 0);

        // Calculate deductions
        $deductionsTotal = 0;
        if (isset($validated['deductions']) && is_array($validated['deductions'])) {
            $deductionsTotal = array_sum(array_column($validated['deductions'], 'amount'));
        }
        $totalDeductions = $deductionsTotal + ($validated['advance_payment'] ?? 0);

        // Calculate net salary
        $netSalary = $grossSalary - $totalDeductions;

        $payroll = Payroll::create([
            'user_id' => $ownerId,
            'staff_id' => $validated['staff_id'],
            'payroll_month' => $validated['payroll_month'],
            'payroll_year' => $validated['payroll_year'],
            'basic_salary' => $validated['basic_salary'],
            'allowances' => $validated['allowances'] ?? [],
            'deductions' => $validated['deductions'] ?? [],
            'overtime_hours' => $validated['overtime_hours'] ?? 0,
            'overtime_rate' => $validated['overtime_rate'] ?? 0,
            'overtime_amount' => $overtimeAmount,
            'bonus' => $validated['bonus'] ?? 0,
            'advance_payment' => $validated['advance_payment'] ?? 0,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payroll generated successfully.',
            'payroll' => $payroll->load('staff')
        ]);
    }

    /**
     * Performance Reviews
     */
    public function performanceReviews(Request $request)
    {
        if (!$this->hasPermission('hr', 'view')) {
            abort(403, 'You do not have permission to view performance reviews.');
        }

        $ownerId = $this->getOwnerId();
        $staffId = $request->get('staff_id');

        $query = PerformanceReview::where('user_id', $ownerId)
            ->with(['staff', 'reviewer']);

        if ($staffId) {
            $query->where('staff_id', $staffId);
        }

        $reviews = $query->orderBy('review_date', 'desc')->paginate(20);

        $staff = Staff::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        return view('hr.performance-reviews', compact('reviews', 'staff', 'staffId'));
    }

    /**
     * Create Performance Review
     */
    public function storePerformanceReview(Request $request)
    {
        if (!$this->hasPermission('hr', 'create')) {
            return response()->json(['error' => 'You do not have permission to create performance reviews.'], 403);
        }

        $ownerId = $this->getOwnerId();
        
        $validated = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'review_period_start' => 'required|date',
            'review_period_end' => 'required|date|after:review_period_start',
            'review_date' => 'required|date',
            'reviewer_id' => 'nullable|exists:staff,id',
            'performance_rating' => 'required|numeric|min:1|max:5',
            'goals_achieved' => 'nullable|string',
            'goals_pending' => 'nullable|string',
            'strengths' => 'nullable|string',
            'areas_for_improvement' => 'nullable|string',
            'training_needs' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'next_review_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Verify staff belongs to owner
        $staff = Staff::where('id', $validated['staff_id'])
            ->where('user_id', $ownerId)
            ->firstOrFail();

        $review = PerformanceReview::create([
            'user_id' => $ownerId,
            'staff_id' => $validated['staff_id'],
            'review_period_start' => $validated['review_period_start'],
            'review_period_end' => $validated['review_period_end'],
            'review_date' => $validated['review_date'],
            'reviewer_id' => $validated['reviewer_id'] ?? auth()->id(),
            'performance_rating' => $validated['performance_rating'],
            'goals_achieved' => $validated['goals_achieved'] ?? null,
            'goals_pending' => $validated['goals_pending'] ?? null,
            'strengths' => $validated['strengths'] ?? null,
            'areas_for_improvement' => $validated['areas_for_improvement'] ?? null,
            'training_needs' => $validated['training_needs'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'next_review_date' => $validated['next_review_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Performance review created successfully.',
            'review' => $review->load(['staff', 'reviewer'])
        ]);
    }
}

