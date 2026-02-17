<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarTable extends Model
{
    use HasFactory;

    protected $table = 'bar_tables';

    protected $fillable = [
        'user_id',
        'table_number',
        'table_name',
        'capacity',
        'status',
        'location',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner (user) that owns this table.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all orders for this table.
     */
    public function orders()
    {
        return $this->hasMany(BarOrder::class, 'table_id');
    }

    /**
     * Get active orders for this table.
     */
    public function activeOrders()
    {
        return $this->hasMany(BarOrder::class, 'table_id')
            ->whereIn('status', ['pending', 'preparing', 'served'])
            ->where('payment_status', '!=', 'paid');
    }

    /**
     * Check if table is available.
     */
    public function isAvailable()
    {
        return $this->status === 'available' && $this->is_active;
    }

    /**
     * Check if table is occupied.
     */
    public function isOccupied()
    {
        return $this->status === 'occupied';
    }

    /**
     * Get remaining capacity (seats available) for this table.
     * Calculates: capacity - sum of people in active orders
     */
    public function getRemainingCapacityAttribute()
    {
        // Get total number of people in active orders
        // Use COALESCE to handle null values (waiter orders might not have number_of_people)
        $peopleInActiveOrders = $this->activeOrders()
            ->selectRaw('COALESCE(SUM(number_of_people), 0) as total')
            ->value('total') ?? 0;
        
        // If no people count, assume at least 1 person per active order
        if ($peopleInActiveOrders == 0) {
            $peopleInActiveOrders = $this->activeOrders()->count();
        }
        
        // Remaining capacity = total capacity - people in active orders
        $remaining = $this->capacity - $peopleInActiveOrders;
        
        // Return 0 if negative (over capacity)
        return max(0, $remaining);
    }

    /**
     * Get total people currently at this table (from active orders).
     */
    public function getCurrentPeopleAttribute()
    {
        // Use COALESCE to handle null values
        $total = $this->activeOrders()
            ->selectRaw('COALESCE(SUM(number_of_people), 0) as total')
            ->value('total') ?? 0;
        
        // If no people count, return count of active orders (at least 1 person per order)
        if ($total == 0) {
            $total = $this->activeOrders()->count();
        }
        
        return $total;
    }

    /**
     * Check if table has available seats.
     */
    public function hasAvailableSeats($requiredSeats = 1)
    {
        return $this->remaining_capacity >= $requiredSeats;
    }

    /**
     * Update table status based on active orders.
     * Sets status to 'occupied' if there are active orders, 'available' otherwise.
     */
    public function updateStatusFromOrders()
    {
        $activeOrdersCount = $this->activeOrders()->count();
        
        if ($activeOrdersCount > 0) {
            // Table has active orders, mark as occupied
            if ($this->status !== 'occupied') {
                $this->update(['status' => 'occupied']);
            }
        } else {
            // No active orders, mark as available
            if ($this->status !== 'available' && $this->is_active) {
                $this->update(['status' => 'available']);
            }
        }
    }
}
