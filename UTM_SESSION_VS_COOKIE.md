# UTM Storage - Quick Reference

## ⚡ TL;DR - 30 Second Answer

**Q: Where are UTM values stored - session or cookie?**

**A: SESSION (not cookies)**

```php
// Stored in:
$_SESSION['edubot_utm_source']    = 'google'
$_SESSION['edubot_utm_medium']    = 'cpc'
$_SESSION['edubot_utm_campaign']  = 'admissions_2025'

// NOT in:
setcookie()  ← Not used for UTM
```

---

## Storage Comparison

| Feature | Session | Cookie |
|---------|---------|--------|
| **Storage Location** | Server (file system) | Client (browser) |
| **Visible to User** | ❌ No (server-side) | ✅ Yes (browser) |
| **Security** | ✅ Secure | ⚠️ Can be stolen |
| **Size Limit** | Unlimited | ~4KB per cookie |
| **How Used** | `$_SESSION[]` | `$_COOKIE[]` |
| **Set Method** | Automatic | `setcookie()` |
| **Survives Page Reload** | ✅ Yes | ✅ Yes |
| **Survives Browser Close** | ❌ No | ✅ Yes |
| **Session ID Cookie** | PHPSESSID | - |
| **Auto-Expires** | After 24 min inactivity | Configurable |

---

## Code Proof

### WHERE CAPTURED - render_chatbot() Function

**File:** `includes/class-edubot-shortcode.php` (Lines 134-160)

```php
// Line 138: Start session
session_start();

// Lines 143-156: Loop through UTM params
foreach ($utm_params_to_capture as $param) {
    if (isset($_GET[$param])) {
        // Line 152: STORE IN SESSION (not cookie!)
        $_SESSION['edubot_' . $param] = sanitize_text_field($_GET[$param]);
    }
}
```

**Key Point:** Using `$_SESSION` = server-side storage ✅

---

### WHERE RETRIEVED - get_utm_data() Function

**File:** `includes/class-edubot-shortcode.php` (Lines 5655-5715)

```php
// Priority 1: Current request
if (isset($_GET[$param])) {
    $utm_data[$param] = sanitize_text_field($_GET[$param]);
    $_SESSION['edubot_' . $param] = $utm_data[$param];  // Update session
}
// Priority 2: Form data
elseif (isset($_POST[$param])) {
    $utm_data[$param] = sanitize_text_field($_POST[$param]);
}
// Priority 3: Session (old data)
elseif (isset($_SESSION['edubot_' . $param])) {
    $utm_data[$param] = sanitize_text_field($_SESSION['edubot_' . $param']);
}
```

**Key Point:** Checking `$_SESSION` = retrieving from server ✅

---

## Data Flow Visualization

```
┌─────────────────────────────────────────────────────────────┐
│  User Clicks Ad Link                                        │
│  ?utm_source=google&utm_medium=cpc&utm_campaign=ads_2025   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  Browser Makes Request                                      │
│  $_GET = {utm_source, utm_medium, utm_campaign, ...}       │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  Server: render_chatbot() Executes                          │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  foreach ($utm_params as $param) {                 │   │
│  │    if (isset($_GET[$param])) {                     │   │
│  │      $_SESSION['edubot_'.$param] = $_GET[$param]   │   │
│  │      // STORED IN SESSION!                         │   │
│  │    }                                                │   │
│  │  }                                                  │   │
│  └─────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
        ┌─────────────────────────────────┐
        │  Server Session File             │
        │  (sess_abc123def456)             │
        │                                  │
        │  edubot_utm_source=google       │
        │  edubot_utm_medium=cpc          │
        │  edubot_utm_campaign=ads_2025   │
        │                                  │
        └─────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  Client: PHPSESSID Cookie Sent                              │
│  (Only the Session ID, not the data!)                       │
│  PHPSESSID=abc123def456                                     │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  Browser Displays Chatbot                                   │
│  UTM data is on server, not in browser                      │
│  ✅ Secure!                                                 │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  User Submits Enquiry (AJAX)                                │
│  POST /wp-admin/admin-ajax.php                              │
│  Sends: PHPSESSID cookie (tells server which session)       │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  Server: handle_enquiry_submission()                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  $utm_data = $this->get_utm_data()                 │   │
│  │  // Reads from $_SESSION using PHPSESSID           │   │
│  │  // Retrieves: utm_source=google                   │   │
│  └─────────────────────────────────────────────────────┘   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│  Database: wp_edubot_enquiries                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  source = 'google'     ← From utm_source!          │   │
│  │  utm_source = 'google'                              │   │
│  │  utm_medium = 'cpc'                                │   │
│  │  utm_campaign = 'ads_2025'                         │   │
│  │  utm_data = {JSON with all data}                   │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## Session Storage Location

### File System Storage

**On Your Server:**
```
C:\xampp\tmp\
    ├── sess_abc123def456
    ├── sess_xyz789abc123
    ├── sess_pqr456mno123
    └── ...
```

**Session File Content:**
```
edubot_utm_source|s:6:"google";
edubot_utm_medium|s:3:"cpc";
edubot_utm_campaign|s:15:"admissions_2025";
edubot_utm_captured_at|s:19:"2025-11-05 14:32:45";
```

**PHP Serialization Format:**
```
Type|Length:"Value"
s     = string
i     = integer
a     = array
O     = object

Example: s:6:"google"
         s = string type
         6 = 6 characters
         "google" = the value
```

---

## Browser Perspective

### What the Browser Sees

**Browser Storage:**
```
Cookies:
  PHPSESSID = abc123def456

LocalStorage:
  (nothing UTM-related)

SessionStorage:
  (nothing UTM-related)
```

**Browser Does NOT See:**
```
- utm_source value ('google')
- utm_medium value ('cpc')
- utm_campaign value ('admissions_2025')

All hidden on server! ✅ Secure
```

---

## Server Perspective

### What the Server Has

**In Memory:**
```php
$_SESSION = array(
    'edubot_utm_source'       => 'google',
    'edubot_utm_medium'       => 'cpc',
    'edubot_utm_campaign'     => 'admissions_2025',
    'edubot_utm_captured_at'  => '2025-11-05 14:32:45',
    // + other session data
);
```

**On Disk:**
```
File: /tmp/sess_abc123def456
Content: (serialized $_SESSION data)
```

---

## When Data is Stored

```
Timeline:

T=0s     User clicks ad with utm_source=google
         ↓
         Browser makes request
         ↓
         Server receives $_GET['utm_source'] = 'google'
         
T=0.1s   render_chatbot() executes
         ↓
         $_SESSION['edubot_utm_source'] = 'google'  ← STORED HERE
         ↓
         Session file written to disk
         
T=0.5s   Chatbot HTML rendered
         ↓
         Browser displays chatbot
         ↓
         PHPSESSID cookie sent to browser
         
T=5s to T=24min   User interacts with chatbot
         ↓
         Session is STILL ACTIVE (on server)
         ↓
         Session timestamp updated
         
T=24min  User submits enquiry
         ↓
         Browser sends PHPSESSID
         ↓
         Server reads session file
         ↓
         $_SESSION['edubot_utm_source'] = 'google'  ← RETRIEVED
         ↓
         Enquiry saved with source='google'
```

---

## Session vs Cookie Details

### PHP Session (What We Use)

```php
// START
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// STORE
$_SESSION['edubot_utm_source'] = 'google';

// RETRIEVE
$source = $_SESSION['edubot_utm_source'];

// WHAT HAPPENS
// 1. Session file created: /tmp/sess_abc123def456
// 2. Session ID sent to client in PHPSESSID cookie
// 3. Data is NOT sent to client
// 4. On next request, PHPSESSID cookie sent back
// 5. Server reads session file using PHPSESSID
// 6. $_SESSION is populated from file
```

### Cookie Alternative (NOT Used)

```php
// NOT DONE IN OUR CODE
setcookie('utm_source', 'google');  // Would expose data
```

**Why NOT cookies:**
- ❌ Exposes sensitive data in browser
- ❌ Limited to 4KB total
- ❌ Client can modify values
- ❌ Increases browser data transmission

---

## Session Lifetime

```
Default PHP Session Duration:

session.gc_maxlifetime = 1440 seconds (24 minutes)

This means:
- If no activity for 24 minutes → Session auto-deleted
- Activity = any server request (page load, AJAX, etc.)
- New request = timer resets to 24 minutes
- Manual session destroy = session_destroy()

For Your Chatbot:
- User visits ad → Timer starts
- User stays on chatbot for < 24 min → Session active ✅
- User leaves and returns after 24 min → New session
- User can re-submit if within 24 min → Source preserved ✅
```

---

## Verification Commands

### Check Session Files on Server

```bash
# List all session files
ls -la /tmp/sess_*

# View session file content
cat /tmp/sess_abc123def456

# Check session size
du -sh /tmp/sess_*
```

### Check PHP Configuration

```php
// Add to debug page
<?php
echo "Session Save Path: " . ini_get('session.save_path');
echo "Session GC Max Lifetime: " . ini_get('session.gc_maxlifetime') . " seconds";
echo "Session Cookie Lifetime: " . ini_get('session.cookie_lifetime') . " seconds";
?>
```

### Check Active Sessions

```php
// Show all session data
<?php
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>
```

---

## Why NOT Cookies?

### Security Issue

**If we used cookies:**
```javascript
// Attacker could read this in browser console
document.cookie
// Result: "utm_source=google; utm_medium=cpc; ..."

// Attacker could modify it
document.cookie = "utm_source=attacker_site";

// Next request would send modified value
// Database would be polluted
```

**With sessions:**
```javascript
// Attacker sees only:
document.cookie
// Result: "PHPSESSID=abc123def456"

// Even if modified, server validates:
// - Session file doesn't exist
// - Can't create fake sessions
// - Server-side storage is secure ✅
```

---

## Summary Table

| Question | Answer |
|----------|--------|
| Where are UTM values stored? | PHP Session (`$_SESSION`) |
| Are cookies used? | Only PHPSESSID (session ID), not UTM data |
| Where is session data stored? | Server file system (`/tmp/sess_*`) |
| Is it secure? | ✅ Yes - server-side only |
| Can it persist across page reloads? | ✅ Yes - survives page refresh |
| Can it persist across browser close? | ❌ No - lost when browser closes |
| Session timeout? | 24 minutes of inactivity |
| How is it sent to browser? | PHPSESSID cookie only (not the data) |
| How is it retrieved? | Server reads session file using PHPSESSID |
| Can user see it? | ❌ No - not exposed to client |
| Can user modify it? | ❌ No - stored on server, server validates |

---

## Next Steps

1. **Run Debug Page:** 
   - `http://localhost/demo/debug_utm_capture.php`
   - Test with: `?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025`

2. **Watch Error Log:**
   - `wp-content/debug.log`
   - Should see: "Captured UTM parameter from request: utm_source = google"

3. **Check Database:**
   ```sql
   SELECT source, utm_source FROM wp_edubot_enquiries LIMIT 5;
   ```

4. **Test Complete Flow:**
   - Visit URL with UTM → Session captured ✅
   - Submit enquiry → Source saved correctly ✅
   - Check DB → Shows 'google' not 'chatbot' ✅

