# UTM Values Storage Flow - Complete Explanation

## Quick Answer
**UTM values are stored in SESSION, NOT cookies.**

---

## Storage Location

### 🔷 Primary Storage: PHP `$_SESSION`

UTM values are stored in **PHP Session** (server-side session storage), not cookies.

**Session Key Format:**
```php
$_SESSION['edubot_' . $param_name]
```

**Example Session Keys:**
```php
$_SESSION['edubot_utm_source']     // "google"
$_SESSION['edubot_utm_medium']     // "cpc"
$_SESSION['edubot_utm_campaign']   // "admissions_2025"
$_SESSION['edubot_utm_term']       // (if provided)
$_SESSION['edubot_utm_content']    // (if provided)
$_SESSION['edubot_utm_captured_at'] // Timestamp when captured
```

---

## Complete Capture Flow

### Step 1: User Visits URL with UTM Parameters

**Example URL:**
```
http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
```

**What's in `$_GET`:**
```php
$_GET = array(
    'utm_source'   => 'google',
    'utm_medium'   => 'cpc',
    'utm_campaign' => 'admissions_2025'
);
```

---

### Step 2: Page Loads - render_chatbot() Function Executes

**File:** `includes/class-edubot-shortcode.php` (Lines 134-160)

**Code:**
```php
public function render_chatbot($atts) {
    // CRITICAL: Capture UTM parameters immediately on page load
    // This ensures we get the current request's UTM data, not cached session data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Capture UTM parameters directly from current request
    $utm_params_to_capture = array(
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', '_kenshoo_clickid', 'irclickid'
    );
    
    foreach ($utm_params_to_capture as $param) {
        if (isset($_GET[$param])) {
            // Always update from current request (don't use old session data)
            $_SESSION['edubot_' . $param] = sanitize_text_field($_GET[$param]);
            error_log("EduBot: Captured UTM parameter from request: {$param} = " . sanitize_text_field($_GET[$param]));
        }
    }
    
    // Mark when UTM data was captured
    $_SESSION['edubot_utm_captured_at'] = current_time('mysql');
```

**What Happens:**
1. ✅ Session is started (if not already started)
2. ✅ Loop through all UTM parameters
3. ✅ For each UTM parameter found in `$_GET`, store it in `$_SESSION`
4. ✅ Log the captured values

**Result in Session:**
```php
$_SESSION['edubot_utm_source']     = 'google';
$_SESSION['edubot_utm_medium']     = 'cpc';
$_SESSION['edubot_utm_campaign']   = 'admissions_2025';
$_SESSION['edubot_utm_captured_at'] = '2025-11-05 14:32:45';
```

---

### Step 3: User Submits Enquiry via AJAX

**AJAX Request Sent** with form data:
- Student name
- Parent email
- Phone number
- Grade
- etc.

**Note:** UTM parameters are NOT re-sent in the AJAX request. They're already stored in the session from Step 2.

---

### Step 4: Server Retrieves UTM Data - get_utm_data() Function

**File:** `includes/class-edubot-shortcode.php` (Lines 5655-5715)

**Code:**
```php
private function get_utm_data() {
    $utm_data = array();
    
    // Check session first
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $utm_params = array(
        'utm_source', 'utm_medium', 'utm_campaign', 
        'utm_term', 'utm_content',
        'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', 
        '_kenshoo_clickid', 'irclickid', 'li_fat_id', 'sc_click_id', 'yclid'
    );
    
    foreach ($utm_params as $param) {
        // Check current request FIRST (priority 1)
        if (isset($_GET[$param])) {
            $utm_data[$param] = sanitize_text_field($_GET[$param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
            error_log("EduBot get_utm_data: Using UTM from current request: {$param}");
        }
        // Fallback to POST data (priority 2)
        elseif (isset($_POST[$param])) {
            $utm_data[$param] = sanitize_text_field($_POST[$param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
        }
        // Last resort: check session (priority 3)
        elseif (isset($_SESSION['edubot_' . $param])) {
            $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param]);
        }
    }
    
    // Add timestamp
    if (!empty($utm_data) && !isset($_SESSION['edubot_utm_captured_at'])) {
        $_SESSION['edubot_utm_captured_at'] = current_time('mysql');
    }
    
    if (isset($_SESSION['edubot_utm_captured_at'])) {
        $utm_data['captured_at'] = $_SESSION['edubot_utm_captured_at'];
    }
    
    return $utm_data;
}
```

**Priority Order:**
```
1. Current $_GET  (fresh URL parameters)
2. Current $_POST (form data)
3. $_SESSION      (stored session data)
```

**Returns:**
```php
$utm_data = array(
    'utm_source'       => 'google',
    'utm_medium'       => 'cpc',
    'utm_campaign'     => 'admissions_2025',
    'captured_at'      => '2025-11-05 14:32:45'
);
```

---

### Step 5: Source is Set from UTM Data

**File:** `includes/class-edubot-shortcode.php` (Lines 2420-2430)

**Code:**
```php
$utm_data = $this->get_utm_data();
$source = 'chatbot'; // Default

if (!empty($utm_data['utm_source'])) {
    // Use utm_source as the source (e.g., 'google', 'facebook', 'email', 'organic_search', 'direct')
    $source = sanitize_text_field($utm_data['utm_source']);
    error_log("EduBot: Source determined from UTM: " . $source);
}

// ... then save to database
'source' => $source
```

**Result:**
```php
$source = 'google';  // NOT 'chatbot'!
```

---

### Step 6: Enquiry Saved to Database

**File:** `wp_edubot_enquiries` table

**Columns:**
```sql
- enquiry_id
- enquiry_number
- source              ← 'google'
- utm_source          ← 'google'
- utm_medium          ← 'cpc'
- utm_campaign        ← 'admissions_2025'
- utm_data            ← JSON: {"utm_source":"google","utm_medium":"cpc",...}
- created_at
- (+ 20 other columns)
```

---

## Flow Diagram

```
User Visits URL
     ↓
http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
     ↓
$_GET = {utm_source, utm_medium, utm_campaign, ...}
     ↓
render_chatbot() executes (Page Load)
     ↓
CAPTURE: $_SESSION['edubot_utm_source'] = 'google'
         $_SESSION['edubot_utm_medium'] = 'cpc'
         $_SESSION['edubot_utm_campaign'] = 'admissions_2025'
     ↓
User interacts with chatbot
     ↓
User submits enquiry (AJAX)
     ↓
handle_enquiry_submission() executes (Server Side)
     ↓
RETRIEVE: get_utm_data() checks:
  1. $_GET (current request)
  2. $_POST (form data)
  3. $_SESSION (session storage)
     ↓
$utm_data = {utm_source: 'google', utm_medium: 'cpc', ...}
     ↓
$source = $utm_data['utm_source'] = 'google'
     ↓
INSERT into wp_edubot_enquiries
  source = 'google'
  utm_source = 'google'
  utm_campaign = 'admissions_2025'
  utm_data = JSON with all UTM values
```

---

## Session Storage Details

### Where is Session Data Stored?

**PHP Session Files Location:**
```
C:\xampp\tmp\sess_*
(or your configured session.save_path in php.ini)
```

**Session File Format:**
```
sess_abc123def456 contains:
edubot_utm_source|s:6:"google";
edubot_utm_medium|s:3:"cpc";
edubot_utm_campaign|s:15:"admissions_2025";
edubot_utm_captured_at|s:19:"2025-11-05 14:32:45";
```

### Session Lifetime

**Default PHP Session Timeout:**
- **Duration:** 24 minutes (1440 seconds) of inactivity
- **Cookie Name:** `PHPSESSID`

**This means:**
- ✅ UTM data persists if user stays within 24 minutes
- ✅ UTM data persists across multiple page visits
- ✅ UTM data persists across browser refresh
- ❌ UTM data is lost after 24 minutes of inactivity
- ❌ UTM data is lost when user clears cookies/closes browser

---

## Why SESSION and NOT Cookies?

### Cookies
❌ Would expose user data in browser (security risk)
❌ Limited to 4KB storage per cookie
❌ Would need explicit set via `setcookie()` function

### PHP Session
✅ Stored on server (secure)
✅ Only session ID sent to client in cookie
✅ Unlimited storage capacity
✅ Automatically managed by PHP
✅ Persists across page loads and AJAX requests
✅ Lost when session expires or user closes browser

---

## Captured UTM Parameters

### Standard Google UTM Parameters
```
utm_source    → Traffic source (google, facebook, email, organic, direct, etc.)
utm_medium    → Traffic medium (cpc, social, newsletter, organic, referral, etc.)
utm_campaign  → Campaign name (admissions_2025, fb_ads_nov, parent_outreach, etc.)
utm_term      → Search term (optional)
utm_content   → Ad content/variation (optional)
```

### Platform Click IDs (Also Captured)
```
gclid         → Google Ads Click ID
fbclid        → Facebook Click ID
msclkid       → Microsoft Ads Click ID
ttclid        → TikTok Click ID
twclid        → Twitter Click ID
_kenshoo_clickid → Kenshoo/Sizmek Click ID
irclickid     → Impact Radius Click ID
li_fat_id     → LinkedIn Click ID
sc_click_id   → Snapchat Click ID
yclid         → Yandex Click ID
```

---

## Testing: How to Verify Storage

### 1. Check Session During Page Load

**Debug Script:** `debug_utm_capture.php`
```
Location: http://localhost/demo/debug_utm_capture.php
Shows: Current $_GET params, $_SESSION data, Recent enquiries
```

### 2. Monitor Error Log

**WordPress Error Log:** `wp-content/debug.log`
```
Look for lines like:
EduBot: Captured UTM parameter from request: utm_source = google
EduBot get_utm_data: Using UTM from current request: utm_source = google
EduBot: Source determined from UTM: google
```

### 3. Check Database

**Query Recent Enquiries:**
```sql
SELECT 
  enquiry_number,
  source,
  utm_source,
  utm_medium,
  utm_campaign,
  utm_data
FROM wp_edubot_enquiries
ORDER BY created_at DESC
LIMIT 5;
```

### 4. Browser Developer Tools

**Check Session Cookie:**
1. Open Browser → F12 → Application → Cookies
2. Look for `PHPSESSID` cookie
3. This cookie ID links to the server-side session file

---

## Common Issues and Fixes

### ❌ Issue: UTM data not captured

**Cause:** Session not started
```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```
**Fix:** Already implemented in code ✅

### ❌ Issue: Old UTM data showing

**Cause:** Session data not being refreshed
```php
// Before (WRONG):
if (isset($_SESSION['edubot_utm_source'])) {
    $utm_data['utm_source'] = $_SESSION['edubot_utm_source'];  // Old data!
}

// After (CORRECT):
if (isset($_GET['utm_source'])) {
    $utm_data['utm_source'] = $_GET['utm_source'];  // Current request!
    $_SESSION['edubot_utm_source'] = $utm_data['utm_source'];  // Update session
}
```
**Fix:** Priority order changed - $_GET checked first ✅

### ❌ Issue: Source still showing "Chatbot"

**Cause:** Plugin code not updated (old version cached)
```
Solution: Version bumped to 1.4.0
Browser cache cleared
```
**Fix:** Already applied ✅

---

## Session Data Lifecycle

```
Timeline of Session Data:

T0:00 → User visits: ?utm_source=google
        ↓
        render_chatbot() captures
        $_SESSION['edubot_utm_source'] = 'google'
        
T0:05 → User stays on page
        Session still active ✅
        
T0:10 → User submits enquiry
        get_utm_data() retrieves: 'google'
        Enquiry saved with source='google' ✅
        
T0:15 → User leaves page
        Session still active (stored on server)
        
T24:00 → If no activity for 24 minutes
        PHP auto-clears session ❌
        
OR
        User closes browser
        Client-side session cookie deleted ❌
        Session can be recreated on next visit
```

---

## Summary

| Aspect | Details |
|--------|---------|
| **Storage Type** | PHP Session (`$_SESSION`) |
| **Storage Location** | Server-side (session file) |
| **Session Key Format** | `$_SESSION['edubot_' . param_name]` |
| **When Captured** | On page load (render_chatbot) |
| **When Retrieved** | On enquiry submission |
| **Session Timeout** | 24 minutes of inactivity |
| **Persistence** | Across page reloads, AJAX calls, browser refresh |
| **Security** | ✅ Secure (not exposed to client) |
| **Cookies** | ❌ Not used for UTM (only PHPSESSID cookie) |

---

## Next Steps

1. **Test with Debug Page:**
   - Visit: `http://localhost/demo/debug_utm_capture.php`
   - Click "Test with Google Ads URL"
   - Verify `$_SESSION` data is captured
   - Submit enquiry
   - Check if source shows "google"

2. **Check Database:**
   ```sql
   SELECT source, utm_source FROM wp_edubot_enquiries 
   WHERE utm_source = 'google' LIMIT 1;
   ```

3. **Monitor Logs:**
   - Watch `wp-content/debug.log` for capture messages
   - Look for: "Using UTM from current request: utm_source = google"

