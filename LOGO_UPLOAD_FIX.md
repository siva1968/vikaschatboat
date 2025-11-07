# Logo Upload Fix

## Problem
Error: "Logo URL failed security validation. Please use a safe URL without JavaScript or suspicious content."

## Root Cause
The logo URL format is incorrect. Common mistakes:
- ❌ `wp-content/uploads/logo.png` (missing leading slash)
- ❌ Relative path without `/` at start
- ❌ Invalid URL format

## Solution

### ✅ Valid Logo URL Formats

**Option 1: Absolute URL (Recommended)**
```
https://epistemo.in/wp-content/uploads/2024/01/logo.png
https://yourdomain.com/path/to/logo.png
```

**Option 2: Relative URL with Leading Slash**
```
/wp-content/uploads/2024/01/logo.png
/uploads/logo.png
```

### ❌ Invalid Formats

```
wp-content/uploads/logo.png          ← Missing / at start
../uploads/logo.png                  ← Invalid relative path
C:\uploads\logo.png                  ← Windows path not allowed
```

## Quick Fix Steps

### Method 1: Upload via WordPress Media Library (Easiest)

1. **Go to:** WordPress Admin → Media → Add New
2. **Upload** your logo image
3. **After upload:** Click on the image
4. **Copy** the "File URL" (looks like `https://yoursite.com/wp-content/uploads/...`)
5. **Paste** this URL in the EduBot logo field
6. **Save**

### Method 2: Use Existing Image URL

If your logo is already uploaded:

1. **Go to:** WordPress Admin → Media → Library
2. **Find** your logo image
3. **Click** on it
4. **Copy** the "File URL"
5. **Paste** in EduBot logo field

### Method 3: Manual URL Entry

If entering manually, use one of these formats:

**Full URL:**
```
https://epistemo.in/wp-content/uploads/2024/01/epistemo-logo.png
```

**Relative URL (note the leading slash):**
```
/wp-content/uploads/2024/01/epistemo-logo.png
```

## Testing Your Logo URL

Before saving, test your URL:

```bash
php test_logo_url.php "YOUR_LOGO_URL_HERE"
```

Example:
```bash
php test_logo_url.php "https://epistemo.in/logo.png"
```

Output will show:
- ✓ SUCCESS: URL is valid and safe!
- ✗ FAILED: With specific reason

## Common Errors & Fixes

### Error 1: "Invalid logo URL format"
**Fix:** Add `https://` at the beginning or `/` at the start

### Error 2: "Logo URL failed security validation"
**Fix:** Remove any of these:
- `javascript:`
- `data:`
- Special characters
- Event handlers (onclick, onerror, etc.)

### Error 3: Relative path not working
**Fix:** Add `/` at the beginning
- ❌ `wp-content/uploads/logo.png`
- ✅ `/wp-content/uploads/logo.png`

## Example Valid URLs

For Epistemo Vikas Leadership School:

```
https://epistemo.in/wp-content/uploads/2024/01/epistemo-logo.png
https://epistemo.in/assets/images/logo.png
/wp-content/uploads/2024/01/epistemo-logo.png
/wp-content/themes/epistemo/images/logo.png
```

## Recommended Approach

1. **Upload logo** via WordPress Media Library
2. **Get the URL** from Media Library
3. **Paste** the URL (it will be in correct format automatically)
4. **Save settings**

This ensures the URL is in the correct format and the file actually exists!

## Security Details

The validation blocks:
- XSS attacks (`javascript:`, `data:`)
- Invalid URL formats
- Relative URLs without leading `/`
- Non-HTTP/HTTPS protocols
- Malicious patterns

This is **intentional security** to protect your site!

## Still Having Issues?

1. Check the URL actually works (paste in browser)
2. Verify file exists on server
3. Run diagnostic: `php test_logo_url.php "your-url"`
4. Check file permissions
5. Try uploading via Media Library instead

## Quick Test

**Your URL should work in browser:**
- Paste the logo URL in a browser
- If image loads → URL is correct format
- If 404 error → File doesn't exist
- If download → Wrong content type

---

**Need Help?**
Run: `php test_logo_url.php "your-logo-url-here"`
