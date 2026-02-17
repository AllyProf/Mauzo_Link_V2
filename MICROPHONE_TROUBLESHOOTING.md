# Microphone Troubleshooting Guide

## Error: "Requested device not found" / "NotFoundError"

This error means your computer cannot detect a microphone. Here's how to fix it:

---

## Quick Solutions

### Solution 1: Check Microphone Connection (Windows)

1. **Check Physical Connection**
   - Ensure microphone is plugged in
   - Try unplugging and reconnecting
   - Try a different USB port (if USB microphone)

2. **Check Windows Settings**
   - Press `Windows + I` to open Settings
   - Go to **Privacy & Security** > **Microphone**
   - Make sure "Microphone access" is **ON**
   - Make sure "Let apps access your microphone" is **ON**

3. **Test Microphone**
   - Open **Sound Settings** (right-click speaker icon in taskbar)
   - Go to **Input** tab
   - Speak into microphone - you should see the bar moving
   - If not moving, microphone is not detected

### Solution 2: Use File Upload Instead (Recommended)

Since microphone access can be problematic, you can **upload pre-recorded audio files** instead:

1. **Record on Your Phone**
   - Use your phone's voice recorder app
   - Record each part: "Oda nambari", "kutoka kwa mhudumu", etc.
   - Save as MP3 or WAV

2. **Transfer to Computer**
   - Connect phone to computer
   - Copy audio files to computer

3. **Upload Files**
   - Go to: `http://localhost:8000/bar/counter/record-voice`
   - Click **"Upload File"** tab
   - Select your audio file
   - Enter clip name and save

---

## Step-by-Step: Record on Phone and Upload

### Step 1: Record Audio Clips on Phone

Record these 5 clips on your phone:

1. **"Oda nambari"** - Say: "Oda nambari"
2. **"kutoka kwa mhudumu"** - Say: "kutoka kwa mhudumu"
3. **"ameagiza"** - Say: "ameagiza"
4. **"yenye thamani ya shilingi"** - Say: "yenye thamani ya shilingi"
5. **"Asante"** - Say: "Asante"

**Tips:**
- Record in a quiet place
- Speak clearly and at normal pace
- Keep each clip short (2-3 seconds)
- Use consistent tone

### Step 2: Transfer Files to Computer

1. Connect phone to computer via USB
2. Copy audio files to a folder on your computer
3. Note the file locations

### Step 3: Upload to System

1. Go to: `http://localhost:8000/bar/counter/record-voice`
2. Click **"Upload File"** tab
3. For each clip:
   - Enter clip name (e.g., "Oda nambari")
   - Select category: "Static Text"
   - Click "Choose File" and select your audio file
   - Click "Play Uploaded File" to preview
   - Click "Save Uploaded File"

---

## Alternative: Use Online Voice Recorder

If you don't have a phone:

1. **Use Online Voice Recorder**
   - Go to: https://online-voice-recorder.com/
   - Click record
   - Say your text
   - Click stop
   - Download as MP3

2. **Upload to System**
   - Follow Step 3 above

---

## Supported Audio Formats

- ✅ MP3 (Recommended)
- ✅ WAV
- ✅ OGG
- ✅ WebM
- ✅ M4A

---

## Testing Your Recordings

After uploading all clips:

1. Go to recording page
2. Scroll to "Test Announcement" section
3. Enter test values:
   - Order Number: 2025120017
   - Waiter Name: NANCY
   - Items: 2 chupa ya COCA COLA
   - Amount: 2400
4. Click "Test Announcement"
5. You should hear your recorded voice + TTS for dynamic parts

---

## If Microphone Still Doesn't Work

### Check Device Manager (Windows)

1. Press `Windows + X`
2. Select **Device Manager**
3. Expand **Audio inputs and outputs**
4. Look for your microphone
5. If there's a yellow warning, right-click and **Update driver**

### Check Browser Permissions

1. **Chrome:**
   - Click lock icon in address bar
   - Check microphone permission
   - Set to "Allow"

2. **Firefox:**
   - Click lock icon in address bar
   - Check permissions
   - Allow microphone access

### Try Different Browser

- Chrome usually has best microphone support
- Try Edge or Firefox if Chrome doesn't work

---

## Recommended Approach

**For best results, use the Upload File method:**

1. ✅ No microphone setup needed
2. ✅ Record on phone (better quality)
3. ✅ Can record anywhere, anytime
4. ✅ Easier to re-record if needed
5. ✅ No browser permission issues

---

## Next Steps

1. **Try Upload File method** (easiest)
2. **Or fix microphone** (if you prefer recording directly)
3. **Test your recordings** using the test section
4. **Create a real order** to hear it in action

---

**The Upload File method is now available on the recording page!** 🎤📁







