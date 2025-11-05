# Logo URL Security Validation - Troubleshooting Guide

**Date:** November 5, 2025  
**Status:** ✅ Debug logging enabled  
**Version:** 1.4.3

---

## Current System Status

### ✅ GD Extension
- Status: **ENABLED**
- Version: 2.1.0 (bundled)
- PNG Support: ✅
- JPEG Support: ✅
- GIF Support: ✅

### ✅ WordPress Debug Logging
- WP_DEBUG: **TRUE**
- WP_DEBUG_LOG: **TRUE** 
- WP_DEBUG_DISPLAY: **FALSE**
- Debug Log: `wp-content/debug.log`

### ✅ Plugin Status
- EduBot Pro: **ACTIVE** (v1.4.3)
- Database: `demo`
- Tables: 1 of 3 present (enquiries ✅, visitors ❌, analytics ❌)

---

## How to Find Your Logo URL Error

**Step 1:** Go to WordPress Admin Settings
- Navigate to: `http://localhost/demo/wp-admin/`
- Go to: **EduBot Pro → School Settings**

**Step 2:** Try uploading a logo
- Click "Select Logo" button
- Choose an image
- Click "Save Settings"
- Note the exact error message

**Step 3:** Check the Debug Log
```bash
File: wp-content/debug.log
Search for: "Logo URL" or "security validation"
```

**Step 4:** Share the error
- Copy the exact error message from debug log
- Share the URL you tried to use (without credentials)

---

## URL Validation Test Results

These URLs were tested in your WordPress instance:

| URL | Status | Type |
|-----|--------|------|
| `/wp-content/uploads/school-logo.png` | ✅ PASS | Relative |
| `/wp-content/uploads/2024/11/logo.jpg` | ✅ PASS | Relative |
| `https://example.com/logo.png` | ✅ PASS | External |
| `http://localhost/logo.png` | ✅ PASS | Local |
| `/images/logo.png` | ✅ PASS | Relative |
| `https://cdn.example.com/schools/logo.jpg` | ✅ PASS | CDN |
| `javascript:alert("xss")` | ❌ FAIL | Malicious |
| `data:image/svg+xml,...` | ❌ FAIL | Malicious |
| `/path/<script>alert()>` | ❌ FAIL | Malicious |
| `ftp://example.com/logo.png` | ❌ FAIL | Invalid |

---

## Troubleshooting Steps

### Issue: "Logo URL failed security validation"

**Step 1: Check URL Format**

| Problem | Solution |
|---------|----------|
| URL contains `javascript:` | Remove it, use regular URL |
| URL contains `<script>` | Remove any code, use image URL only |
| URL contains `data:` | Use regular http/https URL instead |
| URL is incomplete | Ensure it starts with `/` or `http://` |

**Step 2: Verify File Exists**

If using relative URL:
```bash
# Check if file exists in WordPress uploads
http://localhost/demo/wp-content/uploads/your-logo.png
```

**Step 3: Try These URLs**

Copy & paste to test (one at a time):
```
/wp-content/uploads/school-logo.png
https://example.com/logo.png
/images/logo.png
```

**Step 4: Check Admin Settings**

Go to: **EduBot Pro → School Settings**
- Look for "School Logo" field
- Try uploading from media library first
- If that works, try entering URL manually

---

## How to Use Media Library (Recommended)

1. **Go to School Settings**
   - Admin → EduBot Pro → School Settings

2. **Click "Select Logo" Button**
   - A media uploader will appear

3. **Upload Image**
   - Click "Upload Files"
   - Select JPG, PNG, GIF, or SVG
   - Wait for upload to complete

4. **Select Image**
   - Image appears in media library
   - Click to select it
   - URL is auto-populated

5. **Save Settings**
   - Click "Save Settings" button
   - Logo should now display

---

## Manual URL Entry (If Media Library Doesn't Work)

1. **Upload Image to WordPress**
   - Via FTP or File Manager
   - Place in: `/wp-content/uploads/`
   - Example: `/wp-content/uploads/school-logo.png`

2. **In School Settings**
   - Paste URL: `/wp-content/uploads/school-logo.png`
   - Click "Save Settings"

3. **Verify Display**
   - Check if logo appears in chatbot

---

## Debug Log Analysis

**Where to find errors:**
```
Location: wp-content/debug.log
```

**What to look for:**
```
EduBot: Validating logo URL: [YOUR_URL]
EduBot: Logo URL failed security validation
EduBot: Logo URL validation passed
```

**Example error entry:**
```
[05-Nov-2025 11:26:20 UTC] EduBot: Logo URL failed security validation
[05-Nov-2025 11:26:20 UTC] User tried URL: javascript:alert("xss")
```

---

## Common Error Scenarios

### Scenario 1: External URL Blocked

**Error:**
```
Logo URL failed security validation
```

**Cause:** URL might contain suspicious characters

**Solution:**
1. Use HTTPS only: `https://example.com/logo.png`
2. Remove query parameters: `?v=1`, `?size=large`
3. Use HTTPS CDN: `https://cdn.example.com/logo.png`

### Scenario 2: WordPress Path Rejected

**Error:**
```
Logo URL failed security validation
```

**Cause:** Path format incorrect

**Solution:**
1. Use full relative path: `/wp-content/uploads/logo.png`
2. Not: `wp-content/uploads/logo.png` (missing leading `/`)
3. Not: `./wp-content/uploads/logo.png` (should start with `/`)

### Scenario 3: Special Characters in Filename

**Error:**
```
Logo URL failed security validation
```

**Cause:** Filename contains suspicious characters

**Solution:**
1. Rename file to remove special chars
2. Use: `school-logo.png` (not `school-logo (1).png`)
3. Use: `school_logo.png` or `schoolLogo.png`

---

## Quick Test Commands

**Test Logo URL Validation:**
```bash
cd D:\xamppdev\htdocs\demo
php test-logo-urls.php
```

**Check Database Tables:**
```bash
php check-tables.php
```

**View Debug Log (Last 50 lines):**
```bash
Get-Content wp-content\debug.log -Tail 50
```

---

## Next Steps

1. **Try uploading a logo** using the steps above
2. **If it fails**, check the debug log
3. **Share the exact error** from debug log
4. **Provide the URL** you tried to use
5. I'll help debug further

---

## Support Information

### Debug Files Created
- `test-logo-urls.php` - URL validation tester
- `check-tables.php` - Database diagnostic

### Documentation
- `LOGO_URL_SECURITY_FIX.md` - Technical details
- `LOGO_URL_FIX_COMPLETE_SUMMARY.md` - Full documentation

### Configuration Files
- `wp-config.php` - Debug logging enabled

---

**Status:** Ready to debug  
**Version:** 1.4.3  
**Date:** November 5, 2025
