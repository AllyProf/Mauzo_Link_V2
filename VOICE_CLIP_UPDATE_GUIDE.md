# Voice Clip Update Guide

## Overview
This guide will help you update the 5 static audio clips for order announcements.

## New Audio Clips Required

You have 5 audio clips to update:

1. **"Order nambari"** - Says "Order nambari" (Order number)
2. **"Kutoka meza nambari"** - Says "Kutoka meza nambari" (From table number)
3. **"ameagiza"** - Says "ameagiza" (ordered)
4. **"kutoka kwa mhudumu"** - Says "kutoka kwa mhudumu" (from waiter)
5. **"karibu Kili Home"** - Says "karibu Kili Home" (welcome to Kili Home)

## Step-by-Step Update Instructions

### Step 1: Access the Voice Recording Page

1. Go to: `http://192.168.100.101:8000/bar/counter/record-voice`
2. Make sure you're logged in as Counter staff

### Step 2: Find Existing Clips

You should see a list of your current audio clips in the "Recorded Audio Clips" section. Look for:
- "Oda nambari" (or similar) → Update to "Order nambari"
- "Kutoka kwa" (or similar) → Update to "kutoka kwa mhudumu"
- "Ameagiza" → Update to "ameagiza"
- "yenye thaman" (or similar) → **DELETE** (no longer needed)
- "asante" → Update to "karibu Kili Home"

### Step 3: Update Each Clip

For each clip you need to update:

1. **Click the "Update" button** (yellow/warning button) next to the clip
2. The form will be populated with the current clip's name and category
3. **Change the clip name** to match the new audio:
   - Old: "Oda nambari" → New: "Order nambari"
   - Old: "Kutoka kwa" → New: "kutoka kwa mhudumu"
   - Old: "Ameagiza" → New: "ameagiza"
   - Old: "asante" → New: "karibu Kili Home"
4. **Upload your new audio file**:
   - Click the "Upload File" tab
   - Click "Choose File" and select your new audio file
   - Click "Play Uploaded File" to preview
   - Click "Update Uploaded File" to save

### Step 4: Add New Clip (Kutoka meza nambari)

If you don't have a clip for "Kutoka meza nambari" yet:

1. Click the "Upload File" tab
2. Enter clip name: **"Kutoka meza nambari"**
3. Select category: **"Static Text"**
4. Upload your audio file
5. Click "Save Uploaded File"

### Step 5: Delete Old Clip (if needed)

If you have "yenye thaman" or "thamani" clip that's no longer needed:

1. Click the "Delete" button (red button) next to that clip
2. Confirm deletion

## Clip Names (Important!)

Make sure your clips are named **exactly** as follows (case-sensitive matching is flexible):

1. **"Order nambari"** (or "order nambari")
2. **"Kutoka meza nambari"** (or "kutoka meza nambari")
3. **"ameagiza"** (or "Ameagiza")
4. **"kutoka kwa mhudumu"** (or "Kutoka kwa mhudumu")
5. **"karibu Kili Home"** (or "karibu kili home")

## New Announcement Format

The new announcement will play in this order:

1. **"Order nambari"** (recorded clip)
2. **[Order Number]** (TTS - e.g., "2025120035")
3. **"Kutoka meza nambari"** (recorded clip) - *only if table is assigned*
4. **[Table Number]** (TTS - e.g., "T01") - *only if table is assigned*
5. **"kutoka kwa mhudumu"** (recorded clip)
6. **[Waiter Name]** (TTS - e.g., "NANCY")
7. **"ameagiza"** (recorded clip)
8. **[Items List]** (TTS - e.g., "2 chupa ya COCA COLA, 2 chupa ya PEPSI")
9. **"karibu Kili Home"** (recorded clip)

## Example Announcement

For an order from waiter NANCY at table T01:
- "Order nambari 2025120035"
- "Kutoka meza nambari T01"
- "kutoka kwa mhudumu NANCY"
- "ameagiza 2 chupa ya COCA COLA, 2 chupa ya PEPSI"
- "karibu Kili Home"

## Testing

After updating all clips:

1. Go to the counter screen: `http://192.168.100.101:8000/bar/counter/waiter-orders`
2. Click "Enable Audio"
3. Create a test order from the waiter dashboard
4. Listen to the announcement - it should use your new clips!

## Troubleshooting

- **Clip not found?** Check the clip name matches exactly (case doesn't matter)
- **Wrong clip playing?** Make sure you updated the correct clip
- **Still using old format?** Clear your browser cache and refresh the page
- **Need to re-record?** Click "Update" again and upload a new file

## Notes

- The system will automatically match clips even if names are slightly different (e.g., "Order nambari" vs "order nambari")
- If a clip is not found, the system will use TTS (text-to-speech) as a fallback
- Table number will only be announced if the order has a table assigned







