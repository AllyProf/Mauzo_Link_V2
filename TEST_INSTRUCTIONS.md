# Order Announcement System - Test Instructions

This document explains how to test the real-time order announcement system with Swahili text-to-speech.

## Overview

The system includes:
- Real-time order detection (polling every 3 seconds)
- Swahili text-to-speech announcements
- Visual notifications on counter screen
- Automatic order highlighting

## Test Scripts

### 1. PHP Test Script (`test_order_announcement.php`)

**Purpose**: Creates a test order in the database to verify the system works end-to-end.

**Usage**:
```bash
php test_order_announcement.php
```

**What it does**:
- Finds an active user/owner
- Finds an active waiter
- Finds products with counter stock
- Creates a test order with items
- Tests the API endpoint
- Formats and displays the Swahili message

**Expected Output**:
```
========================================
Order Announcement System Test Script
========================================

Step 1: Finding test user...
✓ Found user: John Doe (ID: 1)

Step 2: Finding a waiter...
✓ Found waiter: Jane Waiter (ID: 5)

Step 3: Finding products with counter stock...
✓ Found 3 products with stock:
  - Coca Cola (500ml): 50 units
  - Fanta (500ml): 30 units

Step 4: Getting current latest order ID...
✓ Last order ID: 100

Step 5: Creating test order...
✓ Test order created successfully!
  Order #: ORD2025120001
  Order ID: 101
  Waiter: Jane Waiter
  Total: TSh 6,000.00
  Items: 2

Step 6: Testing API endpoint...
  ✓ Found 1 new order(s)
    - Order #ORD2025120001 (ID: 101)

Step 7: Testing Swahili message format...
  ✓ Swahili message:
    "Oda namba 2025120001 imeombwa: 2 chupa ya Coca Cola, 1 chupa ya Fanta."
```

**Next Steps After Running**:
1. Open the counter screen: `http://127.0.0.1:8000/bar/counter/waiter-orders`
2. Ensure speakers are connected and volume is up
3. The system should detect the order within 3 seconds
4. You should hear the Swahili announcement

---

### 2. Browser TTS Test (`test_tts_browser.html`)

**Purpose**: Test the text-to-speech functionality directly in the browser.

**Usage**:
1. Open `test_tts_browser.html` in your web browser
2. Click "Check Support" to verify browser compatibility
3. Test different Swahili messages
4. Adjust speech rate, pitch, and volume
5. Test sample order messages

**Features**:
- Browser support detection
- Voice selection (auto-selects Swahili if available)
- Speech rate/pitch/volume controls
- Sample order messages
- API endpoint testing
- Activity logging

**What to Test**:
- ✅ Browser supports Speech Synthesis API
- ✅ Swahili voices are available
- ✅ Messages are spoken clearly
- ✅ Audio volume is adequate
- ✅ Speech rate is appropriate

---

### 3. JavaScript Polling Simulation (`test_polling_simulation.js`)

**Purpose**: Simulate the real-time polling mechanism for testing.

**Usage**:

**Option A: Browser Console**
1. Open the counter screen: `http://127.0.0.1:8000/bar/counter/waiter-orders`
2. Open browser console (F12)
3. Copy and paste the contents of `test_polling_simulation.js`
4. Run: `orderPollingTest.start()`

**Option B: Include in HTML**
```html
<script src="test_polling_simulation.js"></script>
```

**Available Commands**:
```javascript
// Start polling
orderPollingTest.start()

// Stop polling
orderPollingTest.stop()

// Check once immediately
orderPollingTest.check()

// Get statistics
orderPollingTest.stats()

// Set last order ID manually
orderPollingTest.setLastOrderId(100)

// Clear announced orders (to test same order again)
orderPollingTest.clearAnnounced()

// Test speech synthesis
orderPollingTest.testSpeech("Oda namba 2025120001 imeombwa: 2 chupa ya Coca Cola.")
```

**Expected Console Output**:
```
========================================
Order Polling Simulation Test
========================================

Configuration:
  Poll Interval: 3000ms
  API Endpoint: /bar/counter/latest-orders
  Speech Synthesis: Supported
  CSRF Token: Found

🚀 Starting polling...
   Interval: 3000ms
   Initial last order ID: 0

[10:30:15] Polling for new orders (Request #1)...
  No new orders
[10:30:18] Polling for new orders (Request #2)...
✓ Found 1 new order(s)
  📦 New Order: #ORD2025120001
     Waiter: Jane Waiter
     Items: 2
     Total: TSh 6,000
     Message: "Oda namba 2025120001 imeombwa: 2 chupa ya Coca Cola, 1 chupa ya Fanta."
🔊 Speech started
✓ Speech completed
```

---

## Complete Test Flow

### Step 1: Prepare Test Environment

1. **Ensure you have**:
   - Active user/owner in database
   - Active waiter staff member
   - Products with counter stock
   - Speakers connected to counter computer

2. **Check counter screen access**:
   - URL: `http://127.0.0.1:8000/bar/counter/waiter-orders`
   - Counter staff must be logged in
   - Page should load without errors

### Step 2: Test Order Creation

```bash
php test_order_announcement.php
```

**Verify**:
- ✅ Script runs without errors
- ✅ Test order is created
- ✅ Order has items
- ✅ Swahili message is formatted correctly

### Step 3: Test Browser TTS

1. Open `test_tts_browser.html` in browser
2. Click "Check Support"
3. Test a sample message
4. Adjust settings if needed

**Verify**:
- ✅ Browser supports TTS
- ✅ Audio plays through speakers
- ✅ Swahili pronunciation is clear
- ✅ Volume is adequate

### Step 4: Test Real-time Detection

1. Open counter screen: `http://127.0.0.1:8000/bar/counter/waiter-orders`
2. Open browser console (F12)
3. Run the polling simulation script
4. Create a new order (via waiter dashboard or run PHP test script again)

**Verify**:
- ✅ Polling detects new orders
- ✅ Swahili announcement plays
- ✅ Visual notification appears
- ✅ Order is highlighted in table

### Step 5: Test End-to-End

1. **On Waiter Dashboard**: Place a new order
2. **On Counter Screen**: Watch and listen
3. **Expected Results**:
   - Within 3 seconds, order appears
   - Swahili announcement plays: "Oda namba X imeombwa: [items]."
   - Visual notification shows
   - Order row is highlighted

---

## Troubleshooting

### Issue: No audio plays

**Solutions**:
1. Check browser permissions (some browsers require user interaction first)
2. Check system volume
3. Check browser console for errors
4. Try clicking on the page first, then test
5. Verify speakers are connected and working

### Issue: Speech doesn't sound like Swahili

**Solutions**:
1. Browser may not have Swahili voice installed
2. System will use default voice (may not be Swahili)
3. Check available voices in browser settings
4. On Windows: Install Swahili language pack
5. On Mac: System Preferences > Keyboard > Input Sources > Add Swahili

### Issue: Orders not detected

**Solutions**:
1. Check browser console for errors
2. Verify API endpoint is accessible
3. Check CSRF token is present
4. Verify order status is "pending"
5. Check `waiter_id` is not null

### Issue: Multiple announcements for same order

**Solutions**:
1. Check `announcedOrders` Set is working
2. Verify order ID tracking
3. Clear browser cache and reload

### Issue: API returns 403 Forbidden

**Solutions**:
1. Ensure counter staff is logged in
2. Check staff has `bar_orders.view` permission
3. Verify staff belongs to correct owner

---

## Expected Swahili Messages

### Format
```
Oda namba [ORDER_NUMBER] imeombwa: [ITEMS_LIST].
```

### Examples

**Single item**:
```
Oda namba 2025120001 imeombwa: 1 chupa ya Coca Cola.
```

**Multiple items**:
```
Oda namba 2025120002 imeombwa: 2 chupa ya Fanta, 1 chupa ya Sprite, 3 chupa ya Pepsi.
```

**Large order**:
```
Oda namba 2025120003 imeombwa: 5 chupa ya Coca Cola, 3 chupa ya Fanta, 2 chupa ya Sprite.
```

---

## Performance Notes

- **Polling Interval**: 3 seconds (configurable in code)
- **API Response Time**: Should be < 500ms
- **Speech Duration**: ~5-10 seconds per announcement
- **Browser Resources**: Minimal (polling is lightweight)

---

## Browser Compatibility

| Browser | Speech Synthesis | Swahili Voice | Notes |
|---------|------------------|---------------|-------|
| Chrome | ✅ Yes | ⚠️ May need language pack | Best support |
| Edge | ✅ Yes | ⚠️ May need language pack | Good support |
| Firefox | ✅ Yes | ⚠️ Limited | May need extension |
| Safari | ✅ Yes | ⚠️ Limited | macOS/iOS only |

---

## Next Steps After Testing

1. ✅ Verify all tests pass
2. ✅ Adjust speech rate/volume if needed
3. ✅ Test with real waiters placing orders
4. ✅ Monitor system performance
5. ✅ Gather feedback from counter staff

---

## Support

If you encounter issues:
1. Check browser console for errors
2. Verify database has test data
3. Check API endpoint accessibility
4. Review server logs
5. Test with browser TTS test page first

---

**Happy Testing! 🎤🔊**







