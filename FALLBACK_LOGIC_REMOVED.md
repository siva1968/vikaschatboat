# Fallback Logic Removed - Clear Error Messages

## Changes Made

Removed all confusing fallback logic from the notification system. The system now fails explicitly with clear error messages instead of silently falling back to alternative configurations.

## Files Modified

### 1. includes/class-api-migration.php

**Method:** `EduBot_API_Migration::get_api_settings()`

**Before:**
- Checked wp_edubot_api_integrations table
- If missing fields, fell back to WordPress options (get_option)
- If no record, used WordPress options entirely
- Multiple confusing fallback paths

**After:**
- ONLY reads from wp_edubot_api_integrations table
- If table has no record, returns empty array with clear error logs
- If fields are empty, logs warnings
- **NO fallbacks to WordPress options**

**Error Messages:**
```
EduBot API Migration: No API settings found in wp_edubot_api_integrations table for site_id: 1
EduBot API Migration: Please configure API settings in WordPress Admin → EduBot Pro → API Settings
EduBot API Migration: email_provider not configured in database
EduBot API Migration: email_api_key not configured in database
```

### 2. includes/class-api-integrations.php

#### A. `send_email()` Method

**Before:**
- Got settings from table with fallback to school config
- If provider empty, tried school config API keys
- If still empty, fell back to wp_mail()
- Unknown provider fell back to wp_mail()

**After:**
- ONLY reads from EduBot_API_Migration::get_api_settings()
- If provider empty, returns false with error logs
- If API key empty, returns false with error logs
- Unknown provider returns false with error logs
- **NO fallback to wp_mail()**

**Error Messages:**
```
EduBot Email: CRITICAL - Email provider not configured in wp_edubot_api_integrations table
EduBot Email: Please configure email settings in WordPress Admin → EduBot Pro → API Settings → Email tab
EduBot Email: Failed to send email to: user@example.com with subject: Test Email

EduBot Email: CRITICAL - Email API key not configured for provider: zeptomail
EduBot Email: Please add API key in WordPress Admin → EduBot Pro → API Settings → Email tab

EduBot Email: ERROR - Unknown email provider: invalid_provider
EduBot Email: Supported providers: sendgrid, mailgun, zeptomail
```

**Success Log:**
```
EduBot Email: Attempting to send via zeptomail to: user@example.com
```

#### B. `send_whatsapp()` Method

**Before:**
- Got API keys from school config
- Silent failure if not configured

**After:**
- ONLY reads from EduBot_API_Migration::get_api_settings()
- If provider empty, returns false with error logs
- If token empty, returns false with error logs
- Unknown provider returns false with error logs
- **NO silent failures**

**Error Messages:**
```
EduBot WhatsApp: CRITICAL - WhatsApp provider not configured in wp_edubot_api_integrations table
EduBot WhatsApp: Please configure WhatsApp settings in WordPress Admin → EduBot Pro → API Settings → WhatsApp tab
EduBot WhatsApp: Failed to send message to: +1234567890

EduBot WhatsApp: CRITICAL - WhatsApp token not configured for provider: meta
EduBot WhatsApp: Please add WhatsApp token in WordPress Admin → EduBot Pro → API Settings → WhatsApp tab

EduBot WhatsApp: ERROR - Unknown WhatsApp provider: invalid_provider
EduBot WhatsApp: Supported providers: meta, twilio
```

**Success Log:**
```
EduBot WhatsApp: Attempting to send via meta to: +1234567890
```

#### C. `send_meta_whatsapp()` Method

**Before:**
- Fell back to get_option() for phone_id and access_token
- Generic error message

**After:**
- ONLY uses values from $api_keys array (no fallbacks)
- Specific error messages for missing phone ID vs missing token
- **NO fallback to get_option()**

**Error Messages:**
```
EduBot WhatsApp: CRITICAL - WhatsApp Phone ID not configured for Meta provider
EduBot WhatsApp: Please add Phone ID in WordPress Admin → EduBot Pro → API Settings → WhatsApp tab

EduBot WhatsApp: CRITICAL - WhatsApp access token missing
```

## Benefits

### 1. Clear Debugging
When notifications don't work, error logs now clearly state:
- What is missing (provider, API key, token, etc.)
- Where to configure it (exact admin page)
- What failed (email address, phone number)

### 2. No Silent Failures
Before: System would silently fall back to wp_mail() which doesn't work on XAMPP
After: System explicitly fails and logs the reason

### 3. Single Source of Truth
Before: Settings could be in multiple places (table, options, school config)
After: Settings ONLY in wp_edubot_api_integrations table

### 4. Easier Troubleshooting
Admins can check logs and immediately know:
- If API settings are not configured
- Which specific setting is missing
- Exact steps to fix the issue

## Testing

All functionality tested and working:
- ✅ Email sending via ZeptoMail works correctly
- ✅ Clear error logs when provider not configured
- ✅ Clear error logs when API key missing
- ✅ No confusing fallback behavior

## Configuration Requirements

After this change, all API integrations MUST be configured in:

**WordPress Admin → EduBot Pro → API Settings**

This saves to: `wp_edubot_api_integrations` table

The system will NO LONGER fall back to:
- WordPress options (get_option)
- School config API keys
- wp_mail() for email
- Any other alternative configuration sources

## Error Log Examples

### When Email Provider Not Configured:
```
EduBot API Migration: No API settings found in wp_edubot_api_integrations table for site_id: 1
EduBot API Migration: Please configure API settings in WordPress Admin → EduBot Pro → API Settings
EduBot Email: CRITICAL - Email provider not configured in wp_edubot_api_integrations table
EduBot Email: Please configure email settings in WordPress Admin → EduBot Pro → API Settings → Email tab
EduBot Email: Failed to send email to: admin@school.com with subject: New Admission Enquiry
```

### When Email Works:
```
EduBot Email: Attempting to send via zeptomail to: admin@school.com
EduBot ZeptoMail: Sending email from info@epistemo.in to admin@school.com
EduBot ZeptoMail: HTTP Status 201
EduBot ZeptoMail: Email sent successfully. Request ID: abc123...
```

## Migration Note

If you have existing settings in WordPress options or school config, they will NO LONGER be used.

Re-save your settings in the admin interface to migrate them to the wp_edubot_api_integrations table.

---

**Summary:** All confusing fallback logic removed. System now fails fast with clear, actionable error messages that tell you exactly what's wrong and how to fix it.
