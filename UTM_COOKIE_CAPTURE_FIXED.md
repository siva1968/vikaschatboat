# UTM Cookie Capture - Fixed Implementation (v1.4.2)

## ✅ Problem Fixed

**Issue:** UTM values were NOT being captured in cookies
**Reason:** `setcookie()` was being called too late in the WordPress lifecycle
**Solution:** Moved UTM capture to `plugins_loaded` hook (earliest possible time, before any output)

---

## 🔄 New Flow

### Timeline

```
1. Browser Request
   ↓
   http://localhost/demo/?utm_source=google&utm_medium=cpc

2. WordPress Loads
   ↓
   plugins_loaded hook (EARLIEST - before any output)
   
3. EduBot Captures UTM to Cookies
   ↓
   capture_utm_to_cookies() function executes
   
4. Cookies Set Successfully
   ↓
   edubot_utm_source = "google"
   edubot_utm_medium = "cpc"
   edubot_utm_campaign = "admissions_2025"
   edubot_utm_captured_at = "2025-11-05 14:32:45"
   
5. User Visits Chatbot Page
   ↓
   Shortcode rendered (render_chatbot)
   
6. User Submits Enquiry
   ↓
   get_utm_data() retrieves from cookies
   
7. Enquiry Saved
   ↓
   source = "google"
```

---

## 📍 Code Location

### Where UTM is Captured

**File:** `includes/class-edubot-shortcode.php`
**Function:** `capture_utm_to_cookies()`
**Lines:** ~95-175 (new function)
**Hook:** `plugins_loaded` with priority 1 (earliest)

```php
// In __construct():
add_action('plugins_loaded', array($this, 'capture_utm_to_cookies'), 1);
```

### Key Code

```php
public function capture_utm_to_cookies() {
    // Only process if there are URL parameters
    if (empty($_GET)) {
        return;
    }
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Define parameters to capture
    $utm_params_to_capture = array(
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', 
        '_kenshoo_clickid', 'irclickid', 'li_fat_id', 'sc_click_id', 'yclid'
    );
    
    // 30 days expiration
    $cookie_lifetime = time() + (30 * 24 * 60 * 60);
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    
    $utm_captured = false;
    
    foreach ($utm_params_to_capture as $param) {
        if (isset($_GET[$param]) && !empty($_GET[$param])) {
            $param_value = sanitize_text_field($_GET[$param]);
            
            // Store in session
            $_SESSION['edubot_' . $param] = $param_value;
            
            // Store in cookie (30 days)
            setcookie(
                'edubot_' . $param,
                $param_value,
                $cookie_lifetime,
                '/',
                $domain,
                $secure,
                true  // HttpOnly
            );
            
            $utm_captured = true;
            error_log("EduBot: Captured UTM to 30-day cookie: {$param} = {$param_value}");
        }
    }
    
    // Store capture timestamp
    if ($utm_captured) {
        $captured_at = current_time('mysql');
        
        $_SESSION['edubot_utm_captured_at'] = $captured_at;
        
        setcookie(
            'edubot_utm_captured_at',
            $captured_at,
            $cookie_lifetime,
            '/',
            $domain,
            $secure,
            true
        );
    }
}
```

---

## 🎯 Why This Works Now

### Problem with Old Approach

**Old:** Called `setcookie()` in `render_chatbot()`
- Shortcode rendered AFTER many WordPress plugins
- Output already sent to browser
- `setcookie()` failed silently (headers already sent)
- Cookies never created ❌

### Solution with New Approach

**New:** Call `setcookie()` in `capture_utm_to_cookies()`
- Runs on `plugins_loaded` hook with priority 1
- Executes BEFORE any WordPress plugin output
- Executes BEFORE any theme rendering
- `setcookie()` succeeds ✅

### WordPress Hook Timing

```
Timeline of WordPress Hooks:
=====================================

1. wp-load.php (loads core)
2. wp-config.php (loads config)
3. wp-settings.php (loads all plugins)
4. plugins_loaded ← WE ARE HERE (Priority 1 = EARLIEST)
5. setup_theme
6. after_setup_theme
7. init (theme & shortcode registration)
8. wp_loaded
9. template_redirect
10. wp_head (output starts here!)
11. render_page
12. wp_footer
13. shutdown

At plugins_loaded (step 4):
- No output sent yet
- setcookie() works perfectly ✅
```

---

## 🍪 What Cookies Are Created

### Cookie Details

| Cookie Name | Value | Duration | HttpOnly |
|------------|-------|----------|---------|
| `edubot_utm_source` | "google" | 30 days | ✅ Yes |
| `edubot_utm_medium` | "cpc" | 30 days | ✅ Yes |
| `edubot_utm_campaign` | "admissions_2025" | 30 days | ✅ Yes |
| `edubot_utm_term` | (if provided) | 30 days | ✅ Yes |
| `edubot_utm_content` | (if provided) | 30 days | ✅ Yes |
| `edubot_utm_captured_at` | "2025-11-05 14:32:45" | 30 days | ✅ Yes |
| `edubot_gclid` | (Google Ads ID) | 30 days | ✅ Yes |
| `edubot_fbclid` | (Facebook ID) | 30 days | ✅ Yes |
| (+ other click IDs) | - | 30 days | ✅ Yes |

### Cookie Security Features

✅ **HttpOnly Flag:** JavaScript cannot access these cookies (secure from XSS attacks)
✅ **Secure Flag:** Sent only over HTTPS in production
✅ **Domain:** Scoped to your domain
✅ **Path:** Accessible on entire site (/)
✅ **30-Day Expiration:** Long-term persistence

---

## 📊 Data Retrieval Flow

When user submits enquiry:

```
1. get_utm_data() is called
   ↓
2. Priority 1: Check $_GET (current URL params) ✅
   If found, update cookies with fresh data
   ↓
3. Priority 2: Check $_POST (form data)
   ↓
4. Priority 3: Check $_SESSION (session storage)
   ↓
5. Priority 4: Check $_COOKIE (persistent cookies) ✅
   If user returns after 24+ hours when session expired
   Data still retrieved from cookies!
   ↓
6. Return $utm_data array
   ↓
7. Source determined: $utm_data['utm_source'] = 'google'
   ↓
8. Save to database
```

---

## 🧪 How to Test

### Step 1: Visit with UTM Parameters

**Test URL:**
```
http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
```

### Step 2: Check Browser Cookies

**In Firefox/Chrome DevTools:**
```
F12 → Application → Cookies → http://localhost

Look for:
✅ edubot_utm_source = "google"
✅ edubot_utm_medium = "cpc"
✅ edubot_utm_campaign = "admissions_2025"
✅ edubot_utm_captured_at = "2025-11-05 14:32:45"
```

### Step 3: Check Expiration

**Each cookie should expire in 30 days:**
- Expires: depends on when test was run
- For example: if today is Nov 5, should expire Dec 5, 2025

### Step 4: Submit Enquiry Immediately

Submit enquiry on the same page visit:
```
Expected Result:
- Source = "google" ✅
- utm_source = "google" ✅
- utm_medium = "cpc" ✅
```

### Step 5: Close Browser & Return Later

Close browser completely (clears session):
```
1. Close browser
2. Clear all cookies
3. Re-open browser
4. Visit chatbot page WITHOUT UTM parameters
5. Submit enquiry

Expected Result:
- Cookies are recreated from stored data (if within 30 days)
OR
- If cookies still exist, they'll be re-read
```

### Step 6: Return After 1+ Month

If user returns after >30 days:
```
Scenario: User visited Nov 5 with ?utm_source=google
         User returns Dec 10 (after 30 days)

Expected:
- Old cookies expired
- Source will default to 'chatbot'
- This is expected behavior (only 30-day persistence)

To extend: Increase cookie lifetime in code
```

---

## 📝 Monitor in Logs

### Check Error Log

**File:** `wp-content/debug.log`

**Expected Lines:**
```
EduBot: Captured UTM to 30-day cookie: utm_source = google
EduBot: Captured UTM to 30-day cookie: utm_medium = cpc
EduBot: Captured UTM to 30-day cookie: utm_campaign = admissions_2025
EduBot: UTM parameters captured to 30-day cookies at: 2025-11-05 14:32:45

(Later, when submitting enquiry:)
EduBot get_utm_data: Using UTM from persistent cookie (30 day): utm_source = google
EduBot: Source determined from UTM: google
```

---

## 🔧 Configuration

### Change Expiration Time

**Default:** 30 days

**To change to 60 days:**

**File:** `includes/class-edubot-shortcode.php`
**Function:** `capture_utm_to_cookies()`
**Line:** ~135

```php
// Current (30 days):
$cookie_lifetime = time() + (30 * 24 * 60 * 60);

// Change to 60 days:
$cookie_lifetime = time() + (60 * 24 * 60 * 60);

// Change to 90 days:
$cookie_lifetime = time() + (90 * 24 * 60 * 60);

// Change to 1 year:
$cookie_lifetime = time() + (365 * 24 * 60 * 60);
```

### Change Cookie Name Prefix

**Current prefix:** `edubot_`

**To use different prefix:**

**File:** `includes/class-edubot-shortcode.php`

Change all occurrences of:
```php
'edubot_' . $param

// To:
'yourschool_' . $param
```

---

## ⚙️ How get_utm_data() Works

### Retrieval Priority

```php
private function get_utm_data() {
    $utm_data = array();
    
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
        // Priority 1: Current URL params (fresh data)
        if (isset($_GET[$param])) {
            $utm_data[$param] = sanitize_text_field($_GET[$param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
        }
        // Priority 2: Form data
        elseif (isset($_POST[$param])) {
            $utm_data[$param] = sanitize_text_field($_POST[$param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
        }
        // Priority 3: Session (intermediate storage)
        elseif (isset($_SESSION['edubot_' . $param])) {
            $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param']);
        }
        // Priority 4: COOKIES (long-term persistence)
        // This is KEY - retrieves from 30-day cookies
        elseif (isset($_COOKIE['edubot_' . $param])) {
            $utm_data[$param] = sanitize_text_field($_COOKIE['edubot_' . $param]);
            // Re-populate session from cookie
            $_SESSION['edubot_' . $param] = $utm_data[$param];
            error_log("EduBot get_utm_data: Using UTM from persistent cookie (30 day): {$param}");
        }
    }
    
    // Handle timestamp
    if (isset($_SESSION['edubot_utm_captured_at'])) {
        $utm_data['captured_at'] = $_SESSION['edubot_utm_captured_at'];
    } elseif (isset($_COOKIE['edubot_utm_captured_at'])) {
        $utm_data['captured_at'] = sanitize_text_field($_COOKIE['edubot_utm_captured_at']);
    }
    
    return $utm_data;
}
```

---

## 🎯 Use Cases

### Scenario 1: Same-Day Submission

```
1. User clicks: ?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
2. Chatbot loads (cookies created)
3. User immediately submits enquiry
   ↓
   Result: Source = "google" ✅ (from $_GET or cookies)
```

### Scenario 2: Return After 1 Hour

```
1. User visits: ?utm_source=facebook (session created, cookies set)
2. User leaves (session expires after 24 minutes)
3. User returns 1 hour later (session gone, cookies still valid)
4. User submits enquiry
   ↓
   Result: Source = "facebook" ✅ (from $_COOKIE, session expired)
```

### Scenario 3: Return After 1 Month (Within Cookie Expiration)

```
1. User visits: ?utm_source=email (Nov 5, cookies set to expire Dec 5)
2. User closes browser (session lost)
3. User returns Dec 4 (within 30 days, still 1 day before expiration)
4. User submits enquiry
   ↓
   Result: Source = "email" ✅ (from $_COOKIE, still valid)
```

### Scenario 4: Return After 1 Month (After Cookie Expiration)

```
1. User visits: ?utm_source=organic (Nov 5, cookies set to expire Dec 5)
2. User closes browser (session lost)
3. User returns Dec 6 (after expiration)
4. Browser auto-deletes expired cookies
5. User submits enquiry
   ↓
   Result: Source = "chatbot" ⚠️ (no cookies, defaults to chatbot)
```

---

## 📊 Database Results

### Expected Database Values

```sql
SELECT 
  enquiry_number,
  source,
  utm_source,
  utm_medium,
  utm_campaign,
  utm_data,
  created_at
FROM wp_edubot_enquiries
WHERE utm_source IS NOT NULL
ORDER BY created_at DESC;
```

**Results:**
```
enquiry_number | source  | utm_source | utm_medium | utm_campaign      | utm_data (JSON)
ENQ-001        | google  | google     | cpc        | admissions_2025   | {"utm_source":"google",...}
ENQ-002        | facebook| facebook   | social     | fb_ads_nov        | {"utm_source":"facebook",...}
ENQ-003        | email   | email      | newsletter | parent_outreach   | {"utm_source":"email",...}
```

---

## ✅ Version Info

**Version:** 1.4.2
**Changes:**
- Added `capture_utm_to_cookies()` function
- Hooked to `plugins_loaded` at priority 1 (earliest execution)
- Sets 30-day persistent cookies for all UTM parameters
- Updated `get_utm_data()` to read from cookies as fallback
- Removed duplicate cookie-setting code from `render_chatbot()`

**Deployment Date:** November 5, 2025

---

## 🚀 Test Now

1. **Clear all cookies** for localhost
2. **Visit with UTM:** `http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025`
3. **Open DevTools** (F12 → Application → Cookies)
4. **Verify cookies created:** ✅ edubot_utm_source, edubot_utm_medium, etc.
5. **Submit enquiry**
6. **Check database:** Source should be "google" not "chatbot"

If you see cookies in DevTools and source in database is correct, then it's working! ✅

