<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'order_item_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the stock transfer this sale belongs to.
     */
    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * Get the order item this sale belongs to.
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
