<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receipt_number',
        'ingredient_id',
        'supplier_id',
        'quantity_received',
        'unit',
        'cost_per_unit',
        'total_cost',
        'expiry_date',
        'received_date',
        'batch_number',
        'location',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:2',
        'cost_per_unit' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'received_date' => 'date',
    ];

    /**
     * Generate a unique receipt number.
     */
    public static function generateReceiptNumber($userId)
    {
        $prefix = 'IR';
        $year = date('Y');
        $month = date('m');
        
        $lastReceipt = self::where('user_id', $userId)
            ->where('receipt_number', 'like', $prefix . $year . $month . '%')
            ->orderBy('receipt_number', 'desc')
            ->first();
        
        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the owner (user) that owns this receipt.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the ingredient.
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Get the supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the staff member who received this.
     */
    public function receivedByStaff()
    {
        return $this->belongsTo(Staff::class, 'received_by');
    }

    /**
     * Get all batches created from this receipt.
     */
    public function batches()
    {
        return $this->hasMany(IngredientBatch::class, 'ingredient_receipt_id');
    }
}
