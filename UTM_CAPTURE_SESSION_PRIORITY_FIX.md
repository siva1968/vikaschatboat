# ✅ UTM Capture FIX - Prioritize Current Request Over Session

## 🔧 Problem Identified

**Previous Issue:**
- User visited: `http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025`
- But source saved as: "Chatbot" 
- Root cause: Old session data was being used instead of current request parameters

## 🔨 Solution Applied

### Change #1: Capture UTM at Page Load (render_chatbot function)
```php
// CRITICAL: Capture UTM parameters immediately on page load
// This ensures we get the current request's UTM data, not cached session data

foreach ($utm_params_to_capture as $param) {
    if (isset($_GET[$param])) {
        // Always update from current request (don't use old session data)
        $_SESSION['edubot_' . $param] = sanitize_text_field($_GET[$param]);
        error_log("EduBot: Captured UTM parameter from request: {$param} = ...");
    }
}
```

### Change #2: Prioritize Current Request in get_utm_data()
```php
foreach ($utm_params as $param) {
    // Check current request FIRST (most important - don't use stale session data)
    if (isset($_GET[$param])) {
        $utm_data[$param] = sanitize_text_field($_GET[$param]);
        $_SESSION['edubot_' . $param] = $utm_data[$param];
    }
    // Fallback to POST data
    elseif (isset($_POST[$param])) {
        $utm_data[$param] = sanitize_text_field($_POST[$param]);
        $_SESSION['edubot_' . $param] = $utm_data[$param];
    }
    // Last resort: check session (old data)
    elseif (isset($_SESSION['edubot_' . $param])) {
        $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param]);
    }
}
```

## 🧪 How to Test (Complete Test Sequence)

### Test 1: Fresh Browser Session
1. **Clear all cookies/session** (or use Incognito/Private Window)
2. **Visit with Google UTM:**
   ```
   http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   ```
3. **Submit enquiry** with your details
4. **Expected Result:** Source should be "google" ✅

### Test 2: Switching Campaigns Mid-Session
1. **Visit first campaign URL (Google):**
   ```
   http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   ```
2. **Go to second campaign URL (Facebook) - DIFFERENT TAB SAME BROWSER:**
   ```
   http://localhost/demo/?utm_source=facebook&utm_medium=social&utm_campaign=fb_ads_nov
   ```
3. **Submit enquiry on Facebook page**
4. **Expected Result:** Source should be "facebook" (NOT "google") ✅

### Test 3: Direct URL Without UTM
1. **Visit direct (no UTM params):**
   ```
   http://localhost/demo/
   ```
2. **Submit enquiry**
3. **Expected Result:** Source should be "chatbot" (default) ✅

### Test 4: Multiple Campaigns Sequence
Create 3 test enquiries in sequence:

```
1. http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   → Submit → Source = "google"

2. http://localhost/demo/?utm_source=facebook&utm_medium=social&utm_campaign=fb_ads_nov
   → Submit → Source = "facebook"

3. http://localhost/demo/?utm_source=email&utm_medium=newsletter&utm_campaign=parent_outreach
   → Submit → Source = "email"
```

## ✅ Verification Steps

### In Database
```sql
SELECT 
    enquiry_number,
    student_name,
    source,
    utm_data,
    created_at
FROM wp_edubot_enquiries
ORDER BY created_at DESC
LIMIT 5;
```

**Expected Output:**
```
ENQ20251440 | Prasad | google | {"utm_source":"google","utm_medium":"cpc",...} | 2025-11-05 14:39:00
ENQ20251439 | Test1  | facebook | {"utm_source":"facebook","utm_medium":"social",...} | 2025-11-05 14:38:00
ENQ20251438 | Test2  | email | {"utm_source":"email","utm_medium":"newsletter",...} | 2025-11-05 14:37:00
ENQ20251437 | Test3  | chatbot | NULL or empty | 2025-11-05 14:36:00
```

### In Debug Log
Look for entries like:
```
[05-Nov-2025 14:39:00] EduBot: Captured UTM parameter from request: utm_source = google
[05-Nov-2025 14:39:00] EduBot get_utm_data: Using UTM from current request: utm_source = google
[05-Nov-2025 14:39:00] EduBot: Source determined from UTM: google
[05-Nov-2025 14:39:00] EduBot: Successfully saved enquiry ENQ20251440 to database with ID 10
```

## 📊 Expected Behavior After Fix

| Scenario | URL | Expected Source | Status |
|----------|-----|-----------------|--------|
| Google Ads click | `?utm_source=google&...` | google | ✅ |
| Facebook ad click | `?utm_source=facebook&...` | facebook | ✅ |
| Email link click | `?utm_source=email&...` | email | ✅ |
| Organic search | `?utm_source=organic_search&...` | organic_search | ✅ |
| Direct visit | (no params) | chatbot | ✅ |
| Multiple campaigns (different visit) | URL changes | Uses NEW URL params | ✅ |

## 🔄 What Changed

### Priority Order (NEW)
1. **Current Request** (`$_GET` params) - Always checked FIRST
2. **POST Data** - Fallback if not in GET
3. **Session Data** - Last resort if not in GET or POST

### Priority Order (OLD)
1. **Session Data** - Checked first (could be stale)
2. **Current Request** - Fallback (too late)

## ✅ Deployment Complete

- [x] Capture UTM on page load in `render_chatbot()`
- [x] Prioritize current request over session in `get_utm_data()`
- [x] Fixed source determination logic
- [x] Deployed to WordPress
- [x] Ready for testing

---

## 🚀 Test Now!

Use campaign URLs from the **Test Different Sources** page:

**http://localhost/demo/test_different_sources.php**

Click the campaign links and submit test enquiries to verify UTM capture is working!

---

**Status: ✅ READY FOR TESTING**

The system will now properly capture and use the current request's UTM parameters instead of relying on cached session data.

