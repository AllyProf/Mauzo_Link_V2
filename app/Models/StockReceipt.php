<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_variant_id',
        'supplier_id',
        'receipt_number',
        'quantity_received',
        'total_units',
        'buying_price_per_unit',
        'selling_price_per_unit',
        'selling_price_per_tot',
        'total_buying_cost',
        'total_selling_value',
        'profit_per_unit',
        'total_profit',
        'discount_type',
        'discount_amount',
        'discount_value',
        'final_buying_cost',
        'received_date',
        'expiry_date',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'total_units' => 'integer',
        'buying_price_per_unit' => 'decimal:2',
        'selling_price_per_unit' => 'decimal:2',
        'selling_price_per_tot' => 'decimal:2',
        'total_buying_cost' => 'decimal:2',
        'total_selling_value' => 'decimal:2',
        'profit_per_unit' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'final_buying_cost' => 'decimal:2',
        'received_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Generate a unique receipt number.
     */
    public static function generateReceiptNumber($userId)
    {
        $prefix = 'SR';
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
     * Get the product variant for this receipt.
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the supplier for this receipt.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the staff member who received the stock.
     */
    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
