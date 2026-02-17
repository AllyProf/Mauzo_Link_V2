<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'staff_id',
        'payroll_month',
        'payroll_year',
        'basic_salary',
        'allowances', // JSON array of allowances
        'deductions', // JSON array of deductions
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'bonus',
        'advance_payment',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'payment_method', // cash, bank_transfer, mobile_money
        'payment_date',
        'payment_status', // pending, paid, failed
        'transaction_reference',
        'notes',
    ];

    protected $casts = [
        'payroll_month' => 'integer',
        'payroll_year' => 'integer',
        'basic_salary' => 'decimal:2',
        'allowances' => 'array',
        'deductions' => 'array',
        'overtime_hours' => 'decimal:2',
        'overtime_rate' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the owner (user) who manages this payroll
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the staff member
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Calculate gross salary
     */
    public function calculateGrossSalary()
    {
        $allowancesTotal = is_array($this->allowances) 
            ? array_sum(array_column($this->allowances, 'amount')) 
            : 0;
        
        return $this->basic_salary 
            + $allowancesTotal 
            + $this->overtime_amount 
            + $this->bonus;
    }

    /**
     * Calculate total deductions
     */
    public function calculateTotalDeductions()
    {
        $deductionsTotal = is_array($this->deductions) 
            ? array_sum(array_column($this->deductions, 'amount')) 
            : 0;
        
        return $deductionsTotal + $this->advance_payment;
    }

    /**
     * Calculate net salary
     */
    public function calculateNetSalary()
    {
        return $this->gross_salary - $this->total_deductions;
    }
}

