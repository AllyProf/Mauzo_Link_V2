<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarOrder extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'order_number',
        'table_id',
        'number_of_people',
        'customer_name',
        'customer_phone',
        'customer_location',
        'latitude',
        'longitude',
        'status',
        'payment_status',
        'total_amount',
        'paid_amount',
        'created_by',
        'waiter_id',
        'order_source',
        'served_by',
        'paid_by_waiter_id',
        'served_at',
        'notes',
        'payment_method',
        'mobile_money_number',
        'transaction_reference',
        'reconciliation_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'served_at' => 'datetime',
    ];

    /**
     * Generate a unique order number.
     * Format: ORD-01, ORD-02, ORD-03, etc.
     */
    public static function generateOrderNumber($userId)
    {
        $prefix = 'ORD';
        
        // Get the last order number for this user
        $lastOrder = self::where('user_id', $userId)
            ->where('order_number', 'like', $prefix . '-%')
            ->orderByRaw('CAST(SUBSTRING(order_number, 5) AS UNSIGNED) DESC')
            ->first();
        
        if ($lastOrder) {
            // Extract the number part (e.g., "ORD-47" -> "47")
            $lastNumber = (int) substr($lastOrder->order_number, 4); // Skip "ORD-"
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . str_pad($newNumber, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get the owner (user) that owns this order.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the table for this order.
     */
    public function table()
    {
        return $this->belongsTo(BarTable::class, 'table_id');
    }

    /**
     * Get all items for this order.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Get all kitchen order items (food items) for this order.
     */
    public function kitchenOrderItems()
    {
        return $this->hasMany(KitchenOrderItem::class, 'order_id');
    }

    /**
     * Get all payments for this order.
     */
    public function payments()
    {
        return $this->hasMany(BarPayment::class, 'order_id');
    }

    /**
     * Get all order payments (new payment system).
     */
    public function orderPayments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id');
    }

    /**
     * Get the daily reconciliation for this order.
     */
    public function reconciliation()
    {
        return $this->belongsTo(WaiterDailyReconciliation::class, 'reconciliation_id');
    }

    /**
     * Get the staff member who created the order.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the staff member who served the order.
     */
    public function servedBy()
    {
        return $this->belongsTo(User::class, 'served_by');
    }

    /**
     * Get the waiter (staff) who placed the order.
     */
    public function waiter()
    {
        return $this->belongsTo(Staff::class, 'waiter_id');
    }

    /**
     * Get the waiter (staff) who collected payment.
     */
    public function paidByWaiter()
    {
        return $this->belongsTo(Staff::class, 'paid_by_waiter_id');
    }

    /**
     * Check if order is pending.
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if order is paid.
     */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Get remaining amount to pay.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if order has food items (kitchen order items).
     */
    public function hasFoodItems()
    {
        // Use collection if already loaded, otherwise query
        if ($this->relationLoaded('kitchenOrderItems')) {
            return $this->kitchenOrderItems->count() > 0;
        }
        return $this->kitchenOrderItems()->count() > 0;
    }

    /**
     * Check if order has bar items (drinks).
     */
    public function hasBarItems()
    {
        // Use collection if already loaded, otherwise query
        if ($this->relationLoaded('items')) {
            return $this->items->count() > 0;
        }
        return $this->items()->count() > 0;
    }

    /**
     * Check if all food items in the order are ready (completed/taken).
     */
    public function isFoodOrderReady()
    {
        if (!$this->hasFoodItems()) {
            return true; // No food items, so considered "ready"
        }
        
        // Use collection if already loaded, otherwise query
        if ($this->relationLoaded('kitchenOrderItems')) {
            $totalFoodItems = $this->kitchenOrderItems->count();
            $completedFoodItems = $this->kitchenOrderItems
                ->where('status', 'completed')
                ->count();
        } else {
            $totalFoodItems = $this->kitchenOrderItems()->count();
            $completedFoodItems = $this->kitchenOrderItems()
                ->where('status', 'completed')
                ->count();
        }
        
        return $totalFoodItems === $completedFoodItems && $totalFoodItems > 0;
    }

    /**
     * Get payment readiness message explaining why payment cannot be recorded yet.
     */
    public function getPaymentReadinessMessage()
    {
        if ($this->payment_status === 'paid') {
            return 'Payment already recorded';
        }

        if ($this->status === 'cancelled') {
            return 'Cannot record payment for cancelled orders';
        }

        $hasFood = $this->hasFoodItems();
        $hasBar = $this->hasBarItems();

        // Bar order only
        if ($hasBar && !$hasFood) {
            if ($this->status !== 'served') {
                return 'Payment can be recorded after order is marked as served';
            }
            return null; // Ready for payment
        }

        // Food order only
        if ($hasFood && !$hasBar) {
            if (!$this->isFoodOrderReady()) {
                if ($this->relationLoaded('kitchenOrderItems')) {
                    $total = $this->kitchenOrderItems->count();
                    $completed = $this->kitchenOrderItems->where('status', 'completed')->count();
                } else {
                    $total = $this->kitchenOrderItems()->count();
                    $completed = $this->kitchenOrderItems()->where('status', 'completed')->count();
                }
                return "Payment can be recorded after all food items are taken. ({$completed}/{$total} items completed)";
            }
            return null; // Ready for payment
        }

        // Mixed order
        if ($hasFood && $hasBar) {
            $messages = [];
            
            if ($this->status !== 'served') {
                $messages[] = 'Drinks must be marked as served';
            }
            
            if (!$this->isFoodOrderReady()) {
                if ($this->relationLoaded('kitchenOrderItems')) {
                    $total = $this->kitchenOrderItems->count();
                    $completed = $this->kitchenOrderItems->where('status', 'completed')->count();
                } else {
                    $total = $this->kitchenOrderItems()->count();
                    $completed = $this->kitchenOrderItems()->where('status', 'completed')->count();
                }
                $messages[] = "All food items must be taken ({$completed}/{$total} completed)";
            }
            
            if (!empty($messages)) {
                return 'Payment can be recorded after: ' . implode(' AND ', $messages);
            }
            return null; // Ready for payment
        }

        return 'Order has no items';
    }

    /**
     * Check if payment can be recorded for this order.
     */
    public function canRecordPayment()
    {
        // Already paid? No
        if ($this->payment_status === 'paid') {
            return false;
        }

        // Cancelled? No
        if ($this->status === 'cancelled') {
            return false;
        }

        $hasFood = $this->hasFoodItems();
        $hasBar = $this->hasBarItems();

        // Bar order only
        if ($hasBar && !$hasFood) {
            return $this->status === 'served';
        }

        // Food order only
        if ($hasFood && !$hasBar) {
            return $this->isFoodOrderReady();
        }

        // Mixed order
        if ($hasFood && $hasBar) {
            return $this->status === 'served' && $this->isFoodOrderReady();
        }

        // Order with no items (shouldn't happen, but handle gracefully)
        return false;
    }

    /**
     * Boot the model and register event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // When order is created, update table status
        static::created(function ($order) {
            if ($order->table_id) {
                $table = \App\Models\BarTable::find($order->table_id);
                if ($table) {
                    $table->updateStatusFromOrders();
                }
            }
        });

        // When order is updated (payment status, order status), update table status
        static::updated(function ($order) {
            if ($order->table_id && ($order->isDirty('payment_status') || $order->isDirty('status'))) {
                $table = \App\Models\BarTable::find($order->table_id);
                if ($table) {
                    $table->updateStatusFromOrders();
                }
            }
        });

        // When order is deleted, update table status
        static::deleted(function ($order) {
            if ($order->table_id) {
                $table = \App\Models\BarTable::find($order->table_id);
                if ($table) {
                    $table->updateStatusFromOrders();
                }
            }
        });
    }
}
