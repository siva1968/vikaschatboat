# 🚨 NOTIFICATIONS NOT SENDING - DIAGNOSTIC & FIX GUIDE

**Issue**: All notifications (Email, WhatsApp, SMS) showing "Not Sent"  
**Status**: 🔍 Diagnosing - Root cause likely configuration in database  
**Created**: November 6, 2025

---

## ⚡ QUICK FIX - Do This First

1. Upload `diagnose_notifications.php` to your WordPress root
2. Open: `http://yoursite.com/diagnose_notifications.php`
3. Check what it says in "Summary & Fixes"
4. Follow the recommended action

---

## 🔍 Root Cause Analysis

The notification system checks configuration flags before sending:

```php
// Line 71 in class-notification-manager.php
if (!empty($notification_settings['parent_notifications'])) {
    // Send parent notifications
}

// Line 76 in class-notification-manager.php
if (!empty($notification_settings['admin_notifications'])) {
    // Send admin notifications
}
```

If these flags are:
- ❌ `false` → Notifications disabled
- ❌ Not set → Using defaults
- ❌ Empty → Notifications won't send

**Most Likely Issue**: Configuration in database doesn't have notifications enabled.

---

## 📋 Where Configuration Comes From

```
Order of precedence:

1. Database table: wp_edubot_school_configs
   └─ Column: config_data (JSON)
   └─ Must have: notification_settings → parent_notifications: true
   
2. If not in DB → Default config in class-school-config.php
   └─ Contains: 'parent_notifications' => true
   └─ Contains: 'admin_notifications' => true
```

**The Problem**: Database config might not have been initialized with notification_settings.

---

## 🧪 Step 1: Run Diagnostic Script

### Upload & Run
1. Copy this file to WordPress root: `diagnose_notifications.php`
2. Open browser: `http://yoursite.com/diagnose_notifications.php`
3. Check section: "Summary & Fixes"

### What It Shows
- ✅ If config table exists
- ✅ If active config found
- ✅ Notification settings status
- ✅ Which notifications are disabled
- ✅ API provider configuration
- ✅ Recent applications and their notification status

---

## 🔧 Common Issues & Fixes

### Issue 1: "notification_settings NOT FOUND"

**Cause**: Config was saved before notification_settings existed

**Fix**:

Go to **WordPress Admin → EduBot Pro Settings**:
1. Click: Notification Settings tab
2. Check: Parent Notifications ✓
3. Check: Admin Notifications ✓
4. Check: Email Notifications ✓
5. Click: Save Settings

This will update the database config with notification_settings.

### Issue 2: "Parent Notifications: false"

**Cause**: Notifications were explicitly disabled

**Fix**:

Go to **WordPress Admin → EduBot Pro Settings**:
1. Click: Notification Settings tab
2. Enable: "Send Notifications to Parents"
3. Click: Save Settings

### Issue 3: "No active config found"

**Cause**: Database table exists but no config for this site

**Fix**:

1. Access WordPress Admin for your site
2. Go to: **EduBot Pro → Settings**
3. Configure basic school info:
   - School Name
   - School Email
   - Contact Phone
4. Click: Save Settings

This creates the configuration record.

### Issue 4: "Config table does NOT exist"

**Cause**: Database wasn't initialized when plugin was activated

**Fix**:

1. Go to **WordPress Admin → Plugins**
2. Find: **EduBot Pro**
3. Click: **Deactivate**
4. Wait 5 seconds
5. Click: **Activate**

This will run activation hooks and create the table.

---

## 🛠️ Enhanced Logging (DEPLOYED)

I've added detailed logging to help diagnose issues:

**File**: `includes/class-notification-manager.php`

**New Logs**:
```
EduBot Notification: Application ID: [ID]
EduBot Notification: Config notification_settings: [JSON]
EduBot Notification: Parent notifications enabled? YES/NO
EduBot Notification: Admin notifications enabled? YES/NO
EduBot Notification: Sending parent notifications for application [ID]
EduBot Notification: Skipping parent notifications - disabled in config
```

**To View Logs**:
1. Enable WP_DEBUG in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Create test enquiry

3. Check: `wp-content/debug.log`

4. Look for lines starting with "EduBot Notification"

---

## 🔧 Manual Database Fix (Advanced)

If you're comfortable with MySQL, you can directly check/fix the database:

### Check Current Config
```sql
SELECT site_id, status, config_data 
FROM wp_edubot_school_configs 
WHERE site_id = 1 
LIMIT 1;
```

### Check notification_settings in Config
```sql
-- This shows the raw JSON
SELECT config_data 
FROM wp_edubot_school_configs 
WHERE site_id = 1 AND status = 'active'
LIMIT 1;

-- Look for: "notification_settings"
-- Should contain: "parent_notifications":true
```

### If notification_settings Missing

Use WordPress admin to save settings:
1. WordPress Admin → EduBot Pro Settings
2. Fill in any field
3. Click: Save
4. This will merge settings with existing config

---

## 📊 Complete Diagnostic Checklist

Run through this checklist:

- [ ] Upload `diagnose_notifications.php`
- [ ] Open it in browser
- [ ] Check: "Config table exists" - should be ✅
- [ ] Check: "Active config found" - should be ✅
- [ ] Check: "notification_settings exists" - should be ✅
- [ ] Check: "parent_notifications" - should be true
- [ ] Check: "admin_notifications" - should be true
- [ ] Check: "email_enabled" - should be true
- [ ] Check: No issues in "Summary & Fixes" section
- [ ] Enable WP_DEBUG
- [ ] Create test enquiry
- [ ] Check debug.log for "EduBot Notification" entries
- [ ] Check if applications show email_sent = 1

---

## 🧪 Testing After Fix

### Step 1: Enable Logging
Edit: `wp-config.php`
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Step 2: Create Test Enquiry
1. Go to chatbot or enquiry form
2. Fill in details:
   - Name: "Test"
   - Email: "your-email@gmail.com"
   - Phone: "919876543210"
   - Grade: "I"
3. Submit

### Step 3: Check Logs
1. Open: `wp-content/debug.log`
2. Look for: "EduBot Notification:"
3. Should see:
   ```
   EduBot Notification: Application ID: 123
   EduBot Notification: Config notification_settings: {"parent_notifications":true,...}
   EduBot Notification: Parent notifications enabled? YES
   EduBot Notification: Admin notifications enabled? YES
   ```

### Step 4: Check Email
1. Look in email inbox (5-10 seconds)
2. Check spam folder
3. If received → ✅ Email works

### Step 5: Verify Database
1. WordPress Admin → EduBot Pro → Enquiries
2. Find your test enquiry
3. Check flags:
   - `email_sent` should be 1
   - `whatsapp_sent` should be 1 (if WhatsApp configured)

---

## 📞 Troubleshooting by Symptom

| Symptom | Likely Cause | Fix |
|---------|--|--|
| All notifications disabled in diagnostics | Config not initialized | Go to Settings and save |
| Logs show "disabled in config" | Notifications unchecked in settings | Enable in Settings → Notifications |
| No logs appear at all | WP_DEBUG not enabled | Enable WP_DEBUG in wp-config.php |
| Logs show parent/admin enabled but no emails | Email provider not configured | Go to Settings → API Integrations → Email |
| Database table doesn't exist | Plugin not activated properly | Deactivate and reactivate plugin |

---

## 🎯 Expected Behavior After Fix

When user creates enquiry:
1. ✅ Notification manager called
2. ✅ Config loaded (should show parent_notifications: true)
3. ✅ Parent email sent
4. ✅ Parent WhatsApp sent (if provider configured)
5. ✅ Admin email sent
6. ✅ Database flags updated (email_sent=1, whatsapp_sent=1)
7. ✅ Logs show success

---

## 📝 Configuration Requirements

For notifications to work, you need:

### Minimum Configuration
- ✅ Parent notifications enabled
- ✅ Admin notifications enabled
- ✅ Email notifications enabled
- ✅ At least email provider configured

### Optional Configuration
- ⭕ WhatsApp provider (for WhatsApp messages)
- ⭕ SMS provider (for SMS messages)

---

## 🔄 Recovery Steps (If Everything Broken)

If nothing works after trying everything:

### Nuclear Option
1. Go to WordPress Admin → Plugins
2. Deactivate: EduBot Pro
3. Delete plugin directory: `/wp-content/plugins/edubot-pro/`
4. Upload fresh copy of plugin
5. Activate plugin
6. Reconfigure: Go to EduBot Pro Settings and fill in all fields
7. Save Settings (this initializes database with all defaults)

---

## 📚 Files Involved

### Files Modified (Enhanced Logging)
- `includes/class-notification-manager.php` (lines 65-88)
  - Added detailed logging for each check point
  - Shows config status, enabled/disabled status
  - Logs when skipping notifications

### Files Created (Diagnostic Tools)
- `diagnose_notifications.php` (WordPress root)
  - Check config table
  - Check active config
  - Check notification settings
  - Check API providers
  - Check recent applications
  - Show summary of issues

---

## ✅ Action Plan

**Right Now**:
1. [ ] Upload `diagnose_notifications.php`
2. [ ] Open it in browser
3. [ ] Note all issues in "Summary & Fixes"

**Immediate**:
4. [ ] Fix issues according to diagnostic output
5. [ ] Save settings in WordPress Admin
6. [ ] Enable WP_DEBUG
7. [ ] Create test enquiry
8. [ ] Check error logs

**Verify**:
9. [ ] See "EduBot Notification:" entries in debug.log
10. [ ] Check email received
11. [ ] Check database flags updated

**Clean Up**:
12. [ ] Delete `diagnose_notifications.php`
13. [ ] Disable WP_DEBUG if preferred

---

## 🚀 Next Steps

1. **Immediate**: Run `diagnose_notifications.php` to identify the exact issue
2. **Then**: Follow the "Summary & Fixes" recommendations
3. **Test**: Create test enquiry and verify notifications
4. **Monitor**: Check error logs for "EduBot Notification" entries
5. **Document**: Keep notes of what was fixed for future reference

---

**Status**: 🔍 Ready for diagnosis  
**Tools**: `diagnose_notifications.php` + Enhanced logging  
**Expected Resolution Time**: 5-15 minutes  

---

*Last Updated: November 6, 2025*

