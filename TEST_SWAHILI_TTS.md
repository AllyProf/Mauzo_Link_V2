# Swahili TTS Testing Guide

## Test Order Created ✅

**Order Details:**
- Order #: ORD2025120017
- Order ID: 20
- Waiter: NANCY
- Items: 2x COCA COLA, 2x PEPSI
- Total: TSh 2,400.00

**Swahili Message:**
```
Oda nambari 2025120017 kutoka kwa mhudumu NANCY ameagiza 2 chupa ya COCA COLA, 2 chupa ya PEPSI yenye thamani ya shilingi 2,400. Asante.
```

---

## Testing Steps

### 1. Test Google Translate TTS (Recommended)

1. **Open Test Page**
   - Open `test_swahili_tts.html` in your browser
   - This tests Google Translate TTS directly

2. **Test the Message**
   - The test message is already loaded
   - Click "🔊 Test Google TTS"
   - You should hear proper Swahili pronunciation

3. **Check Results**
   - Look for "✅ Playing audio (Google TTS)" status
   - Listen for proper Swahili pronunciation
   - Check log for any errors

### 2. Test on Counter Screen

1. **Open Counter Screen**
   - URL: `http://192.168.100.101:8000/bar/counter/waiter-orders`
   - Login as Counter staff
   - Keep page open

2. **Enable Speech**
   - Click anywhere on the page (enables speech)
   - Check browser console (F12) for messages

3. **Verify System is Running**
   - Console should show: "Polling for new orders..."
   - Should see polling every 3 seconds

4. **Wait for Announcement**
   - The test order (ID: 20) should be detected within 3 seconds
   - You should hear: "Oda nambari 2025120017 kutoka kwa mhudumu NANCY ameagiza 2 chupa ya COCA COLA, 2 chupa ya PEPSI yenye thamani ya shilingi 2,400. Asante."

### 3. Create Another Test Order

```bash
php test_order_announcement.php
```

This will create a new order and you can test again.

---

## What to Check

### ✅ Google TTS Working
- Audio plays without errors
- Swahili pronunciation is correct
- No CORS errors in console

### ✅ Counter Screen Working
- Polling is active (console messages)
- Order detected (console shows "Found 1 new order(s)!")
- Audio plays automatically
- Visual notification appears

### ❌ If Not Working

**Google TTS Issues:**
- Check browser console for CORS errors
- Check internet connection
- System will fallback to browser TTS automatically

**Counter Screen Issues:**
- Check console for errors
- Verify page was clicked (enables speech)
- Check speakers are connected
- Verify order status is 'pending'

---

## Expected Console Output

### On Counter Screen:
```
[timestamp] Polling for new orders (Poll #X, Last ID: 19)
[timestamp] API response received
[timestamp] Found 1 new order(s)!
[timestamp] Using Google Translate TTS for Swahili
[timestamp] Audio playback started
[timestamp] Audio playback completed
```

### On Test Page:
```
[timestamp] Testing Google Translate TTS
[timestamp] Message: "Oda nambari..."
[timestamp] Audio loading started
[timestamp] Audio can play
[timestamp] Audio playback started
[timestamp] Audio playback completed
```

---

## Troubleshooting

### Issue: No sound from Google TTS

**Possible Causes:**
1. CORS error (Google blocking direct access)
2. Internet connection issue
3. Browser blocking audio

**Solutions:**
1. Check browser console for CORS errors
2. System will automatically fallback to browser TTS
3. Try different browser (Chrome recommended)

### Issue: Audio plays but wrong pronunciation

**Solutions:**
1. Google TTS should handle Swahili correctly
2. If using browser TTS fallback, install Swahili voice (see SWAHILI_VOICE_SETUP.md)
3. Check that language is set to 'sw' in code

### Issue: Order not detected

**Solutions:**
1. Check order ID in database (should be > lastOrderId)
2. Check order status is 'pending'
3. Check order has waiter_id set
4. Verify API endpoint is accessible

---

## Test Results

After testing, note:
- ✅ Google TTS works / ❌ Doesn't work
- ✅ Counter screen detects orders / ❌ Doesn't detect
- ✅ Audio plays / ❌ No audio
- ✅ Pronunciation is correct / ❌ Wrong pronunciation

---

## Next Steps

1. **If Google TTS works**: System is ready! ✅
2. **If Google TTS fails**: System will use browser TTS fallback
3. **If browser TTS also fails**: Install Swahili voice (see SWAHILI_VOICE_SETUP.md)

---

**Ready to test!** 🎤

Open `test_swahili_tts.html` first to verify Google TTS works, then check the counter screen.







