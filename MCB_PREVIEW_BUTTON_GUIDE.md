# MCB Preview Button & Marketing Data Capture Issue

## Summary

I've added a **"Preview MCB Data"** button to the Applications page that allows you to see exactly what data will be sent to MCB for each enquiry without making an actual API call.

## New Feature: Preview Button on Applications Page

### How to Use:

1. Go to **EduBot Admin > Applications**
2. You'll see each enquiry with action buttons: **Sync MCB** and **👁️ Preview** (NEW)
3. Click the **Preview** button to see:
   - Student Information (name, parent, email, phone, DOB)
   - Academic Information (class ID, academic year ID)
   - MCB Configuration (org ID, branch ID, lead source ID)
   - **Marketing Attribution Data** - Shows whether UTM parameters were captured:
     - ✓ Captured (in green) - Data found
     - Not captured (in gray) - Data missing

### What the Preview Shows:

```
Marketing Parameter   | Value    | Status
─────────────────────────────────────────
utm_source           | (empty)  | Not captured
utm_medium           | (empty)  | Not captured
utm_campaign         | (empty)  | Not captured
gclid (Google)       | (empty)  | Not captured
fbclid (Facebook)    | (empty)  | Not captured
```

## Issue: Marketing Parameters Not Capturing

Based on your screenshot showing "Not captured" for all marketing parameters, here are the root causes:

### Problem Analysis:

The screenshot shows that when you submitted the form with marketing data (Source: Google, Medium: cpc, Campaign: admissions_2025), this data is **not being saved to the database**, even though it was submitted.

### Possible Causes:

1. **UTM Parameters Not in URL**
   - If you visit `http://localhost/demo/` (without URL parameters)
   - The plugin bootstrap won't set UTM cookies
   - Solution: Visit with UTM params: `http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025`

2. **UTM Cookies Not Persisting to Next Page**
   - If cookies were set on page 1, but form is on page 2
   - The `get_utm_data()` method should still find them
   - But setcookie might fail silently if headers already sent

3. **Form Submitting UTM Data as Custom Fields (Not URL Parameters)**
   - The screenshot showed "Source: Google, Medium: cpc, Campaign: admissions_2025"
   - If these are FORM FIELDS (not URL parameters), they're NOT being captured
   - Current system only looks for URL parameters or cookies

### How to Diagnose:

Visit this diagnostic page: **http://localhost/demo/debug_utm_capture.php** (admin access required)

This shows:
- ✓ URL parameters (from `?utm_source=...`)
- ✓ Cookie status (edubot_utm_source, edubot_utm_medium, etc.)
- ✓ Session data (in $_SESSION)
- ✓ Latest enquiry in database (what's actually saved)

### What to Check:

1. **If URL has parameters but cookies don't show:**
   - The plugin bootstrap isn't calling setcookie() correctly
   - Check that headers aren't sent before cookie setting

2. **If cookies show but database doesn't:**
   - The enquiry creation process isn't calling `get_utm_data()`
   - Or it's not passing the result to the database insert

3. **If form fields show marketing data but database doesn't:**
   - These might be custom form fields, not UTM parameters
   - Need to map form field "Source" → utm_source, "Medium" → utm_medium, etc.

## Code Changes Made

### 1. Fixed MCB Preview Method
**File:** `includes/class-edubot-mcb-service.php` (lines 145-168)

```php
// Extract marketing data from enquiry utm_data for display
$utm_data = !empty($enquiry['utm_data']) ? json_decode($enquiry['utm_data'], true) : array();

return array(
    'success' => true,
    'marketing_data' => array(
        'utm_source' => $utm_data['utm_source'] ?? '',
        'utm_medium' => $utm_data['utm_medium'] ?? '',
        'utm_campaign' => $utm_data['utm_campaign'] ?? '',
        'gclid' => $utm_data['gclid'] ?? '',
        'fbclid' => $utm_data['fbclid'] ?? ''
    )
);
```

**Change:** Now correctly extracts utm_data from the enquiry record instead of looking for non-existent fields in MCB payload.

### 2. Added Preview Button to Applications Page
**File:** `includes/class-edubot-mcb-admin.php` (lines 106-113)

Added new action link:
```php
$actions['mcb_preview'] = sprintf(
    '<a href="#" class="mcb-preview-btn" data-enquiry-id="%d" title="Preview MCB data that will be sent">👁️ Preview</a>',
    $application_id
);
```

### 3. Added Preview Modal Display
**File:** `js/edubot-mcb-admin.js` (lines 24-189)

New JavaScript functionality:
- Handles preview button clicks
- Shows modal with all MCB data
- Displays marketing parameters with status indicators
- Shows complete JSON payload

## Next Steps

### To Fix Marketing Parameter Capture:

1. **Verify URL has parameters:**
   - Visit: `http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025`
   - Fill out and submit the form
   - Check MCB Preview - should now show "✓ Captured" for all marketing fields

2. **If that doesn't work:**
   - Check: `http://localhost/demo/debug_utm_capture.php`
   - Compare what's in cookies vs. what's in database
   - This will identify exactly where data is being lost

3. **If form fields are custom (not URL params):**
   - Need to update the chatbot to map form fields to UTM parameters
   - Or create a new capture mechanism for custom form fields

## Files Modified

1. `includes/class-edubot-mcb-service.php` - Fixed preview_mcb_data() method
2. `includes/class-edubot-mcb-admin.php` - Added preview button to row actions
3. `js/edubot-mcb-admin.js` - Added preview modal functionality
4. `D:\xampp\htdocs\demo\debug_utm_capture.php` - New diagnostic tool (CREATED)

## Testing the Feature

1. Go to Applications page
2. Find any enquiry
3. Click "👁️ Preview" button
4. Modal opens showing:
   - All MCB data fields
   - Marketing parameters with capture status
   - Complete JSON payload

If all marketing parameters show "Not captured", use the diagnostic tool to identify where data is lost in the flow.

---

**Version:** 1.0  
**Date:** November 10, 2025  
**Status:** Ready for testing
