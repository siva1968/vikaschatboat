# Remote WordPress EduBot 500 Error Debugging

## The Problem
Your EduBot chatbot at https://stage.epistemo.in is getting HTTP 500 errors when processing user messages.

## Step 1: Check WordPress Error Logs
Log into your server and check these locations for error logs:

### Via cPanel/File Manager:
```
/public_html/wp-content/debug.log
/public_html/error_log
/logs/error_log
```

### Via SSH:
```bash
tail -f /home/username/public_html/wp-content/debug.log
tail -f /home/username/logs/error_log
```

## Step 2: Enable WordPress Debug Mode (if not enabled)
Add these lines to your `wp-config.php` file:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Step 3: Common Issues to Check

### A. OpenAI API Key Issue
Most likely cause - check in WordPress Admin:
- Go to: **EduBot Pro → API Integrations**
- Verify OpenAI API Key is set and valid
- Test the connection

### B. PHP Memory/Timeout Issues
Check if your server has sufficient resources:
- PHP memory limit (should be at least 256MB)
- PHP execution time limit
- Server load

### C. Plugin Conflicts
Temporarily deactivate other plugins to isolate the issue

## Step 4: Quick Fix via WordPress Admin

1. **Login to WordPress Admin**: https://stage.epistemo.in/wp-admin/
2. **Go to EduBot Pro → Settings**
3. **Check API Integrations tab**
4. **Verify all API keys are properly configured**

## Step 5: If You Can Access Server Files

Look for these specific files that might have the error:
- `/wp-content/plugins/edubot-pro/includes/class-edubot-chatbot.php`
- `/wp-content/plugins/edubot-pro/includes/class-edubot-api-integrations.php`

## Most Likely Fixes:

### Fix 1: OpenAI API Key
The error is probably due to missing/invalid OpenAI API key.

### Fix 2: WhatsApp Integration
After updating the WhatsApp token, there might be a configuration mismatch.

### Fix 3: Database Issue
The chatbot might be trying to access a database table that doesn't exist.

---

## What Error Are You Seeing in Logs?
Please check the error logs and share the actual PHP error message - it will tell us exactly what's wrong.
