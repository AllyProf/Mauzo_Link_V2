<?php

namespace App\Services;

use App\Models\WaiterSmsNotification;
use App\Models\BarOrder;
use App\Models\Staff;

class WaiterSmsService
{
    protected $smsService;

    public function __construct()
    {
        $this->smsService = new SmsService();
    }

    /**
     * Send order notification SMS to waiter
     */
    public function sendOrderNotification(BarOrder $order)
    {
        $waiter = $order->waiter;
        
        if (!$waiter || !$waiter->phone_number) {
            \Log::warning('Cannot send SMS: Waiter or phone number missing', [
                'order_id' => $order->id,
                'waiter_id' => $order->waiter_id
            ]);
            return false;
        }

        // Build message
        $items = [];
        
        // Add drink items
        foreach ($order->items as $item) {
            $productName = $item->productVariant->product->name ?? 'Item';
            $items[] = $item->quantity . 'x ' . $productName;
        }
        
        // Add food items
        foreach ($order->kitchenOrderItems as $item) {
            $variant = $item->variant_name ? ' (' . $item->variant_name . ')' : '';
            $items[] = $item->quantity . 'x ' . $item->food_item_name . $variant;
        }

        $message = "New Order #{$order->order_number}\n";
        $message .= "Items: " . implode(', ', $items) . "\n";
        $message .= "Total: TSh " . number_format($order->total_amount, 0) . "\n";
        
        if ($order->table) {
            $message .= "Table: {$order->table->table_number}\n";
        }
        
        if ($order->customer_name) {
            $message .= "Customer: {$order->customer_name}\n";
        }
        
        $message .= "Time: " . $order->created_at->format('H:i');

        // Send SMS
        $result = $this->smsService->sendSms($waiter->phone_number, $message);

        // Log notification
        WaiterSmsNotification::create([
            'waiter_id' => $waiter->id,
            'order_id' => $order->id,
            'phone_number' => $waiter->phone_number,
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
        ]);

        \Log::info('Waiter SMS notification sent', [
            'waiter_id' => $waiter->id,
            'order_id' => $order->id,
            'success' => $result['success']
        ]);

        return $result['success'];
    }

    /**
     * Send payment confirmation SMS to waiter
     */
    public function sendPaymentNotification(BarOrder $order, $paymentMethod, $amount)
    {
        $waiter = $order->waiter;
        
        if (!$waiter || !$waiter->phone_number) {
            return false;
        }

        $methodName = ucfirst(str_replace('_', ' ', $paymentMethod));
        $message = "Payment received for Order #{$order->order_number}\n";
        $message .= "Amount: TSh " . number_format($amount, 0) . "\n";
        $message .= "Method: {$methodName}\n";
        $message .= "Time: " . now()->format('H:i');

        $result = $this->smsService->sendSms($waiter->phone_number, $message);

        WaiterSmsNotification::create([
            'waiter_id' => $waiter->id,
            'order_id' => $order->id,
            'phone_number' => $waiter->phone_number,
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
        ]);

        return $result['success'];
    }

    /**
     * Send food ready notification SMS to waiter
     */
    public function sendFoodReadyNotification($kitchenOrderItem)
    {
        $order = $kitchenOrderItem->order;
        $waiter = $order->waiter;
        
        if (!$waiter || !$waiter->phone_number) {
            \Log::warning('Cannot send ready SMS: Waiter or phone number missing', [
                'order_id' => $order->id,
                'waiter_id' => $order->waiter_id,
                'item_id' => $kitchenOrderItem->id
            ]);
            return false;
        }

        $variant = $kitchenOrderItem->variant_name ? ' (' . $kitchenOrderItem->variant_name . ')' : '';
        $message = "Food Ready! Order #{$order->order_number}\n";
        $message .= "Item: {$kitchenOrderItem->quantity}x {$kitchenOrderItem->food_item_name}{$variant}\n";
        
        if ($order->table) {
            $message .= "Table: {$order->table->table_number}\n";
        }
        
        $message .= "Please pick up from kitchen.\n";
        $message .= "Time: " . now()->format('H:i');

        $result = $this->smsService->sendSms($waiter->phone_number, $message);

        WaiterSmsNotification::create([
            'waiter_id' => $waiter->id,
            'order_id' => $order->id,
            'phone_number' => $waiter->phone_number,
            'message' => $message,
            'status' => $result['success'] ? 'sent' : 'failed',
            'sent_at' => $result['success'] ? now() : null,
            'error_message' => $result['success'] ? null : ($result['error'] ?? 'Unknown error')
        ]);

        \Log::info('Food ready SMS notification sent', [
            'waiter_id' => $waiter->id,
            'order_id' => $order->id,
            'item_id' => $kitchenOrderItem->id,
            'success' => $result['success']
        ]);

        return $result['success'];
    }

    /**
     * Send order confirmation SMS to customer
     */
    public function sendCustomerOrderConfirmation(BarOrder $order)
    {
        if (!$order->customer_phone) {
            \Log::info('No customer phone number provided for order', [
                'order_id' => $order->id
            ]);
            return false;
        }

        // Build message
        $items = [];
        
        // Add drink items
        foreach ($order->items as $item) {
            $productName = $item->productVariant->product->name ?? 'Item';
            $items[] = $item->quantity . 'x ' . $productName;
        }
        
        // Add food items
        foreach ($order->kitchenOrderItems as $item) {
            $variant = $item->variant_name ? ' (' . $item->variant_name . ')' : '';
            $items[] = $item->quantity . 'x ' . $item->food_item_name . $variant;
        }

        $message = "Thank you for your order!\n";
        $message .= "Order #{$order->order_number}\n";
        $message .= "Items: " . implode(', ', $items) . "\n";
        $message .= "Total: TSh " . number_format($order->total_amount, 0) . "\n";
        
        if ($order->table) {
            $message .= "Table: {$order->table->table_number}\n";
        }
        
        $message .= "We'll notify you when your order is ready.\n";
        $message .= "Time: " . $order->created_at->format('H:i');

        $result = $this->smsService->sendSms($order->customer_phone, $message);

        \Log::info('Customer order confirmation SMS sent', [
            'order_id' => $order->id,
            'customer_phone' => $order->customer_phone,
            'success' => $result['success']
        ]);

        return $result['success'];
    }

    /**
     * Send food ready notification SMS to customer
     */
    public function sendCustomerFoodReadyNotification($kitchenOrderItem)
    {
        $order = $kitchenOrderItem->order;
        
        if (!$order->customer_phone) {
            \Log::info('No customer phone number for food ready notification', [
                'order_id' => $order->id,
                'item_id' => $kitchenOrderItem->id
            ]);
            return false;
        }

        $variant = $kitchenOrderItem->variant_name ? ' (' . $kitchenOrderItem->variant_name . ')' : '';
        $message = "Your order is ready! 🍽️\n";
        $message .= "Order #{$order->order_number}\n";
        $message .= "Item: {$kitchenOrderItem->quantity}x {$kitchenOrderItem->food_item_name}{$variant}\n";
        
        if ($order->table) {
            $message .= "Table: {$order->table->table_number}\n";
        }
        
        $message .= "Your waiter will bring it to you shortly.\n";
        $message .= "Time: " . now()->format('H:i');

        $result = $this->smsService->sendSms($order->customer_phone, $message);

        \Log::info('Customer food ready SMS sent', [
            'order_id' => $order->id,
            'item_id' => $kitchenOrderItem->id,
            'customer_phone' => $order->customer_phone,
            'success' => $result['success']
        ]);

        return $result['success'];
    }

    /**
     * Send payment thank you SMS to customer
     */
    public function sendCustomerPaymentThankYou(BarOrder $order, $paymentMethod, $amount)
    {
        if (!$order->customer_phone) {
            \Log::info('No customer phone number for payment thank you', [
                'order_id' => $order->id
            ]);
            return false;
        }

        $methodName = ucfirst(str_replace('_', ' ', $paymentMethod));
        $message = "Thank you for your payment! 🙏\n";
        $message .= "Order #{$order->order_number}\n";
        $message .= "Amount: TSh " . number_format($amount, 0) . "\n";
        $message .= "Payment Method: {$methodName}\n";
        $message .= "We hope you enjoyed your meal!\n";
        $message .= "Time: " . now()->format('H:i');

        $result = $this->smsService->sendSms($order->customer_phone, $message);

        \Log::info('Customer payment thank you SMS sent', [
            'order_id' => $order->id,
            'customer_phone' => $order->customer_phone,
            'success' => $result['success']
        ]);

        return $result['success'];
    }
}


