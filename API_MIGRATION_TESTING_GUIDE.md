# API Migration - Testing & Deployment Instructions

## Quick Start (5 Minutes)

### Step 1: Trigger Auto-Migration (2 min)

1. Open browser: `http://localhost/demo/wp-admin/plugins.php`
2. Find "EduBot Pro" plugin
3. Click "Deactivate"
4. Wait for page reload
5. Click "Activate"
6. Wait for activation to complete

**What happens behind scenes:**
- Plugin loads migration helper class
- Checks if migration is needed
- If needed, automatically migrates API settings
- Logs results to debug.log

---

### Step 2: Check Migration Status (1 min)

1. Go to: `http://localhost/demo/wp-admin/admin.php?page=edubot-api-migration`
2. Should see "API Configuration Migration" page
3. Look for:
   - ✅ Migration Status: "Complete" (green)
   - ✅ Email Provider: Should show your provider
   - ✅ SMS Provider: Should show value or "Not configured"

**Expected values after migration:**
- Email Provider: (whatever was in `edubot_email_service`)
- Email From: (whatever was in `edubot_email_from_address`)
- Email API Key: (masked or hidden for security)

---

### Step 3: Test Email Sending (2 min)

1. Open chatbot: `http://localhost/demo/`
2. Fill in test enquiry with your details
3. Submit enquiry
4. Check email inbox (5-10 seconds)
5. Should receive confirmation email

**Verify success:**
- ✅ Email received in inbox
- ✅ Sender is from configured email
- ✅ Subject line correct
- ✅ Content formatted correctly

---

## Detailed Testing Guide

### Test 1: Auto-Migration Verification

**Objective:** Verify that auto-migration ran during plugin activation

**Steps:**

1. **Check Debug Log**
   ```bash
   1. Open: D:\xamppdev\htdocs\demo\wp-content\debug.log
   2. Look for entries like:
   
      [06-Nov-2025 14:30:25 UTC] EduBot Activation: API Migration Result - SUCCESS
      [06-Nov-2025 14:30:25 UTC] EduBot Activation: Migrated X API settings to database table
   
   3. If found → Migration ran successfully ✅
   4. If not found → Check if any API settings configured
   ```

2. **Check Admin Page Status**
   ```
   WordPress Admin
   → EduBot Pro
   → API Migration
   
   Should show:
   ✅ Migration Status: Complete
   ✅ Email Provider: [your provider]
   ```

3. **Query Database Directly**
   ```sql
   -- Open phpMyAdmin or MySQL client
   SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;
   
   -- Should show records with columns filled:
   ✅ id: [number]
   ✅ site_id: 1
   ✅ email_provider: [your setting]
   ✅ email_from_address: [your email]
   ✅ created_at: [timestamp]
   ✅ updated_at: [timestamp]
   ```

---

### Test 2: Settings Form Verification

**Objective:** Verify that saving settings writes to database table

**Steps:**

1. **Go to API Settings**
   ```
   WordPress Admin
   → EduBot Pro (or Settings)
   → API Integrations / Email Settings
   ```

2. **Make a Small Change**
   ```
   1. Change "From Name" field value
   2. Make it unique, like: "TEST_FromName_2025"
   3. Click "Save Settings"
   4. Should see success message or redirect
   ```

3. **Verify Both Locations**
   ```sql
   -- Check table
   SELECT email_from_name FROM wp_edubot_api_integrations WHERE site_id = 1;
   
   -- Should show: "TEST_FromName_2025"
   
   -- Check options (for backward compat)
   SELECT option_value FROM wp_options 
   WHERE option_name = 'edubot_email_from_name';
   
   -- Should also show: "TEST_FromName_2025"
   ```

4. **Reload Settings Page**
   ```
   1. Refresh API Settings page in browser
   2. Check "From Name" field shows your change
   3. Should show: "TEST_FromName_2025"
   ```

---

### Test 3: Email Sending Verification

**Objective:** Verify that emails are sent using migrated configuration

**Steps:**

1. **Submit Test Enquiry**
   ```
   1. Open: http://localhost/demo/
   2. Scroll to chatbot widget
   3. Fill in form:
      - Name: "Test User"
      - Email: "your-email@example.com"
      - Phone: "1234567890"
      - School: "Test School"
      - Class: "10"
      - Message: "Test enquiry for migration verification"
   4. Click "Submit" or "Send"
   ```

2. **Check Email Received**
   ```
   1. Wait 5-10 seconds
   2. Check your email inbox
   3. Should receive:
      ✅ From: [your configured email]
      ✅ Subject: Enquiry Confirmation (or similar)
      ✅ Content: Enquiry acknowledgment
   ```

3. **Verify Database Flag**
   ```sql
   -- Get latest enquiry/application
   SELECT id, application_number, email_sent, email_from_address 
   FROM wp_edubot_applications 
   ORDER BY created_at DESC 
   LIMIT 1;
   
   -- Should show:
   ✅ email_sent: 1 (sent)
   ✅ application_number: ENQ[timestamp]
   ```

4. **Check Debug Log**
   ```bash
   1. Open: D:\xamppdev\htdocs\demo\wp-content\debug.log
   2. Look for entries like:
   
      [06-Nov-2025 14:35:10 UTC] EduBot Notification: Application ID: ENQ20251406
      [06-Nov-2025 14:35:10 UTC] EduBot: Email sent successfully via SendGrid
   ```

---

### Test 4: Fallback Mechanism Verification

**Objective:** Verify that system falls back to options if table record missing

**Steps:**

1. **Delete Table Record**
   ```sql
   DELETE FROM wp_edubot_api_integrations WHERE site_id = 1;
   ```

2. **Submit Another Test Enquiry**
   ```
   1. Repeat Test 3 steps
   2. Email should still be sent
   3. Using options as fallback
   ```

3. **Verify Debug Log**
   ```bash
   1. Should see log entries
   2. Email still sent successfully
   3. Proves fallback works ✅
   ```

4. **Check Migration Page Status**
   ```
   WordPress Admin
   → EduBot Pro
   → API Migration
   
   Should now show:
   ⚠️ Migration Status: Pending
   (Because table record was deleted)
   ```

5. **Restore Migration**
   ```
   1. Click "Start Migration Now"
   2. Should re-migrate settings to table
   3. Status should return to "Complete"
   ```

---

### Test 5: Manual Migration Trigger

**Objective:** Verify admin can manually trigger migration

**Steps:**

1. **Simulate Data Loss**
   ```sql
   DELETE FROM wp_edubot_api_integrations WHERE site_id = 1;
   ```

2. **Go to Migration Page**
   ```
   WordPress Admin
   → EduBot Pro
   → API Migration
   
   Should show:
   ⚠️ "Migration Available"
   📌 "Your API settings are still stored in WordPress options"
   🔘 "Start Migration Now" button
   ```

3. **Click Migration Button**
   ```
   1. Click "Start Migration Now"
   2. Should see loading or redirect
   3. Should return to page with success
   ```

4. **Verify Migration Completed**
   ```sql
   SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;
   
   -- Should show data restored from options ✅
   ```

---

## Expected Results Summary

| Test | Expected Result | Status |
|------|---|---|
| Auto-migration on activation | Settings migrated to table | ✅ |
| Admin page shows status | Status = Complete | ✅ |
| Settings form saves to table | Changes visible in DB | ✅ |
| Email sending works | Email received | ✅ |
| Fallback works | Email sent from options | ✅ |
| Manual migration triggers | Settings re-migrated | ✅ |

---

## Troubleshooting

### "Migration shows Pending - why?"

**Cause:** No API config record in table

**Solution:**
1. Click "Start Migration Now" button
2. Should migrate settings from options to table
3. Or check if any settings configured in `edubot_email_service` option

```sql
SELECT option_value FROM wp_options 
WHERE option_name = 'edubot_email_service';

-- If empty, settings not configured
-- If has value, migration should work
```

---

### "Email not sending after migration"

**Troubleshooting Steps:**

1. **Check Migration Page**
   ```
   WordPress Admin → EduBot Pro → API Migration
   
   ✓ Status shows "Complete"?
   ✓ Email Provider shows value?
   ✓ From Address filled in?
   ```

2. **Check Configuration**
   ```sql
   SELECT email_provider, email_from_address, email_api_key 
   FROM wp_edubot_api_integrations 
   WHERE site_id = 1;
   
   ✓ email_provider has value?
   ✓ email_from_address has valid email?
   ✓ email_api_key not empty?
   ```

3. **Check Debug Log**
   ```bash
   Open: D:\xamppdev\htdocs\demo\wp-content\debug.log
   
   ✓ Any "EduBot" error entries?
   ✓ Any "API error" entries?
   ✓ Check last 50 lines for clues
   ```

4. **Check Email Provider**
   ```
   If using SendGrid/Mailgun/etc:
   ✓ API key is valid?
   ✓ API key has correct permissions?
   ✓ From address verified in provider?
   
   If using wp_mail:
   ✓ Server has mail function configured?
   ✓ Check server email settings?
   ```

---

### "I see 'No active API config found' error"

**Cause:** Application record created but API config missing

**Solution:**
```
1. Go to: WordPress Admin → EduBot Pro → API Migration
2. Status should show: Email Provider, etc.
3. If not showing → Run migration
4. Click: "Start Migration Now"
5. Check status again
```

---

## Database Queries Reference

### Check Migration Status
```sql
-- View current API config
SELECT site_id, email_provider, email_from_address, sms_provider, 
       whatsapp_provider, status, updated_at
FROM wp_edubot_api_integrations
WHERE site_id = 1;
```

### Verify Both Locations
```sql
-- Check table (primary)
SELECT email_provider, email_from_address
FROM wp_edubot_api_integrations
WHERE site_id = 1;

-- Check options (fallback)
SELECT option_name, option_value
FROM wp_options
WHERE option_name IN (
    'edubot_email_service',
    'edubot_email_from_address',
    'edubot_email_api_key'
)
ORDER BY option_name;
```

### Check Sent Notifications
```sql
-- Recent applications with send status
SELECT id, application_number, created_at, 
       email_sent, whatsapp_sent, sms_sent
FROM wp_edubot_applications
ORDER BY created_at DESC
LIMIT 10;
```

---

## Success Indicators ✅

After completing all tests, you should see:

- [x] Migration page accessible and shows correct status
- [x] Settings saved to both table and options
- [x] Email notifications sent successfully
- [x] Database flags updated (`email_sent = 1`)
- [x] Debug log shows success messages
- [x] Fallback mechanism works
- [x] Manual migration trigger works

---

## Deployment Checklist

Before considering migration complete:

- [ ] Plugin re-activated successfully
- [ ] Migration page shows "Complete" status
- [ ] Email provider visible in migration page
- [ ] Test enquiry submitted
- [ ] Test email received
- [ ] Database shows `email_sent = 1` for test enquiry
- [ ] Debug log shows no errors
- [ ] Settings form update works (tested at least one field)
- [ ] Manual migration button works (if tested)
- [ ] All queries in troubleshooting section execute successfully

---

## Performance Verification

### Before Migration
```sql
-- Query to measure before
SET @start = NOW(3);
SELECT * FROM wp_options WHERE option_name = 'edubot_email_service';
SELECT * FROM wp_options WHERE option_name = 'edubot_email_api_key';
SELECT * FROM wp_options WHERE option_name = 'edubot_email_from_address';
SELECT TIMESTAMPDIFF(MICROSECOND, @start, NOW(3)) as time_ms;
```

### After Migration
```sql
-- Query to measure after
SET @start = NOW(3);
SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;
SELECT TIMESTAMPDIFF(MICROSECOND, @start, NOW(3)) as time_ms;
```

**Expected:** After should be 3-4x faster ⚡

---

## Timeline

| Step | Time | Status |
|------|------|--------|
| Deactivate plugin | 30 sec | ⏳ |
| Activate plugin (auto-migrate) | 1 min | ⏳ |
| Check migration page | 1 min | ⏳ |
| Test email sending | 2 min | ⏳ |
| Verify database | 2 min | ⏳ |
| **Total** | **~6 min** | ⏳ |

---

## Next Steps After Testing

✅ If all tests pass:
1. Document results
2. Mark migration as successful
3. Deploy to production (if desired)
4. Monitor for 24 hours
5. Consider Phase 2 enhancements (encryption, etc.)

❌ If any test fails:
1. Check troubleshooting section
2. Review debug.log for errors
3. Run relevant queries to check DB state
4. Contact support with specific error message

---

**Version:** 1.0  
**Date:** 2025-11-06  
**Expected Duration:** 6-10 minutes  
**Complexity:** Low  
**Risk Level:** Very Low (fully backward compatible)
