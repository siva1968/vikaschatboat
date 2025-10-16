# ⚡ Quick Action Guide - Database Migration

## What to Do Now

### Step 1: Copy Plugin Files
✅ Already deployed to: `D:\xamppdev\htdocs\ep\wp-content\plugins\AI ChatBoat\`

### Step 2: Deactivate Plugin in WordPress Admin

1. Go to: `http://localhost/ep/wp-admin/plugins.php`
2. Find: "AI ChatBoat" 
3. Click: "Deactivate"
4. Wait for page to reload

### Step 3: Activate Plugin in WordPress Admin

1. Find: "AI ChatBoat" (should now be in "Inactive" list)
2. Click: "Activate"
3. Wait for page to reload

### Step 4: Check Success

Open WordPress Debug Log and search for:
- ✅ Should see: `EduBot: Added missing column 'source' to enquiries table`
- ✅ Should see: `EduBot: Ensured enquiry table exists`
- ✅ Should NOT see: `Unknown column` error

---

## Then Test Form Submission

1. Go to chatbot on website
2. Fill out admission form
3. Submit form
4. Expected: ✅ Success with enquiry number displayed
5. NOT expected: ❌ "Unknown column" error

---

## What Gets Fixed on Reactivation

When you activate the plugin, it will automatically:

✅ Add `source` column
✅ Add `ip_address` column
✅ Add `user_agent` column
✅ Add `utm_data` column
✅ Add `gclid` column
✅ Add `fbclid` column
✅ Add `click_id_data` column
✅ Add `whatsapp_sent` column
✅ Add `email_sent` column
✅ Add `sms_sent` column

All without any manual SQL commands needed!

---

## Done! 🎉

After reactivation completes, the database will be fully updated and forms will save successfully.

