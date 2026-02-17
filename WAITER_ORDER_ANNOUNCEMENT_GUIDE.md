# Waiter Order Announcement - Complete Guide

## ✅ System Status: WORKING

The real-time order announcement system is **fully operational**. When a waiter places an order, it will automatically trigger a Swahili announcement on the counter screen within 3 seconds.

---

## How It Works

### 1. Waiter Places Order
- Waiter uses dashboard: `http://192.168.100.101:8000/bar/waiter/dashboard`
- Or uses kiosk: `http://192.168.100.101:8000/kiosk`
- Order is created with:
  - `status = 'pending'`
  - `waiter_id = [waiter's ID]`
  - Order items and total

### 2. Counter Screen Detects Order
- Counter screen polls every 3 seconds
- Checks for new orders with `id > lastOrderId` and `status = 'pending'`
- When found, triggers announcement

### 3. Announcement Plays
- Swahili message: "Oda namba X imeombwa: [items]."
- Visual notification appears
- Order highlighted in table

---

## Setup Requirements

### Counter Screen Setup

1. **Open Counter Screen**
   - URL: `http://192.168.100.101:8000/bar/counter/waiter-orders`
   - Login as Counter staff
   - Keep this page open at all times

2. **Enable Speech (IMPORTANT!)**
   - Click anywhere on the counter page
   - This enables speech synthesis (browser security requirement)
   - You only need to do this once per page load
   - Check console for "Speech enabled" message

3. **Check Speakers**
   - Ensure speakers are connected
   - Check system volume
   - Test with: `testOrderAnnouncement()` in browser console

4. **Verify System is Running**
   - Check debug panel (bottom-right) shows "Polling..." messages
   - Check browser console shows polling every 3 seconds
   - Should see: "Polling for new orders (Poll #X, Last ID: Y)"

---

## Testing the System

### Option 1: Real Waiter Order (Recommended)

1. **Open Counter Screen**
   - Login as Counter staff
   - Click page to enable speech
   - Keep page open

2. **Have Waiter Place Order**
   - Waiter logs into dashboard
   - Selects products
   - Places order

3. **Verify Announcement**
   - Within 3 seconds, you should:
     - See console message: "Found 1 new order(s)!"
     - Hear Swahili announcement
     - See visual notification
     - See order highlighted in table

### Option 2: Test Script

```bash
php test_order_announcement.php
```

This creates a test order that will trigger the announcement.

---

## Troubleshooting

### Issue: No sound when waiter places order

**Checklist:**
1. ✅ Counter screen is open and logged in
2. ✅ Page has been clicked (enables speech)
3. ✅ Speakers are connected and volume is up
4. ✅ Browser console shows "Polling..." messages
5. ✅ Check console for "Found X new order(s)!" message

**Debug Steps:**
1. Open browser console (F12)
2. Check for errors
3. Verify polling is working: Should see messages every 3 seconds
4. Test speech manually: `testOrderAnnouncement()` in console
5. Check system status: `getOrderPollingStatus()` in console

### Issue: Orders not detected

**Possible Causes:**
1. Order status is not 'pending' (check database)
2. Order doesn't have waiter_id (check database)
3. lastOrderId is higher than new order ID (should auto-update)
4. API endpoint error (check console for 403/404/500)

**Solutions:**
1. Check order in database:
   ```sql
   SELECT id, order_number, status, waiter_id 
   FROM orders 
   WHERE waiter_id IS NOT NULL 
   ORDER BY id DESC LIMIT 5;
   ```
2. Check API response in browser console
3. Verify counter staff has `bar_orders.view` permission

### Issue: Speech not working

**Solutions:**
1. Click anywhere on counter page (enables speech)
2. Check browser console for "Speech enabled" message
3. Test manually: `testOrderAnnouncement()` in console
4. Check system volume
5. Try different browser (Chrome recommended)

---

## Current System Status

✅ **Polling**: Working (every 3 seconds)  
✅ **API Endpoint**: Working (returns 200 OK)  
✅ **Order Detection**: Working (detects new orders)  
✅ **Speech Synthesis**: Working (after user interaction)  
✅ **Visual Notifications**: Working  
✅ **Waiter Orders**: Configured correctly  

---

## Example Flow

1. **10:00:00** - Waiter places order #ORD2025120012
2. **10:00:01** - Order saved to database (status='pending', waiter_id=5)
3. **10:00:03** - Counter screen polls API
4. **10:00:03** - API returns new order
5. **10:00:03** - System detects order
6. **10:00:03** - Swahili announcement plays: "Oda namba 2025120012 imeombwa: 2 chupa ya COCA COLA, 1 chupa ya Fanta."
7. **10:00:03** - Visual notification appears
8. **10:00:03** - Order highlighted in table

---

## Important Notes

1. **Speech Requires User Interaction**
   - Browsers require clicking the page before allowing speech
   - This is a browser security feature
   - Click the counter page once to enable

2. **Polling Interval**
   - System checks for new orders every 3 seconds
   - Maximum delay: 3 seconds from order creation to announcement

3. **Order Status**
   - Only orders with `status = 'pending'` trigger announcements
   - Once order status changes (prepared/served), it won't be announced again

4. **Counter Screen Must Stay Open**
   - Polling only works when page is open
   - If tab is hidden, polling pauses (resumes when visible)

---

## Debug Commands

Open browser console (F12) on counter screen:

```javascript
// Get system status
getOrderPollingStatus()

// Test speech manually
testOrderAnnouncement()

// Toggle debug panel
toggleDebugPanel()

// Check if speech is enabled
speechEnabled

// Check last order ID
lastOrderId
```

---

## Summary

✅ **System is working correctly!**

When a waiter places an order:
1. Order is created with correct status and waiter_id
2. Counter screen detects it within 3 seconds
3. Swahili announcement plays automatically
4. Visual notification appears

**Just make sure:**
- Counter screen is open
- Page has been clicked (enables speech)
- Speakers are connected

That's it! The system will work automatically for all waiter orders. 🎉







