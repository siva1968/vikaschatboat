# UTM Long-Term Persistence Implementation - Summary

## ✅ Problem Solved

**Requirement:** "If user comes back after one month, I should still be able to capture source"

**Solution:** Store UTM parameters in **30-day persistent cookies** (not just session)

---

## 🎯 What Changed

### Version 1.4.2 - Cookie Implementation

**Before (v1.4.0):**
- UTM stored only in session
- Session expires after 24 minutes
- User returns after 1 hour → Source lost ❌

**After (v1.4.2):**
- UTM stored in BOTH session AND 30-day cookies
- Session expires after 24 minutes (still used for immediate requests)
- Cookies persist for 30 days
- User returns after 1 hour → Source recovered from cookie ✅
- User returns after 1 month → Source still available ✅

---

## 📍 Technical Details

### Storage Strategy

```
Layer 1: URL Parameters ($_GET)
    ↓
Layer 2: Session ($_SESSION) - 24 minute expiry
    ↓
Layer 3: Cookies ($_COOKIE) - 30 day expiry
    ↓
Layer 4: Database - permanent record
```

### Cookie Details

| Property | Value |
|----------|-------|
| Cookie Names | `edubot_utm_source`, `edubot_utm_medium`, `edubot_utm_campaign`, etc. |
| Expiration | 30 days (2,592,000 seconds) |
| HttpOnly | ✅ Yes (JavaScript can't access - secure) |
| Secure Flag | ✅ Yes (HTTPS only in production) |
| Domain | Your site (e.g., localhost) |
| Path | / (entire site) |
| Values Captured | All UTM parameters + platform click IDs (Google, Facebook, etc.) |

### Cookies Created

When user visits: `?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025`

Cookies created:
```
edubot_utm_source    = "google"
edubot_utm_medium    = "cpc"
edubot_utm_campaign  = "admissions_2025"
edubot_utm_captured_at = "2025-11-05 14:32:45"

(Also captures if present:)
edubot_gclid         (Google Ads ID)
edubot_fbclid        (Facebook ID)
edubot_msclkid       (Microsoft Ads ID)
edubot_ttclid        (TikTok ID)
edubot_twclid        (Twitter ID)
(+ more platform IDs)
```

---

## 🔄 Implementation

### Code Changes

**File:** `includes/class-edubot-shortcode.php`

**1. Added new function:** `capture_utm_to_cookies()`
   - Runs on `plugins_loaded` hook (earliest execution)
   - Called BEFORE any WordPress output
   - Captures all UTM parameters to cookies

**2. Updated constructor:**
   - Added: `add_action('plugins_loaded', array($this, 'capture_utm_to_cookies'), 1);`
   - Priority 1 = earliest execution

**3. Updated function:** `get_utm_data()`
   - Added Priority 4: Check cookies as fallback
   - If session expired but cookies valid → retrieves from cookies
   - Re-populates session from cookies for current request

**4. Simplified function:** `render_chatbot()`
   - Removed duplicate cookie-setting code
   - Now just starts session (cookies already set earlier)

---

## 🧪 How to Test

### Test 1: Immediate Submission (Same Page Visit)

```
1. Visit: http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025

2. Debug page to verify cookies created:
   http://localhost/demo/debug_cookies.php?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025

3. Expected: Cookies section shows ✅
   - edubot_utm_source = google
   - edubot_utm_medium = cpc
   - edubot_utm_campaign = admissions_2025

4. Submit enquiry via chatbot

5. Check database:
   SELECT source FROM wp_edubot_enquiries ORDER BY created_at DESC LIMIT 1;
   Expected: source = "google" ✅
```

### Test 2: Return After Closing Browser

```
1. Visit: http://localhost/demo/?utm_source=facebook&utm_medium=social&utm_campaign=fb_ads_nov

2. Verify cookies created (debug page shows them)

3. Close browser completely (clears session)

4. Wait a few seconds

5. Re-open browser

6. Go to chatbot: http://localhost/demo/

7. Submit enquiry

8. Check database:
   Expected: source = "facebook" ✅ (retrieved from cookies)
```

### Test 3: Return After 30+ Days

```
1. Visit with UTM (cookies created)

2. Wait 30+ days (or manually delete old cookies and test)

3. Return to site

4. If cookies still valid (< 30 days):
   source = "original_source" ✅
   
5. If cookies expired (> 30 days):
   source = "chatbot" ⚠️ (expected behavior)
   (To extend persistence, increase cookie lifetime in code)
```

### Test 4: Multiple Campaign Sources

```
1. Visit: ?utm_source=google → source saved as "google"
2. Close browser
3. Wait 1 hour
4. Visit: ?utm_source=facebook → source updated to "facebook"
5. Close browser
6. Wait 1 hour
7. Visit chatbot (no UTM params) → cookies still available
8. Source retrieved from cookies = "facebook"
```

---

## 📊 Data Flow

### Timeline: User's Journey

```
Day 1, 10:00 AM
├─ User clicks Google Ads: ?utm_source=google&utm_medium=cpc
├─ Cookies created: edubot_utm_source = "google"
├─ Session created: $_SESSION['edubot_utm_source'] = "google"
└─ Chatbot rendered

Day 1, 10:05 AM
├─ User submits enquiry
├─ get_utm_data() checks:
│  ├─ $_GET['utm_source'] → Not present (no URL param)
│  ├─ $_POST['utm_source'] → Not present (not in form)
│  ├─ $_SESSION['edubot_utm_source'] → FOUND! = "google"
│  └─ Result: source = "google"
├─ Enquiry saved to database: source = "google"
└─ Notifications sent

Day 1, 11:00 AM
├─ User closes browser
├─ Session destroyed
└─ Cookies still valid (29.95 days remaining)

Day 1, 02:00 PM
├─ User opens browser
├─ Session is new (empty)
├─ BUT cookies still present: edubot_utm_source = "google"
└─ Continues browsing

Day 1, 02:05 PM
├─ User submits enquiry
├─ get_utm_data() checks:
│  ├─ $_GET['utm_source'] → Not present
│  ├─ $_POST['utm_source'] → Not present
│  ├─ $_SESSION['edubot_utm_source'] → Not present (new session)
│  ├─ $_COOKIE['edubot_utm_source'] → FOUND! = "google"
│  └─ Result: source = "google"
├─ Enquiry saved to database: source = "google"
└─ Notifications sent

... (many days pass, user doesn't visit) ...

Day 30, 10:00 AM
├─ Cookies still valid: edubot_utm_source = "google"
├─ User returns to site: ?utm_source=google (same campaign)
├─ Cookies refreshed with current timestamp
└─ TTL reset to 30 days

Day 30, 10:05 AM
├─ User submits enquiry
├─ Source = "google" ✅ (from either fresh URL or cookie)

... (more days pass) ...

Day 31, 10:00 AM
├─ Browser auto-deletes expired cookies ❌
├─ User returns to site
├─ get_utm_data() finds no cookies
├─ Source defaults to "chatbot"
└─ Enquiry saved: source = "chatbot"
```

---

## 🔐 Security Features

### HttpOnly Flag
```php
setcookie('name', 'value', $expire, '/', $domain, $secure, true);
                                                               ↑
                                                        HttpOnly = true
```
- Prevents JavaScript from accessing cookies
- Protects against XSS (Cross-Site Scripting) attacks
- Only sent to server, not accessible from frontend

### Secure Flag
```php
setcookie('name', 'value', $expire, '/', $domain, $secure, $httponly);
                                                   ↑
                                            Secure flag
```
- Cookies only sent over HTTPS
- Not sent over plain HTTP (in production)
- Protects against man-in-the-middle attacks

### Data Sanitization
```php
$param_value = sanitize_text_field($_GET[$param]);
// Removes HTML/PHP, prevents injection
```

---

## 🎯 Captured Parameters

### Standard UTM Parameters
```
utm_source    → Where traffic comes from (google, facebook, email, organic, direct)
utm_medium    → Type of traffic (cpc, social, newsletter, organic, referral)
utm_campaign  → Campaign identifier (admissions_2025, fb_ads_nov, parent_outreach)
utm_term      → Search term (optional)
utm_content   → Ad variation (optional)
```

### Platform Click IDs (Also Captured)
```
gclid         → Google Ads Click ID
fbclid        → Facebook Click ID
msclkid       → Microsoft Ads Click ID
ttclid        → TikTok Click ID
twclid        → Twitter Click ID
_kenshoo_clickid → Kenshoo Click ID
irclickid     → Impact Radius Click ID
li_fat_id     → LinkedIn Click ID
sc_click_id   → Snapchat Click ID
yclid         → Yandex Click ID
```

---

## 📈 Use Cases Solved

### Use Case 1: Same-Day Enquiry
**Scenario:** User clicks ad, visits chatbot, submits enquiry immediately
```
Session: ✅ Works
Cookie: Not needed (session still active)
Result: Source captured ✅
```

### Use Case 2: Multi-Visit Same Day
**Scenario:** User clicks ad, leaves, returns within hours, submits enquiry
```
Session: ❌ Expired after 24 minutes
Cookie: ✅ Still valid (30 days)
Result: Source captured ✅ (from cookie)
```

### Use Case 3: Return After Days
**Scenario:** User clicked ad on Monday, returns Thursday, submits enquiry
```
Session: ❌ Lost (browser closed)
Cookie: ✅ Still valid (30 days)
Result: Source captured ✅ (from cookie)
```

### Use Case 4: Campaign Attribution
**Scenario:** Track which campaign brought user (Google Ads vs Facebook vs Email)
```
Each user gets cookie with original utm_source
Across multiple visits within 30 days
All enquiries tied to same source
Result: Accurate campaign attribution ✅
```

---

## 🛠️ Configuration Options

### Change Cookie Duration

**File:** `includes/class-edubot-shortcode.php`
**Function:** `capture_utm_to_cookies()`

Change this line:
```php
// Current (30 days):
$cookie_lifetime = time() + (30 * 24 * 60 * 60);

// To extend to 90 days:
$cookie_lifetime = time() + (90 * 24 * 60 * 60);

// To extend to 6 months:
$cookie_lifetime = time() + (180 * 24 * 60 * 60);

// To extend to 1 year:
$cookie_lifetime = time() + (365 * 24 * 60 * 60);
```

### Change Cookie Name Prefix

If you want cookies named differently (e.g., `myschool_utm_source` instead of `edubot_utm_source`):

Search for: `'edubot_' . $param`
Replace with: `'myschool_' . $param`

---

## 📝 Files Modified

| File | Changes | Lines |
|------|---------|-------|
| `includes/class-edubot-shortcode.php` | Added `capture_utm_to_cookies()` | +85 |
| `includes/class-edubot-shortcode.php` | Modified constructor to hook function | +1 |
| `includes/class-edubot-shortcode.php` | Updated `get_utm_data()` for cookie fallback | +15 |
| `includes/class-edubot-shortcode.php` | Simplified `render_chatbot()` | -50 |
| `edubot-pro.php` | Version bump | 1.4.1 → 1.4.2 |

---

## 📊 Database Implications

### Query to Check Source Attribution

```sql
-- See all enquiries with their source
SELECT 
  enquiry_number,
  source,
  utm_source,
  utm_medium,
  utm_campaign,
  student_email,
  created_at
FROM wp_edubot_enquiries
WHERE source IS NOT NULL
ORDER BY created_at DESC;

-- Group by source for campaign analysis
SELECT 
  source,
  COUNT(*) as enquiry_count,
  COUNT(DISTINCT student_email) as unique_contacts,
  DATE(MIN(created_at)) as first_enquiry,
  DATE(MAX(created_at)) as last_enquiry
FROM wp_edubot_enquiries
WHERE source IN ('google', 'facebook', 'email', 'organic_search')
GROUP BY source
ORDER BY enquiry_count DESC;
```

---

## ✅ Testing Checklist

- [ ] Deploy version 1.4.2
- [ ] Clear all localhost cookies
- [ ] Visit debug page with UTM: `http://localhost/demo/debug_cookies.php?utm_source=google&utm_medium=cpc`
- [ ] Verify cookies show in DevTools (F12 → Application → Cookies)
- [ ] Submit enquiry via chatbot
- [ ] Check database: `SELECT source FROM wp_edubot_enquiries ORDER BY created_at DESC LIMIT 1;`
- [ ] Verify source = "google" (not "chatbot")
- [ ] Close browser completely
- [ ] Wait 1 hour
- [ ] Return to chatbot page (without UTM params)
- [ ] Submit another enquiry
- [ ] Verify source still = "google" (from cookies, not session)
- [ ] Test with different UTM sources (facebook, email, etc.)

---

## 🚀 Deployment Status

**Version:** 1.4.2
**Deployed:** November 5, 2025
**Files Modified:** 2 (class-edubot-shortcode.php, edubot-pro.php)
**Status:** ✅ Ready for Testing

---

## 📞 Support

### Common Issues

**Q: Cookies not appearing in DevTools?**
- A: Hard refresh (Ctrl+F5), check if plugin is activated, verify WordPress debug log

**Q: Getting "headers already sent" error?**
- A: The old cookie code was running too late. New version runs at plugins_loaded hook.

**Q: Want cookies to last longer than 30 days?**
- A: Update the cookie lifetime in `capture_utm_to_cookies()` function

**Q: Want to disable cookies and use only session?**
- A: The session fallback still works. Remove `setcookie()` calls if you prefer.

---

## 📚 Documentation Files Created

- `UTM_SESSION_VS_COOKIE.md` - Explanation of session vs cookies
- `UTM_STORAGE_FLOW.md` - Complete data flow documentation
- `UTM_COOKIE_CAPTURE_FIXED.md` - Detailed implementation guide
- `debug_cookies.php` - Interactive debug page to verify cookies working

**Next Steps:**
1. Test with debug page
2. Verify cookies captured
3. Submit enquiries
4. Confirm source field shows correct campaign
5. Return after some time and verify persistence
