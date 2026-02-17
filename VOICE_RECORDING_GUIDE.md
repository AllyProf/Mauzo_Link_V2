# Voice Recording Guide for Order Announcements

## How It Works

The system allows you to record your own voice for the static parts of the announcement, and uses TTS (Text-to-Speech) for dynamic parts like order numbers, waiter names, items, and amounts.

## Message Structure

The announcement follows this format:
```
"Oda nambari [ORDER_NUMBER] kutoka kwa mhudumu [WAITER_NAME] ameagiza [ITEMS] yenye thamani ya shilingi [AMOUNT]. Asante."
```

## What to Record

You can record these static parts (the parts that don't change):

1. **"Oda nambari"** - "Order number"
2. **"kutoka kwa mhudumu"** - "from waiter"
3. **"ameagiza"** - "has ordered"
4. **"yenye thamani ya shilingi"** - "with value of shillings"
5. **"Asante"** - "Thank you"

**Dynamic parts (will use TTS automatically):**
- Order number (e.g., "2025120017")
- Waiter name (e.g., "NANCY")
- Items list (e.g., "2 chupa ya COCA COLA, 2 chupa ya PEPSI")
- Amount (e.g., "2,400")

## How to Record

### Step 1: Access Recording Page

1. Go to: `http://192.168.100.101:8000/bar/counter/record-voice`
2. Login as Counter staff

### Step 2: Record Each Part

For each static part:

1. **Enter Clip Name**: e.g., "Oda nambari"
2. **Select Category**: "Static Text"
3. **Click "Start Recording"**
4. **Speak clearly**: Say "Oda nambari" (or the part you're recording)
5. **Click "Stop Recording"** when done
6. **Click "Play Recording"** to preview
7. **Click "Save Recording"** to store it

### Step 3: Repeat for All Parts

Record these parts in order:
- "Oda nambari"
- "kutoka kwa mhudumu"
- "ameagiza"
- "yenye thamani ya shilingi"
- "Asante"

## Example Recording

**Clip 1:**
- Name: "Oda nambari"
- Category: Static Text
- Say: "Oda nambari"

**Clip 2:**
- Name: "kutoka kwa mhudumu"
- Category: Static Text
- Say: "kutoka kwa mhudumu"

**Clip 3:**
- Name: "ameagiza"
- Category: Static Text
- Say: "ameagiza"

**Clip 4:**
- Name: "yenye thamani ya shilingi"
- Category: Static Text
- Say: "yenye thamani ya shilingi"

**Clip 5:**
- Name: "Asante"
- Category: Static Text
- Say: "Asante"

## How It Plays

When an order is created, the system will:

1. Play your recorded: "Oda nambari"
2. Use TTS for: Order number (e.g., "2025120017")
3. Play your recorded: "kutoka kwa mhudumu"
4. Use TTS for: Waiter name (e.g., "NANCY")
5. Play your recorded: "ameagiza"
6. Use TTS for: Items (e.g., "2 chupa ya COCA COLA, 2 chupa ya PEPSI")
7. Play your recorded: "yenye thamani ya shilingi"
8. Use TTS for: Amount (e.g., "2,400")
9. Play your recorded: "Asante"

## Testing

1. After recording all clips, go to the recording page
2. Use the "Test Announcement" section
3. Enter test values
4. Click "Test Announcement"
5. You should hear your recorded voice + TTS for dynamic parts

## Tips

- **Speak clearly and at normal pace**
- **Record in a quiet environment**
- **Use consistent tone and volume**
- **Test each clip before saving**
- **You can re-record any clip if needed**

## If You Don't Record Clips

If you don't record any clips, the system will use **full TTS** (Text-to-Speech) for the entire message. This still works, but your recorded voice will sound more natural and professional.

## Managing Clips

- **View all clips**: They appear in the "Recorded Audio Clips" section
- **Play clips**: Click the play button on any clip
- **Delete clips**: Click the delete button to remove a clip
- **Re-record**: Delete old clip and record a new one

---

**Ready to record?** Go to the recording page and start recording your voice! 🎤







