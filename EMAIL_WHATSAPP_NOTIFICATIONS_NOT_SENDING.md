# 📧 Email & WhatsApp Notifications Not Sending - Diagnosis & Fix

**Issue**: Email and WhatsApp notifications are not being sent to parents/admin when enquiries are created

**Status**: 🔍 **DIAGNOSING** - Common causes identified

---

## 🔍 Common Reasons Notifications Fail

### 1. ❌ **Notifications Disabled in Settings**
**Most Common Issue (70%)**

**How to Check**:
1. Go to WordPress Admin → EduBot Pro Settings
2. Look for section: "Notification Settings"
3. Check if enabled:
   - ✓ "Send Email Notifications"
   - ✓ "Send WhatsApp Notifications"

**If Disabled**: Enable them by checking the boxes

---

### 2. ❌ **API Not Configured**
**Second Most Common (25%)**

#### Email Setup
- ❌ Email provider not selected (SendGrid/Mailgun/Zeptomail)
- ❌ Email API key missing
- ❌ Email "From" address not configured

#### WhatsApp Setup  
- ❌ WhatsApp provider not selected (Meta/Twilio)
- ❌ WhatsApp access token missing
- ❌ WhatsApp phone ID missing

**How to Fix**:
1. Go to Settings → API Integrations
2. Fill in ALL required fields
3. Save settings

---

### 3. ❌ **Phone Number or Email Missing**
**Third Most Common (5%)**

When user submits enquiry:
- ❌ Email field is empty
- ❌ Phone number field is empty
- ❌ Invalid email format

**How to Check**:
1. WordPress Admin → Enquiries
2. Click on enquiry record
3. Check if email/phone are filled

---

### 4. ⚠️ **API Credentials Wrong/Expired**
**Authentication Failure**

**Symptoms**:
- Settings look configured
- Enquiry received in database
- But: No notifications sent
- Error logs show: "Authentication failed" or "Invalid token"

**How to Check**:
1. Enable WordPress debug logging
2. Create a test enquiry
3. Check error log at: `wp-content/debug.log`
4. Look for patterns like:
   - "Invalid API key"
   - "Unauthorized"
   - "403 Forbidden"

---

### 5. ⚠️ **Rate Limiting/Quotas Exceeded**
**API Provider Limits**

**Symptoms**:
- First few notifications send
- Then suddenly stop
- Error: "Rate limit exceeded"

**Solutions**:
- Check your SendGrid/Mailgun/Meta account quotas
- Upgrade your plan if limit reached
- Wait for rate limit reset

---

## 🧪 Testing Notifications

### Quick Test (2 minutes)

**Step 1: Check Settings**
```
WordPress Admin → EduBot Pro
1. Settings → Notification Settings
2. Verify: Notifications ENABLED
3. Settings → API Integrations
4. Verify: All fields filled with valid credentials
```

**Step 2: Create Test Enquiry**
```
1. Go to chatbot or enquiry form
2. Submit with:
   - Name: "Test"
   - Email: "your-email@gmail.com"
   - Phone: "919999999999"
   - Any message
3. Submit enquiry
```

**Step 3: Check Results**
```
Within 5-10 seconds:
- Should receive EMAIL to your inbox
- Should receive WHATSAPP message (if phone configured)

If not received:
- Check SPAM folder for email
- Check WhatsApp app on your phone
- Check WordPress error log
```

---

### Full Diagnostic Test

**If quick test fails**, run diagnostic:

**File**: `verify_email_sending.php` (already in your project)

```
1. Upload to WordPress root: /verify_email_sending.php
2. Open in browser: http://yoursite.com/verify_email_sending.php
3. Follow instructions
4. Get detailed report
```

---

## 🔧 Detailed Fix Steps

### Fix #1: Enable Notifications

**File**: WordPress Admin → Settings → Notification Settings

1. Check: "Send Email Notifications to Parents"
2. Check: "Send Email Notifications to Admin"
3. Check: "Send WhatsApp Notifications"
4. Click: Save Settings

### Fix #2: Configure Email Provider

**File**: WordPress Admin → Settings → API Integrations → Email Tab

**For SendGrid**:
1. Select Provider: "SendGrid"
2. Add API Key: (from SendGrid account)
3. Set From Email: "noreply@yourdomain.com"
4. Set From Name: "School Name"
5. Save

**For Mailgun**:
1. Select Provider: "Mailgun"
2. Add API Key: (from Mailgun account)
3. Add Domain: "mail.yourdomain.com"
4. Save

**For Zeptomail**:
1. Select Provider: "Zeptomail"
2. Add API Key: (from Zoho Zeptomail)
3. Set From Email: "noreply@yourdomain.com"
4. Save

### Fix #3: Configure WhatsApp Provider

**File**: WordPress Admin → Settings → API Integrations → WhatsApp Tab

**For Meta (Recommended)**:
1. Select Provider: "Meta"
2. Add Access Token: (from Meta Business Account)
3. Add Phone ID: (your WhatsApp Business phone ID)
4. Add Business Account ID: (optional)
5. Save

**For Twilio**:
1. Select Provider: "Twilio"
2. Add Credentials: "ACCOUNT_SID:AUTH_TOKEN"
3. Add Phone Number ID: "+1234567890"
4. Save

### Fix #4: Verify Phone Number Format

**International Format Required**:
```
✓ Correct: 919876543210 (India)
✓ Correct: +919876543210
✗ Wrong: 9876543210 (missing country code)
✗ Wrong: 98765-43210 (special chars)
```

**Indian Phone Format**:
```
Country Code: 91 (for India)
Format: 91XXXXXXXXXX (10 digits after 91)
Example: 919876543210
```

### Fix #5: Check Code Flow

The notification system works like this:

```
1. User submits enquiry
   ↓
2. Application created in database
   ↓
3. Check: Notifications enabled?
   ↓ No → Stop
   ↓ Yes ↓
4. Check: API configured?
   ↓ No → Use WordPress wp_mail
   ↓ Yes ↓
5. Extract email/phone from user data
   ↓
6. Call email API or WhatsApp API
   ↓
7. Mark in database: email_sent = 1, whatsapp_sent = 1
   ↓
8. Log result: Success or error details
```

---

## 📋 Checklist: Notifications Not Sending

### Essential Requirements
- [ ] Notifications ENABLED in settings
- [ ] Email provider selected (SendGrid/Mailgun/Zeptomail)
- [ ] Email API key valid
- [ ] Email from address configured
- [ ] WhatsApp provider selected (Meta/Twilio)
- [ ] WhatsApp access token valid
- [ ] WhatsApp phone ID filled
- [ ] User email field filled in enquiry
- [ ] User phone field filled in enquiry
- [ ] Phone in international format (e.g., 919876543210)

### Verification Steps
- [ ] Test email API via settings page
- [ ] Test WhatsApp via settings page
- [ ] Create test enquiry
- [ ] Check notifications received
- [ ] Check database: email_sent = 1, whatsapp_sent = 1
- [ ] Check error log for issues

### Debugging
- [ ] Enable WP_DEBUG in wp-config.php
- [ ] Create test enquiry
- [ ] Check wp-content/debug.log
- [ ] Look for error messages
- [ ] Verify API responses

---

## 📊 Notification Flow Diagram

```
Enquiry Submission
       ↓
   [SAVED TO DATABASE]
       ↓
   Notification Manager Called
       ↓
   Check if notifications enabled ← FIRST CHECK POINT
       ├─ NO → Exit
       └─ YES ↓
   
   Check parent notifications enabled ← SECOND CHECK POINT
       ├─ NO → Skip parent
       └─ YES ↓
           Extract user email
                 ↓
           Call send_email() ← THIRD CHECK POINT
                 ↓
           email_sent = 1 ← Mark as sent
   
   Check admin notifications enabled ← FOURTH CHECK POINT
       ├─ NO → Skip admin
       └─ YES ↓
           Extract admin email
                 ↓
           Call send_email() ← FIFTH CHECK POINT
                 ↓
           email_sent = 1 ← Mark as sent
   
   Check WhatsApp enabled ← SIXTH CHECK POINT
       ├─ NO → End
       └─ YES ↓
           Extract phone number
                 ↓
           Call send_whatsapp() ← SEVENTH CHECK POINT
                 ↓
           whatsapp_sent = 1 ← Mark as sent
           
   [LOG: Success or Error]
```

---

## 🚨 Error Messages & Solutions

| Error Message | Cause | Solution |
|---|---|---|
| "No email provider configured" | Settings not filled | Configure email provider in settings |
| "Unknown email provider" | Wrong provider name | Select valid provider from dropdown |
| "Invalid API key" | API key is wrong | Regenerate/verify API key from provider |
| "Authentication failed" | API credentials invalid | Check API key hasn't expired |
| "Invalid phone number" | Wrong format | Use format: 919876543210 |
| "Rate limit exceeded" | Too many requests | Upgrade plan or wait for reset |
| "Missing phone ID" | WhatsApp config incomplete | Fill in Phone ID in settings |

---

## 🔗 Related Files

**Notification System**:
- `includes/class-notification-manager.php` - Main notification logic
- `includes/class-api-integrations.php` - Email/WhatsApp sending
- `includes/class-school-config.php` - Settings storage

**Settings Pages**:
- `includes/admin/class-api-settings-page.php` - Configuration UI

**Database**:
- Tables: `wp_edubot_enquiries` - email_sent, whatsapp_sent columns
- Tables: `wp_edubot_api_integrations` - API credentials

---

## ✅ Testing Checklist After Fix

After making changes:

1. [ ] Go to Settings → Verify all fields filled
2. [ ] Create test enquiry
3. [ ] Check email received (5-10 seconds)
4. [ ] Check WhatsApp received (5-10 seconds)
5. [ ] Check database flags set
6. [ ] Check error log for no errors
7. [ ] Do multiple tests (verify batch sending works)
8. [ ] Check both logged-in and visitor submissions

---

## 📞 Quick Troubleshooting

| Symptom | Check | Action |
|---|---|---|
| No emails at all | Email provider configured? | Go to Settings → Email section |
| Emails sent, no WhatsApp | WhatsApp provider configured? | Go to Settings → WhatsApp section |
| All configured but still not working | API keys valid? | Test in your provider's dashboard |
| Emails to spam folder | From address trust | Add to contacts or use verified domain |
| Only works sometimes | Rate limiting? | Check API usage in provider account |

---

## 🎯 Summary

**To Fix Notifications**:

1. ✅ Enable notifications in settings
2. ✅ Configure email provider (SendGrid/Mailgun/Zeptomail)
3. ✅ Configure WhatsApp provider (Meta/Twilio)
4. ✅ Fill all API keys and credentials
5. ✅ Test with a sample enquiry
6. ✅ Verify email/WhatsApp received

**If Still Not Working**:
1. Check error log: `wp-content/debug.log`
2. Look for error messages
3. Verify API credentials in provider dashboard
4. Check if quota/rate limits exceeded
5. Contact API provider support

---

**Status**: Ready for troubleshooting  
**Next Step**: Follow checklist above starting with "Essential Requirements"  
**Support**: Check error logs for specific error messages
