<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockMovement;
use App\Models\TransferSale;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferSaleService
{
    /**
     * Attribute order items to stock transfers using FIFO (First In First Out)
     * 
     * @param OrderItem $orderItem
     * @param int $ownerId
     * @return void
     */
    public function attributeSaleToTransfer(OrderItem $orderItem, $ownerId)
    {
        $productVariantId = $orderItem->product_variant_id;
        $quantityNeeded = $orderItem->quantity;
        
        // If it's a tot sale, calculate fractional bottle quantity for transfer attribution
        if (($orderItem->sell_type ?? 'unit') === 'tot') {
            $variant = $orderItem->productVariant ?: \App\Models\ProductVariant::find($productVariantId);
            $totsPerBottle = ($variant && $variant->total_tots) ? $variant->total_tots : 1;
            $quantityNeeded = $orderItem->quantity / $totsPerBottle;
        }
        
        // Get all stock movements that added stock to counter for this variant
        // Ordered by date (oldest first - FIFO)
        $stockMovements = StockMovement::where('user_id', $ownerId)
            ->where('product_variant_id', $productVariantId)
            ->where('to_location', 'counter')
            ->where('movement_type', 'transfer') // Only transfers
            ->where('reference_type', StockTransfer::class)
            ->orderBy('created_at', 'asc')
            ->with('reference')
            ->get();
        
        $remainingQuantity = $quantityNeeded;
        
        foreach ($stockMovements as $movement) {
            if ($remainingQuantity <= 0) {
                break;
            }
            
            $transfer = $movement->reference;
            if (!$transfer || $transfer->status !== 'completed') {
                continue;
            }
            
            // Get how many units from this transfer are still available (not yet sold)
            $soldFromTransfer = TransferSale::where('stock_transfer_id', $transfer->id)
                ->sum('quantity');
            
            $availableFromTransfer = $movement->quantity - $soldFromTransfer;
            
            if ($availableFromTransfer <= 0) {
                continue; // This transfer is already fully sold
            }
            
            // Calculate how many units to attribute from this transfer
            $quantityToAttribute = min($remainingQuantity, $availableFromTransfer);
            
            // Create transfer sale record
            TransferSale::create([
                'stock_transfer_id' => $transfer->id,
                'order_item_id' => $orderItem->id,
                'quantity' => $quantityToAttribute,
                'unit_price' => $orderItem->unit_price,
                'total_price' => $quantityToAttribute * $orderItem->unit_price,
            ]);
            
            $remainingQuantity -= $quantityToAttribute;
            
            // Check if this transfer is now fully sold
            $this->checkTransferCompletion($transfer, $ownerId);
        }
        
        // If there's still remaining quantity, it means some stock came from direct additions
        // (not from transfers) - we don't track those for transfer reconciliation
        if ($remainingQuantity > 0) {
            Log::info('Some order items could not be attributed to transfers', [
                'order_item_id' => $orderItem->id,
                'remaining_quantity' => $remainingQuantity,
                'product_variant_id' => $productVariantId,
            ]);
        }
    }
    
    /**
     * Check if a transfer is fully sold and send SMS notifications
     * 
     * @param StockTransfer $transfer
     * @param int $ownerId
     * @return void
     */
    public function checkTransferCompletion(StockTransfer $transfer, $ownerId)
    {
        // Get total sold quantity for this transfer
        $soldQuantity = TransferSale::where('stock_transfer_id', $transfer->id)
            ->sum('quantity');
        
        // Check if fully sold
        if ($soldQuantity >= $transfer->total_units) {
            // Check if we've already sent notification (to avoid duplicate SMS)
            // Check if notes contain "Completion SMS sent" to avoid duplicates
            $notes = $transfer->notes ?? '';
            $notificationSent = strpos($notes, 'Completion SMS sent') !== false;
            
            if (!$notificationSent) {
                // Send SMS notifications
                try {
                    $smsService = new StockTransferSmsService();
                    $smsService->sendTransferFullySoldNotification($transfer, $ownerId);
                    
                    // Mark transfer as having sent completion SMS
                    $transfer->refresh(); // Reload to get latest data
                    $transfer->update([
                        'notes' => ($transfer->notes ? $transfer->notes . ' | ' : '') . 'Completion SMS sent at ' . now()->format('Y-m-d H:i:s'),
                    ]);
                    
                    Log::info('Transfer fully sold - SMS notifications sent', [
                        'transfer_id' => $transfer->id,
                        'transfer_number' => $transfer->transfer_number,
                        'sold_quantity' => $soldQuantity,
                        'total_units' => $transfer->total_units,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send transfer completion SMS', [
                        'transfer_id' => $transfer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
    
    /**
     * Get sold quantity for a transfer
     * 
     * @param StockTransfer $transfer
     * @return int
     */
    public function getSoldQuantity(StockTransfer $transfer)
    {
        return TransferSale::where('stock_transfer_id', $transfer->id)
            ->sum('quantity');
    }
}

