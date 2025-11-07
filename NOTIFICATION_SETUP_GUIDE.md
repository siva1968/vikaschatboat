# Notification Setup Guide

## Problem
Emails and WhatsApp messages are not being sent because notification settings are not configured.

## Current Status
```
❌ Email Provider: NOT SET
❌ WhatsApp Provider: NOT SET
❌ Admin Email: NOT SET
❌ Admin Phone: NOT SET
```

## Quick Fix - Configure Notifications

### Step 1: Go to Admin Settings
1. Login to WordPress Admin
2. Go to: **EduBot Pro → Settings** (or School Settings)
3. Look for **Notification Settings** section

### Step 2: Configure Email Notifications

**Option A: ZeptoMail (Recommended)**
1. Email Provider: Select "ZeptoMail"
2. Get ZeptoMail API Token from: https://www.zeptomail.com/
3. Enter the token in API Settings
4. Test email delivery

**Option B: SMTP**
1. Email Provider: Select "SMTP"
2. Configure:
   - SMTP Host: (e.g., smtp.gmail.com)
   - SMTP Port: (e.g., 587)
   - SMTP Username: your@email.com
   - SMTP Password: your password
3. Test email delivery

**Option C: WordPress Default**
1. Email Provider: Select "WordPress Mail"
2. Uses wp_mail() function
3. May require SMTP plugin for reliability

### Step 3: Configure WhatsApp Notifications

**Option A: Interakt (Recommended for India)**
1. WhatsApp Provider: Select "Interakt"
2. Get Interakt API Key from: https://www.interakt.shop/
3. Enter API key in API Settings
4. Configure WhatsApp template
5. Test WhatsApp delivery

**Option B: Twilio**
1. WhatsApp Provider: Select "Twilio"
2. Get Twilio credentials from: https://www.twilio.com/
3. Configure:
   - Account SID
   - Auth Token
   - WhatsApp Number
4. Test WhatsApp delivery

### Step 4: Set Recipient Details

**Admin Notifications:**
```
Admin Email: admissions@epistemo.in
Admin Phone: +917702800800
```

**Enable Notifications:**
```
✓ Admin Notifications: ON
✓ Parent Notifications: ON (optional - sends to parents)
✓ Email Enabled: ON
✓ WhatsApp Enabled: ON
```

### Step 5: Test Notifications

Run this command to test:
```bash
php check_notifications.php
```

Or submit a test admission enquiry through the chatbot.

## Database Configuration Method

If admin interface is not working, you can configure directly in database:

```sql
-- Get current config
SELECT config_data FROM wp_edubot_school_configs WHERE site_id = 1;

-- Update notification settings (example)
UPDATE wp_edubot_school_configs
SET config_data = JSON_SET(
    config_data,
    '$.notification_settings.email_provider', 'zeptomail',
    '$.notification_settings.whatsapp_provider', 'interakt',
    '$.notification_settings.admin_email', 'admissions@epistemo.in',
    '$.notification_settings.admin_phone', '+917702800800',
    '$.notification_settings.email_enabled', true,
    '$.notification_settings.whatsapp_enabled', true,
    '$.notification_settings.admin_notifications', true,
    '$.notification_settings.parent_notifications', true
)
WHERE site_id = 1;
```

## Verification Checklist

After configuration, verify:

- [ ] Email provider selected
- [ ] Email provider API key/credentials configured
- [ ] WhatsApp provider selected
- [ ] WhatsApp provider API key configured
- [ ] Admin email set
- [ ] Admin phone set
- [ ] Email enabled: ON
- [ ] WhatsApp enabled: ON
- [ ] Admin notifications: ON
- [ ] Test notification sent successfully

## Common Issues

### Issue 1: Emails not sending
**Solution:**
- Check email provider API key
- Check spam folder
- Verify admin email is correct
- Check email provider logs

### Issue 2: WhatsApp not sending
**Solution:**
- Check WhatsApp provider API key
- Verify phone number format (+91XXXXXXXXXX)
- Check WhatsApp template is approved
- Verify provider account balance

### Issue 3: No notifications at all
**Solution:**
- Check admin_notifications is enabled
- Check email_enabled is true
- Check whatsapp_enabled is true
- Run diagnostic: `php check_notifications.php`

## Quick Database Check

```sql
-- Check current notification settings
SELECT
    JSON_EXTRACT(config_data, '$.notification_settings.email_provider') as email_provider,
    JSON_EXTRACT(config_data, '$.notification_settings.whatsapp_provider') as whatsapp_provider,
    JSON_EXTRACT(config_data, '$.notification_settings.admin_email') as admin_email,
    JSON_EXTRACT(config_data, '$.notification_settings.admin_phone') as admin_phone,
    JSON_EXTRACT(config_data, '$.notification_settings.email_enabled') as email_enabled,
    JSON_EXTRACT(config_data, '$.notification_settings.whatsapp_enabled') as whatsapp_enabled
FROM wp_edubot_school_configs
WHERE site_id = 1;
```

## Next Steps

1. Choose email provider (ZeptoMail recommended)
2. Choose WhatsApp provider (Interakt recommended for India)
3. Get API keys from providers
4. Configure in admin settings
5. Set admin email and phone
6. Enable all notifications
7. Test with a dummy enquiry

## Support

If issues persist:
- Check error logs: `wp-content/debug.log`
- Run diagnostic: `php check_notifications.php`
- Verify API provider account is active
- Check API provider documentation
