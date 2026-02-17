# Swahili Voice Setup Guide

## Problem
If the system is speaking in English instead of Swahili, it means your browser doesn't have a Swahili voice installed.

## Solution: Install Swahili Voice

### Windows 10/11

1. **Open Settings**
   - Press `Windows + I`
   - Go to **Time & Language** > **Speech**

2. **Add Swahili Voice**
   - Under **Manage voices**, click **Add voices**
   - Search for "Swahili" or "Kiswahili"
   - Select **Swahili (Tanzania)** or **Swahili (Kenya)**
   - Click **Install**

3. **Set as Default (Optional)**
   - After installation, you can set it as the default voice
   - Restart your browser

### macOS

1. **Open System Preferences**
   - Go to **System Preferences** > **Accessibility**
   - Click **Spoken Content**

2. **Add Swahili Voice**
   - Click **System Voice** dropdown
   - Click **Customize...**
   - Search for "Swahili"
   - Select and download **Swahili** voice
   - Click **Done**

3. **Restart Browser**
   - Close and reopen your browser

### Linux (Ubuntu/Debian)

```bash
# Install espeak with Swahili support
sudo apt-get update
sudo apt-get install espeak espeak-data

# Or install festival with Swahili
sudo apt-get install festival festvox-sw1
```

### Chrome/Edge (Windows)

Chrome and Edge use Windows voices. Follow the Windows instructions above.

### Firefox

Firefox also uses system voices. Follow your operating system instructions above.

---

## Verify Installation

1. **Open Counter Screen**
   - Go to: `http://192.168.100.101:8000/bar/counter/waiter-orders`
   - Open browser console (F12)

2. **Check Available Voices**
   - In console, type:
   ```javascript
   speechSynthesis.getVoices().filter(v => v.lang.startsWith('sw'))
   ```
   - Should show Swahili voices if installed

3. **Test Speech**
   - In console, type:
   ```javascript
   testOrderAnnouncement()
   ```
   - Should speak in Swahili

---

## Alternative: Use Online TTS Service

If you cannot install Swahili voices, we can integrate an online TTS service like:
- Google Cloud Text-to-Speech (requires API key)
- Azure Cognitive Services (requires API key)
- Amazon Polly (requires API key)

Contact your developer to set this up.

---

## Current System Behavior

The system will:
1. ✅ Try to find Swahili voice
2. ✅ Set language to `sw-TZ` (Tanzania Swahili)
3. ⚠️ If no Swahili voice found, use default voice but still pronounce Swahili text

**Note**: Even without a Swahili voice, the text format is correct. The pronunciation may not be perfect, but the message will be understandable.

---

## Quick Test

After installing Swahili voice:

1. Refresh counter screen
2. Check console for: "Swahili voice found: [voice name]"
3. Create test order: `php test_order_announcement.php`
4. Should hear Swahili pronunciation

---

**Status**: System is configured correctly. You just need to install Swahili voice on your system.







