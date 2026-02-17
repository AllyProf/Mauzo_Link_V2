# Waiter Reconciliation & Payment System - Implementation Proposal

## Executive Summary

This document outlines a comprehensive system for tracking waiter sales, end-of-day reconciliation, SMS notifications, and mobile money (Lipa Number) payment integration.

---

## 1. System Architecture Overview

### 1.1 Core Components

1. **Waiter Sales Tracking**
   - Track all orders (bar & restaurant) by waiter
   - Real-time sales dashboard
   - Product-wise breakdown

2. **End-of-Day Reconciliation**
   - Daily sales summary per waiter
   - Cash vs Mobile Money breakdown
   - Reconciliation reports for Counter and Chef

3. **SMS Notification System**
   - Automatic SMS to waiter for each order
   - Order confirmation with details
   - Payment confirmation

4. **Mobile Money Integration (Lipa Number)**
   - M-Pesa/Lipa na M-Pesa integration
   - Payment verification
   - Transaction tracking

---

## 2. Database Schema Changes

### 2.1 New Tables Required

#### `waiter_daily_reconciliations`
```sql
- id
- user_id (restaurant owner)
- waiter_id (staff)
- reconciliation_date
- total_sales (decimal)
- cash_collected (decimal)
- mobile_money_collected (decimal)
- expected_amount (decimal)
- submitted_amount (decimal)
- difference (decimal)
- status (pending, submitted, verified, disputed)
- submitted_at (timestamp)
- verified_by (user_id)
- verified_at (timestamp)
- notes (text)
- created_at, updated_at
```

#### `order_payments`
```sql
- id
- order_id
- payment_method (cash, mobile_money, card, bank_transfer)
- amount (decimal)
- mobile_money_number (string, nullable) - Customer's phone
- transaction_reference (string, nullable) - M-Pesa transaction code
- transaction_id (string, nullable) - M-Pesa transaction ID
- payment_status (pending, verified, failed, refunded)
- verified_at (timestamp)
- verified_by (user_id, nullable)
- notes (text, nullable)
- created_at, updated_at
```

#### `waiter_sms_notifications`
```sql
- id
- waiter_id
- order_id
- phone_number
- message
- status (pending, sent, failed)
- sent_at (timestamp)
- error_message (text, nullable)
- created_at, updated_at
```

### 2.2 Modifications to Existing Tables

#### `orders` table - Add columns:
```sql
- payment_method (enum: cash, mobile_money, card, bank_transfer)
- mobile_money_number (string, nullable)
- transaction_reference (string, nullable)
- reconciliation_id (foreign key, nullable)
```

#### `staff` table - Add columns:
```sql
- phone_number (string) - For SMS notifications
- lipa_number (string, nullable) - Restaurant's M-Pesa number
```

---

## 3. Feature Implementation Plan

### 3.1 Waiter Sales Dashboard

**Location:** `/bar/waiter/sales` or `/bar/waiter/reconciliation`

**Features:**
- Daily sales summary
- List of all orders for the day
- Cash vs Mobile Money breakdown
- Submit reconciliation button
- View previous reconciliations

**Data to Display:**
- Total orders count
- Total sales amount
- Cash collected
- Mobile Money collected
- Orders list (order number, customer, items, amount, payment method, time)

### 3.2 Counter Reconciliation Page

**Location:** `/bar/counter/reconciliation`

**Features:**
- List all waiters with sales (highest to lowest)
- Each waiter shows:
  - Total sales
  - Number of orders
  - Cash collected
  - Mobile Money collected
  - Expected amount
  - Submitted amount
  - Difference
  - Status (Pending/Submitted/Verified)
- Click on waiter to see all their orders
- Verify reconciliation button
- Export to PDF/Excel

**Sorting:**
- Default: Highest sales first
- Options: By name, by orders count, by date

### 3.3 Chef Reconciliation Page

**Location:** `/bar/chef/reconciliation` (or same as counter)

**Features:**
- Similar to Counter page
- Focus on food orders only
- Kitchen performance metrics

### 3.4 SMS Notification System

**When to Send SMS:**
1. **Order Placed** - Immediately when waiter creates order
   - Message: "New Order #ORD-123: 2x Grilled Chicken, 1x Pepsi. Total: TSh 15,000. Table: 5"
   
2. **Payment Received** - When payment is recorded
   - Message: "Payment received for Order #ORD-123: TSh 15,000 (Cash/Mobile Money)"

3. **Daily Summary** - End of day (optional)
   - Message: "Daily Summary: 25 orders, Total: TSh 450,000. Cash: TSh 300,000, Mobile Money: TSh 150,000"

**SMS Template Variables:**
- `{order_number}` - Order number
- `{items}` - List of items
- `{total_amount}` - Total amount
- `{table_number}` - Table number (if applicable)
- `{payment_method}` - Payment method
- `{waiter_name}` - Waiter name

### 3.5 Mobile Money (Lipa Number) Integration

**Implementation Options:**

#### Option A: Manual Entry (Recommended for Start)
- Waiter enters customer's phone number
- Customer receives M-Pesa prompt
- Waiter enters transaction reference/confirmation code
- System marks payment as "pending verification"
- Manager verifies payment manually

#### Option B: API Integration (Advanced)
- Integrate with M-Pesa API (Daraja API)
- Real-time payment verification
- Automatic payment confirmation
- Webhook for payment notifications

**Recommended Approach:**
- **Phase 1:** Manual entry with transaction reference
- **Phase 2:** Add M-Pesa API integration for automatic verification

**Payment Flow:**
1. Customer selects "Mobile Money" payment
2. Waiter enters customer's phone number
3. System displays restaurant's Lipa Number
4. Customer pays via M-Pesa
5. Waiter enters transaction reference (e.g., "QGH7X8Y9Z")
6. System stores payment as "pending verification"
7. Manager verifies payment against M-Pesa statement
8. Payment marked as "verified"

---

## 4. Implementation Steps

### Phase 1: Database & Models (Week 1)
1. Create migrations for new tables
2. Create Eloquent models
3. Add relationships
4. Update existing models

### Phase 2: Waiter Sales Dashboard (Week 1-2)
1. Create controller methods
2. Create views
3. Add routes
4. Implement daily sales calculation
5. Add submit reconciliation functionality

### Phase 3: Counter Reconciliation Page (Week 2)
1. Create reconciliation controller
2. Build reconciliation view with waiter list
3. Implement sorting and filtering
4. Add order details modal
5. Add verification functionality

### Phase 4: SMS Integration (Week 2-3)
1. Create SMS notification service
2. Add SMS sending on order creation
3. Add SMS sending on payment
4. Create SMS templates
5. Add SMS notification log

### Phase 5: Mobile Money Integration (Week 3-4)
1. Add payment method selection
2. Create mobile money payment form
3. Add transaction reference input
4. Create payment verification page
5. Add payment status tracking

### Phase 6: Reporting & Export (Week 4)
1. Add PDF export for reconciliations
2. Add Excel export
3. Create daily/weekly/monthly reports
4. Add charts and graphs

---

## 5. Detailed Feature Specifications

### 5.1 Waiter Sales Dashboard

**Route:** `bar.waiter.sales` or `bar.waiter.reconciliation`

**Controller Method:**
```php
public function salesDashboard(Request $request)
{
    $waiter = $this->getCurrentStaff();
    $ownerId = $this->getOwnerId();
    $date = $request->get('date', now()->format('Y-m-d'));
    
    // Get all orders for this waiter on this date
    $orders = BarOrder::where('user_id', $ownerId)
        ->where('waiter_id', $waiter->id)
        ->whereDate('created_at', $date)
        ->with(['items', 'kitchenOrderItems', 'table', 'payments'])
        ->get();
    
    // Calculate totals
    $totalSales = $orders->sum('total_amount');
    $cashCollected = $orders->where('payment_method', 'cash')->sum('paid_amount');
    $mobileMoneyCollected = $orders->where('payment_method', 'mobile_money')->sum('paid_amount');
    $totalOrders = $orders->count();
    
    // Check if reconciliation already submitted
    $reconciliation = WaiterDailyReconciliation::where('waiter_id', $waiter->id)
        ->where('reconciliation_date', $date)
        ->first();
    
    return view('bar.waiter.sales', compact('orders', 'totalSales', 'cashCollected', 'mobileMoneyCollected', 'totalOrders', 'date', 'reconciliation'));
}
```

**View Features:**
- Date selector
- Sales summary cards
- Orders table
- Submit reconciliation button (if not submitted)
- View submitted reconciliation (if submitted)

### 5.2 Counter Reconciliation Page

**Route:** `bar.counter.reconciliation`

**Controller Method:**
```php
public function reconciliation(Request $request)
{
    $ownerId = $this->getOwnerId();
    $date = $request->get('date', now()->format('Y-m-d'));
    
    // Get all waiters with their sales for the date
    $waiters = Staff::where('user_id', $ownerId)
        ->whereHas('role', function($q) {
            $q->where('name', 'Waiter');
        })
        ->with(['dailyReconciliations' => function($q) use ($date) {
            $q->where('reconciliation_date', $date);
        }])
        ->get()
        ->map(function($waiter) use ($ownerId, $date) {
            $orders = BarOrder::where('user_id', $ownerId)
                ->where('waiter_id', $waiter->id)
                ->whereDate('created_at', $date)
                ->get();
            
            return [
                'waiter' => $waiter,
                'total_sales' => $orders->sum('total_amount'),
                'total_orders' => $orders->count(),
                'cash_collected' => $orders->where('payment_method', 'cash')->sum('paid_amount'),
                'mobile_money_collected' => $orders->where('payment_method', 'mobile_money')->sum('paid_amount'),
                'orders' => $orders,
                'reconciliation' => $waiter->dailyReconciliations->first()
            ];
        })
        ->sortByDesc('total_sales')
        ->values();
    
    return view('bar.counter.reconciliation', compact('waiters', 'date'));
}
```

**View Features:**
- Date selector
- Waiter list table (sortable)
- Expandable rows to show orders
- Verify button for each waiter
- Export buttons

### 5.3 SMS Notification Service

**Service Class:**
```php
class WaiterSmsService
{
    public function sendOrderNotification(BarOrder $order)
    {
        $waiter = $order->waiter;
        if (!$waiter || !$waiter->phone_number) {
            return false;
        }
        
        // Build message
        $items = [];
        foreach ($order->items as $item) {
            $items[] = $item->quantity . 'x ' . $item->productVariant->product->name;
        }
        foreach ($order->kitchenOrderItems as $item) {
            $items[] = $item->quantity . 'x ' . $item->food_item_name;
        }
        
        $message = "New Order #{$order->order_number}\n";
        $message .= "Items: " . implode(', ', $items) . "\n";
        $message .= "Total: TSh " . number_format($order->total_amount, 0) . "\n";
        if ($order->table) {
            $message .= "Table: {$order->table->table_number}\n";
        }
        $message .= "Time: " . $order->created_at->format('H:i');
        
        // Send SMS
        $smsService = new SmsService();
        $result = $smsService->sendSms($waiter->phone_number, $message);
        
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
        
        return $result['success'];
    }
}
```

### 5.4 Mobile Money Payment Flow

**Payment Form (in Waiter Dashboard):**
```php
// When marking order as paid
if ($paymentMethod === 'mobile_money') {
    // Show form with:
    // - Customer phone number input
    // - Transaction reference input
    // - Display restaurant's Lipa Number
}
```

**Payment Verification:**
```php
public function verifyMobileMoneyPayment(Request $request, BarOrder $order)
{
    // Manager verifies payment
    // Can check against M-Pesa statement
    // Mark as verified
    $order->payments()->where('payment_method', 'mobile_money')->update([
        'payment_status' => 'verified',
        'verified_by' => auth()->id(),
        'verified_at' => now()
    ]);
}
```

---

## 6. User Interface Mockups

### 6.1 Waiter Sales Dashboard
```
┌─────────────────────────────────────────┐
│  Waiter Sales Dashboard - Dec 6, 2025  │
├─────────────────────────────────────────┤
│  [Date Picker]                          │
│                                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐│
│  │ Total    │ │ Cash     │ │ Mobile   ││
│  │ Sales    │ │ Collected│ │ Money    ││
│  │ TSh 450K │ │ TSh 300K │ │ TSh 150K ││
│  └──────────┘ └──────────┘ └──────────┘│
│                                         │
│  Orders Today: 25                       │
│                                         │
│  ┌───────────────────────────────────┐│
│  │ Order # | Items | Amount | Method  ││
│  │ ORD-123 | 2x... | 15,000 | Cash    ││
│  │ ORD-124 | 1x... | 8,000  | Mobile  ││
│  └───────────────────────────────────┘│
│                                         │
│  [Submit Reconciliation]                 │
└─────────────────────────────────────────┘
```

### 6.2 Counter Reconciliation Page
```
┌─────────────────────────────────────────┐
│  Daily Reconciliation - Dec 6, 2025     │
├─────────────────────────────────────────┤
│  [Date Picker]                          │
│                                         │
│  ┌─────────────────────────────────────┐│
│  │ Waiter | Sales | Orders | Status   ││
│  ├─────────────────────────────────────┤│
│  │ John   │ 450K │  25    │ [Verify]  ││
│  │ ├─ ORD-123: 15,000 (Cash)          ││
│  │ ├─ ORD-124: 8,000 (Mobile)        ││
│  │ Mary   │ 320K │  18    │ [Verify]  ││
│  │ Peter  │ 280K │  15    │ Verified  ││
│  └─────────────────────────────────────┘│
│                                         │
│  [Export to PDF] [Export to Excel]      │
└─────────────────────────────────────────┘
```

---

## 7. Security Considerations

1. **Reconciliation Verification:**
   - Only managers/counter staff can verify
   - Audit trail for all verifications
   - Cannot modify verified reconciliations

2. **Payment Verification:**
   - Mobile money payments require verification
   - Transaction references must be unique
   - Cannot delete verified payments

3. **SMS Notifications:**
   - Rate limiting to prevent spam
   - Opt-out functionality
   - Cost tracking

---

## 8. Recommendations

### 8.1 Implementation Priority

**High Priority (Must Have):**
1. ✅ Waiter sales dashboard
2. ✅ Counter reconciliation page
3. ✅ Basic SMS notifications
4. ✅ Mobile money payment entry (manual)

**Medium Priority (Should Have):**
5. ⚠️ Chef reconciliation page
6. ⚠️ Payment verification system
7. ⚠️ PDF/Excel export

**Low Priority (Nice to Have):**
8. 📋 M-Pesa API integration
9. 📋 Advanced analytics
10. 📋 Mobile app for waiters

### 8.2 Mobile Money Integration Strategy

**Recommended Approach:**
1. **Start with Manual Entry:**
   - Simple and fast to implement
   - No API costs
   - Full control

2. **Add Verification Workflow:**
   - Manager verifies against M-Pesa statement
   - Mark payments as verified
   - Track verification status

3. **Future: API Integration:**
   - Integrate with M-Pesa Daraja API
   - Real-time payment verification
   - Automatic confirmation

### 8.3 SMS Notification Strategy

**Cost Considerations:**
- Each SMS costs ~TSh 100-200
- For 100 orders/day = TSh 10,000-20,000/day
- Consider: Only send for orders above certain amount?
- Or: Send summary at end of day instead of per order?

**Recommendation:**
- Send SMS for orders above TSh 10,000
- Or send daily summary at end of day
- Allow waiter to configure notification preferences

---

## 9. Next Steps

1. **Review this proposal** with stakeholders
2. **Prioritize features** based on business needs
3. **Create detailed technical specifications** for Phase 1
4. **Set up development environment** for testing
5. **Begin Phase 1 implementation**

---

## 10. Questions to Consider

1. **SMS Frequency:**
   - Per order or daily summary?
   - What's the cost tolerance?

2. **Mobile Money:**
   - Start with manual or go straight to API?
   - Which payment provider? (M-Pesa, Tigo Pesa, Airtel Money)

3. **Reconciliation Timing:**
   - End of day only or multiple times per day?
   - What happens if waiter forgets to submit?

4. **Access Control:**
   - Who can verify reconciliations?
   - Can waiters see other waiters' data?

---

## Conclusion

This system will provide comprehensive tracking and reconciliation for waiter sales, with SMS notifications and mobile money payment support. The phased approach allows for incremental implementation while maintaining system stability.

**Estimated Development Time:** 4-6 weeks
**Estimated Cost:** Development time + SMS costs + Potential M-Pesa API costs




