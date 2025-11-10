# MCB Preview Button Implementation - Complete Summary

## What Was Done

### 1. ✅ Fixed MCB Preview Data Extraction
**Problem:** The MCB preview tool was looking for fields that don't exist in the MCB payload (UTMSource, UTMMedium, etc.).

**Solution:** Updated `preview_mcb_data()` method to correctly extract marketing data from the enquiry's `utm_data` column:

```php
// BEFORE (BROKEN)
'marketing_data' => array(
    'utm_source' => $mcb_data['UTMSource'] ?? '',  // ❌ NOT in MCB payload
    'utm_medium' => $mcb_data['UTMMedium'] ?? '',
    ...
)

// AFTER (FIXED)
'marketing_data' => array(
    'utm_source' => $utm_data['utm_source'] ?? '',  // ✅ From enquiry utm_data
    'utm_medium' => $utm_data['utm_medium'] ?? '',
    ...
)
```

### 2. ✅ Added Preview Button to Applications Page
**Where:** Admin > Applications table (each row)

**What it does:**
- Shows action button "👁️ Preview" for every enquiry
- Clicking opens an inline modal (no page navigation needed)
- Displays all MCB data that will be sent to MCB
- Shows marketing parameters with capture status (✓ Captured / Not captured)

**Code added:**
- `includes/class-edubot-mcb-admin.php` - Added button to row actions
- `js/edubot-mcb-admin.js` - Added modal display and styling

### 3. ✅ Created Diagnostic Tool
**Location:** `http://localhost/demo/debug_utm_capture.php` (admin access required)

**Traces UTM parameter flow:**
```
URL ?utm_source=google
        ↓
URL Parameters Detection ($_GET)
        ↓
Cookie Status (edubot_utm_source, etc.)
        ↓
Session Data ($_SESSION)
        ↓
Database Enquiry Record
```

Shows at each stage whether data is present or missing, helping you identify exactly where the capture flow breaks.

## How to Use the New Preview Button

### Step 1: Go to Applications Page
```
WordPress Admin → EduBot → Applications
```

### Step 2: Find an Enquiry
You'll see a table with all submitted enquiries

### Step 3: Click the Preview Button
Click the "👁️ Preview" button in the Actions column

### Step 4: View MCB Data Modal
A modal will show:

#### Student Information Section
- Student Name
- Parent Name
- Email
- Phone
- Date of Birth

#### Academic Information Section
- Class ID (from grade mapping)
- Academic Year ID (from mapping)

#### MCB Configuration Section
- Organization ID (21)
- Branch ID (113)
- Lead Source ID (280 = Organic by default)

#### Marketing Attribution Data Section (NEW)
Shows capture status for:
- utm_source - ✓ Captured or Not captured
- utm_medium - ✓ Captured or Not captured
- utm_campaign - ✓ Captured or Not captured
- gclid (Google) - ✓ Captured or Not captured
- fbclid (Facebook) - ✓ Captured or Not captured

#### Complete MCB Payload (JSON)
Full JSON that will be sent to MCB API

## Understanding the "Not captured" Issue

When you see "Not captured" for all marketing parameters, it means the `utm_data` column in the database is empty.

### Possible Causes:

**1. URL doesn't have UTM parameters**
- If visitor goes to `http://localhost/demo/` (no ?utm_source=... etc)
- Plugin won't set UTM cookies
- **Solution:** Test with full URL:
  ```
  http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
  ```

**2. Form submitting marketing data as CUSTOM FIELDS (not URL params)**
- Your screenshot showed "Source: Google, Medium: cpc, Campaign: admissions_2025"
- If these are form input fields, they're NOT being captured
- Current system only captures from:
  - URL parameters (?utm_source=...)
  - Cookies (set by previous page)
  - Session storage
- **Solution:** Need to map form field "Source" → utm_source, etc.

**3. UTM Cookies not persisting**
- If cookies are set on page 1, but form is on page 2
- Cookies might expire or not be read properly
- **Solution:** Check with diagnostic tool

## How to Debug

### Run Diagnostic Tool
Visit: **http://localhost/demo/debug_utm_capture.php**

This will show:
- ✅ What's in URL ($_GET)
- ✅ What's in cookies
- ✅ What's in session
- ✅ What's in the database for the latest enquiry

### Identify the Breakage Point
- **URL has data but cookies don't?** → Plugin bootstrap issue
- **Cookies have data but database doesn't?** → Enquiry creation issue
- **Form fields show data but database doesn't?** → Form capture issue

## Files Modified

| File | Change | Lines |
|------|--------|-------|
| `includes/class-edubot-mcb-service.php` | Fix marketing_data extraction | 145-168 |
| `includes/class-edubot-mcb-admin.php` | Add preview button to actions | 106-113 |
| `js/edubot-mcb-admin.js` | Add preview modal handler | 24-189 |
| `D:\xampp\htdocs\demo\debug_utm_capture.php` | NEW diagnostic tool | Full file |
| `MCB_PREVIEW_BUTTON_GUIDE.md` | NEW documentation | Full file |

## What's Next?

1. **Test with UTM parameters in URL:**
   ```
   http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   ```
   Fill form → Submit → Check Preview → Should show "✓ Captured"

2. **If still showing "Not captured":**
   - Run diagnostic tool: `debug_utm_capture.php`
   - Identify where data is lost
   - Report which stage fails (URL → Cookies → Session → DB)

3. **If form fields are custom fields:**
   - We need to add mapping for those form fields
   - Or capture them differently
   - Provide the field names and we can implement the fix

## Technical Details

### UTM Data Flow (Current)
```
1. User visits with URL params: ?utm_source=google
                    ↓
2. Plugin bootstrap sets cookies: edubot_utm_source = "google"
                    ↓
3. User fills form and submits via AJAX
                    ↓
4. process_final_submission() calls get_utm_data()
                    ↓
5. get_utm_data() checks: $_GET → $_POST → $_SESSION → $_COOKIE
                    ↓
6. Returns array like: ['utm_source' => 'google', 'utm_medium' => 'cpc', ...]
                    ↓
7. Array is JSON encoded and saved to enquiry.utm_data
                    ↓
8. MCB preview tool retrieves and displays it
```

### Why Preview Shows "Not captured"
- Database enquiry.utm_data is empty
- Means step 7 above didn't happen
- Usually means step 5 returned empty array
- Which means URL/cookies/session didn't have the data

## Commit Information

- **Commit 1:** `cf962fd` - Add MCB Preview button to applications page
- **Commit 2:** `ccf53fa` - Add MCB preview button documentation and diagnostic tool
- **Pushed to:** GitHub master branch

---

**Implementation Date:** November 10, 2025  
**Status:** ✅ Complete - Ready for Testing  
**Next Action:** Test with UTM parameters and use diagnostic tool if needed
