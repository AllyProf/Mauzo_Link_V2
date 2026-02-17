<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'supplier_type',
        'company_name',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the owner (user) that owns the supplier.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all products from this supplier.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all stock receipts from this supplier.
     */
    public function stockReceipts()
    {
        return $this->hasMany(StockReceipt::class);
    }
}
