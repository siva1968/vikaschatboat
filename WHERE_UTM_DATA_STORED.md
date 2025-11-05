# Where UTM Data is Stored - Complete Guide

## 📍 Storage Locations

### 1. **Browser URL Bar** (Entry Point)
```
http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
                       ↑ UTM parameters here
```
**Storage Type:** URL parameters
**Visible:** Yes (in address bar)
**Duration:** Only current request

---

### 2. **Server-Side: $_GET Array** (First Capture)
```php
// When page loads, PHP receives:
$_GET = array(
    'utm_source'   => 'google',
    'utm_medium'   => 'cpc',
    'utm_campaign' => 'admissions_2025'
);
```

**File:** `edubot-pro.php` (lines 50-80)
**Function:** `edubot_capture_utm_immediately()`

```php
foreach ($utm_params as $param) {
    if (isset($_GET[$param])) {  // ← UTM data here
        $value = sanitize_text_field($_GET[$param]);
        // Store in cookies and session...
    }
}
```

**Storage Type:** Server memory (request-specific)
**Visible:** No (server-side only)
**Duration:** Only during this HTTP request

---

### 3. **Browser Cookies** (30-Day Persistence) ⭐ PRIMARY STORAGE
```
Cookie: edubot_utm_source=google
Cookie: edubot_utm_medium=cpc
Cookie: edubot_utm_campaign=admissions_2025
Cookie: edubot_utm_captured_at=2025-11-05 14:32:45
```

**File:** `edubot-pro.php` (lines 50-80)
**Function:** `edubot_capture_utm_immediately()`

```php
setcookie(
    'edubot_utm_source',        // Cookie name
    'google',                   // Cookie value
    time() + (30 * 24 * 60 * 60), // Expires in 30 days
    '/',                        // Path: entire site
    'localhost',                // Domain
    false,                      // Secure: false (dev), true (prod)
    true                        // HttpOnly: true (secure)
);
```

**Storage Location:** Browser's cookie storage (client-side)
**Visible:** Yes (in DevTools → Application → Cookies)
**Duration:** 30 days (or until browser closes in some cases)
**Persistence:** Survives page refresh, browser close, return visits

```
Browser Cookie Storage:
├─ Name: edubot_utm_source
├─ Value: google
├─ Domain: localhost
├─ Path: /
├─ Expires: Dec 5, 2025 (30 days)
├─ Size: ~50 bytes
├─ HttpOnly: Yes (secure)
└─ Secure: No (development)
```

---

### 4. **PHP Session** (Server-Side Session)
```php
$_SESSION = array(
    'edubot_utm_source'       => 'google',
    'edubot_utm_medium'       => 'cpc',
    'edubot_utm_campaign'     => 'admissions_2025',
    'edubot_utm_captured_at'  => '2025-11-05 14:32:45',
    // ... other session data
);
```

**File:** `includes/class-edubot-shortcode.php` (lines 88-180)
**Function:** `capture_utm_to_cookies()`

```php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store in session
foreach ($utm_params as $param) {
    if (isset($_GET[$param])) {
        $_SESSION['edubot_' . $param] = sanitize_text_field($_GET[$param]);
    }
}
```

**Storage Location:** Server file system
**Physical File:** `C:\xampp\tmp\sess_abc123def456` (or `/tmp/sess_*` on Linux)
**Visible:** No (server-side only)
**Duration:** 24 minutes of inactivity (default PHP)
**Persistence:** Only while session exists (lost on browser close unless cookies refresh)

**Session File Format:**
```
edubot_utm_source|s:6:"google";
edubot_utm_medium|s:3:"cpc";
edubot_utm_campaign|s:15:"admissions_2025";
edubot_utm_captured_at|s:19:"2025-11-05 14:32:45";
```

---

### 5. **Database** (Permanent Record) ⭐ PERMANENT STORAGE
```sql
-- Table: wp_edubot_enquiries
SELECT 
  enquiry_id,
  enquiry_number,
  source,                   -- ← "google" (from utm_source)
  utm_source,               -- ← "google"
  utm_medium,               -- ← "cpc"
  utm_campaign,             -- ← "admissions_2025"
  utm_term,                 -- ← NULL (if not provided)
  utm_content,              -- ← NULL (if not provided)
  utm_data,                 -- ← JSON with all data
  created_at
FROM wp_edubot_enquiries
WHERE enquiry_number = 'ENQ-001';
```

**Result:**
```
enquiry_id: 1
enquiry_number: ENQ-001
source: google
utm_source: google
utm_medium: cpc
utm_campaign: admissions_2025
utm_term: NULL
utm_content: NULL
utm_data: {"utm_source":"google","utm_medium":"cpc","utm_campaign":"admissions_2025"}
created_at: 2025-11-05 14:32:45
```

**File:** `includes/class-edubot-shortcode.php` (lines 2420-2480)
**Function:** `handle_enquiry_submission()`

```php
// When enquiry submitted, data retrieved from cookies/session
$utm_data = $this->get_utm_data();
$source = $utm_data['utm_source'] ?? 'chatbot';

// Insert into database
$wpdb->insert(
    $wpdb->prefix . 'edubot_enquiries',
    array(
        'source'        => $source,              // ← 'google'
        'utm_source'    => $utm_data['utm_source'] ?? NULL,
        'utm_medium'    => $utm_data['utm_medium'] ?? NULL,
        'utm_campaign'  => $utm_data['utm_campaign'] ?? NULL,
        'utm_data'      => json_encode($utm_data),
        'created_at'    => current_time('mysql')
    )
);
```

**Storage Location:** MySQL database (`wp_edubot_enquiries` table)
**Columns:** 26 total columns including utm_source, utm_medium, utm_campaign
**Visible:** Yes (via SQL query or WordPress admin)
**Duration:** Permanent (until deleted)
**Persistence:** Forever

---

## 🔄 Data Flow Diagram

```
┌─────────────────────────────┐
│ 1. Browser URL              │
│ ?utm_source=google          │
└────────────┬────────────────┘
             │
             ↓ HTTP Request
┌─────────────────────────────┐
│ 2. Server $_GET Array       │
│ $_GET['utm_source']='google'│
└────────────┬────────────────┘
             │
             ├──→ setcookie()
             │    ↓
    ┌────────┴─────────────┐
    │ 3. Browser Cookies   │
    │ edubot_utm_source    │
    │ (30-day persistence) │
    └────────┬─────────────┘
             │
             ├──→ $_SESSION[]
             │    ↓
    ┌────────┴──────────────┐
    │ 4. PHP Session        │
    │ $_SESSION on server   │
    │ (24-min timeout)      │
    └────────┬──────────────┘
             │
             ├──→ User submits enquiry
             │    ↓
    ┌────────┴────────────────────┐
    │ 5. Database wp_enquiries    │
    │ source='google'             │
    │ utm_source='google'         │
    │ utm_data={...}              │
    └─────────────────────────────┘
```

---

## 📊 Storage Comparison

| Storage | Location | Visible | Duration | Can Retrieve |
|---------|----------|---------|----------|--------------|
| URL | Browser address bar | ✅ Yes | Current request only | From URL |
| $_GET | Server memory | ❌ No | Current request only | From URL |
| **Cookies** | **Browser storage** | ✅ **DevTools** | **30 days** | **Persist across visits** |
| $_SESSION | Server file system | ❌ No | 24 minutes | Current session |
| **Database** | **MySQL table** | ✅ **SQL query** | **Forever** | **Permanent record** |

---

## 🎯 Retrieval Priority

When you submit an enquiry, system retrieves UTM data in this order:

### Function: `get_utm_data()` 
**File:** `includes/class-edubot-shortcode.php` (lines 5725-5795)

```php
private function get_utm_data() {
    $utm_data = array();
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $utm_params = array('utm_source', 'utm_medium', 'utm_campaign', ...);
    
    foreach ($utm_params as $param) {
        // Priority 1: CURRENT REQUEST (fresh)
        if (isset($_GET[$param])) {
            $utm_data[$param] = sanitize_text_field($_GET[$param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
            // Found in URL! Use this.
        }
        
        // Priority 2: FORM DATA
        elseif (isset($_POST[$param])) {
            $utm_data[$param] = sanitize_text_field($_POST[$param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
        }
        
        // Priority 3: SESSION (intermediate storage)
        elseif (isset($_SESSION['edubot_' . $param])) {
            $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param]);
            // Session still valid, use this
        }
        
        // Priority 4: COOKIES (long-term persistence) ⭐
        elseif (isset($_COOKIE['edubot_' . $param])) {
            $utm_data[$param] = sanitize_text_field($_COOKIE['edubot_' . $param]);
            $_SESSION['edubot_' . $param] = $utm_data[$param];
            // Found in cookies! User returning after session expired
        }
    }
    
    return $utm_data;
}
```

**Scenarios:**

### Scenario 1: Immediate Submission (Same Page)
```
Timeline:
├─ 09:00 → User visits: ?utm_source=google
├─ 09:01 → Immediately submits enquiry
└─ 09:02 → Saves to database: source='google'

Retrieval Used: Priority 1 ($_GET) or Priority 3 ($_SESSION)
Storage Checked: $_GET (found!) or $_SESSION (found!)
Result: ✅ source = 'google'
```

### Scenario 2: Return After 1 Hour (Session Expired)
```
Timeline:
├─ 09:00 → User visits: ?utm_source=google
├─ 09:01 → Session created, cookies set
├─ 10:00 → User returns (session expired, 24-min timeout)
├─ 10:01 → Browser sends cookie: edubot_utm_source=google
└─ 10:02 → Submits enquiry

Retrieval Used: Priority 4 ($_COOKIE)
Storage Checked: 
  - $_GET (not present - no URL param)
  - $_POST (not present - not form data)
  - $_SESSION (empty - session expired)
  - $_COOKIE (FOUND!) ✅

Result: ✅ source = 'google' (from cookie!)
```

### Scenario 3: Return After 1 Month (Before Expiration)
```
Timeline:
├─ Nov 5 → User visits: ?utm_source=google
├─ Nov 5 → Cookies set: expires Dec 5
├─ Dec 4 → User returns (cookies still valid)
├─ Dec 4 → Browser sends cookie: edubot_utm_source=google
└─ Dec 4 → Submits enquiry

Retrieval Used: Priority 4 ($_COOKIE)
Storage Checked:
  - All same as above, COOKIE FOUND!

Result: ✅ source = 'google' (from 30-day cookie!)
```

### Scenario 4: Return After 45 Days (Cookie Expired)
```
Timeline:
├─ Nov 5 → User visits: ?utm_source=google
├─ Nov 5 → Cookies set: expires Dec 5
├─ Dec 16 → User returns (cookies EXPIRED and deleted by browser)
├─ Dec 16 → No cookies sent
└─ Dec 16 → Submits enquiry without UTM

Retrieval Used: Default 'chatbot'
Storage Checked:
  - $_GET (not present)
  - $_POST (not present)
  - $_SESSION (not present)
  - $_COOKIE (not present)

Result: ⚠️ source = 'chatbot' (no UTM data found)
```

---

## 📁 Physical Storage Locations

### On Your Computer

**Browser Cookies:**
```
Windows:
C:\Users\[YourName]\AppData\Local\[BrowserName]\User Data\Default\Cookies

Firefox:
C:\Users\[YourName]\AppData\Roaming\Mozilla\Firefox\Profiles\*\cookies.sqlite
```

**Server Session Files:**
```
Windows (XAMPP):
C:\xampp\tmp\sess_*

Linux:
/tmp/sess_*
```

**Database:**
```
MySQL Database: edubot_pro (or whatever your WordPress DB is named)
Table: wp_edubot_enquiries
Columns: source, utm_source, utm_medium, utm_campaign, utm_data
```

**WordPress Debug Log:**
```
D:\xamppdev\htdocs\demo\wp-content\debug.log
```

---

## 🔍 How to View Each Storage

### 1. Browser URL
```
Visible in: Address bar
Example: http://localhost/demo/?utm_source=google
```

### 2. Browser Cookies (DevTools)
```
Steps:
1. Press F12 (open DevTools)
2. Go to Application tab
3. Click Cookies
4. Select http://localhost
5. View: edubot_utm_source, edubot_utm_medium, etc.
```

### 3. Session Data
```
File: C:\xampp\tmp\sess_abc123...
Content: 
  edubot_utm_source|s:6:"google";
  edubot_utm_medium|s:3:"cpc";
```

### 4. PHP $_GET (During Request)
```
Only visible in logs or during request processing
Log: wp-content/debug.log
Message: "EduBot Bootstrap: Set cookie edubot_utm_source = google"
```

### 5. Database Records
```
Query:
SELECT * FROM wp_edubot_enquiries WHERE source='google' ORDER BY created_at DESC;

Or via WordPress Admin:
Dashboard → EduBot → Applications → [View Enquiry]
```

---

## 💾 Storage Timeline

```
Timeline of Single User Journey:

09:00 AM (Nov 5)
│
├─ User clicks ad: ?utm_source=google
│  Storage 1: Browser URL
│  Storage 2: $_GET array (server memory)
│
├─ setcookie() called
│  Storage 3: Browser Cookies (set to expire Dec 5)
│
├─ Session created
│  Storage 4: PHP Session file on server
│
└─ Chatbot loads: source still available ✅

09:05 AM
│
├─ User submits enquiry
│
├─ get_utm_data() retrieves: source = 'google'
│
└─ INSERT into database
   Storage 5: MySQL Database
   Data: source='google', utm_source='google'
   Status: PERMANENT ✅

10:00 AM (1 hour later)
│
├─ Session expires (24-min default)
│  Storage 4: DELETED
│
├─ Cookies still valid (30 days left)
│  Storage 3: STILL PRESENT ✅
│
└─ Data in database still there
   Storage 5: STILL PRESENT ✅

Dec 4 (29 days later)
│
├─ User returns
│
├─ Browser sends cookie
│  Storage 3: RETRIEVED FROM COOKIES ✅
│
└─ Submit enquiry: source = 'google'
   Storage 5: Another record with source='google' ✅

Dec 6 (31 days later)
│
├─ Cookie expires
│  Storage 3: DELETED BY BROWSER
│
├─ User returns with NO UTM
│
└─ Submit enquiry: source = 'chatbot' (default)
   Storage 5: Record with source='chatbot'
```

---

## 🎯 Summary

**Where is UTM Data Stored?**

| When | Where | Duration | Retrieved From |
|------|-------|----------|-----------------|
| **First Visit** | Browser URL | Current request | Direct URL |
| **Being Captured** | $_GET (server memory) | Current request | URL parameters |
| **Short-term (24 min)** | PHP Session file | Until session expires | Session data |
| **Long-term (30 days)** | Browser Cookies | 30 days | Cookie storage |
| **Permanent** | MySQL Database | Forever | SQL query |

**You now have:**
- ✅ Immediate capture from URL
- ✅ Short-term storage in session (24 min)
- ✅ **Long-term storage in cookies (30 days)** ⭐
- ✅ Permanent record in database

**The key innovation:** Cookies bridge the gap between session timeout and database, allowing you to capture the original source even if the user returns 1+ weeks later!

