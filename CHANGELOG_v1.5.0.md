# EduBot Pro v1.5.0 - Changelog

**Release Date:** November 9, 2025  
**Previous Version:** 1.4.2  
**Commit:** `7dfb0dd` + version bump

---

## 🎉 Major Features

### MCB Admin Interface & Manual Sync
- **Manual MCB Sync Button** on Applications list page
- One-click sync trigger with AJAX (no page reload)
- Real-time status updates with color-coded badges
- Inline sync status display in applications table
- Retry functionality for failed syncs

### Marketing Parameters Integration
- Enhanced MCB sync payload with 9 new marketing fields
- UTM parameter tracking (source, medium, campaign, content, term)
- Click ID support (Google Ads, Facebook Ads)
- IP address & lead source tracking
- Automatic extraction from enquiry data

### Admin Interface Enhancements
- Professional button styling with loading animations
- Color-coded status indicators (✓ Synced, ✗ Failed, ⊙ Pending)
- Toast notifications for sync results
- MCB reference ID display on successful sync
- Dark mode CSS support

---

## 📁 New Files

1. **`includes/class-edubot-mcb-admin.php`** (250 lines)
   - AJAX handler for manual sync requests
   - Admin page enhancements
   - Status display logic
   - Security validation & nonce checks

2. **`js/edubot-mcb-admin.js`** (120 lines)
   - Interactive sync button functionality
   - AJAX request management
   - Real-time UI updates
   - Notification system

3. **`css/edubot-mcb-admin.css`** (170 lines)
   - Professional button styling
   - Status badge styling
   - Loading animations
   - Responsive design

---

## 🔄 Modified Files

1. **`edubot-pro.php`**
   - Version bumped: 1.4.2 → 1.5.0
   - Added MCB admin class loader
   - Updated plugin constant: EDUBOT_PRO_VERSION

2. **`includes/class-edubot-mcb-service.php`**
   - Enhanced `prepare_mcb_data()` method
   - Added marketing parameter extraction
   - Added 9 new MCB payload fields:
     - UTMSource, UTMMedium, UTMCampaign, UTMContent, UTMTerm
     - GClickID, FBClickID
     - IPAddress, CapturedFrom

---

## ✨ Features Breakdown

### Manual Sync Button
```
Location: Applications list → Actions column
Trigger: Click button next to View/Delete
Result: AJAX sync to MyClassBoard
Feedback: Button state changes + notification toast
```

### Status Column
```
Column: MCB Status
Display: Color-coded badge with status text
Options: Synced (✓), Failed (✗), Pending (⊙), Retrying (↻)
Info: Shows MCB ID when synced
```

### Marketing Data Fields
```
UTM Parameters:
  - utm_source: Traffic source (google, facebook, etc)
  - utm_medium: Traffic medium (cpc, organic, etc)
  - utm_campaign: Campaign name
  - utm_content: Ad variation
  - utm_term: Search keywords

Click IDs:
  - gclid: Google Ads click ID
  - fbclid: Facebook Ads click ID

Tracking:
  - ip_address: User's IP address
  - captured_from: "EduBot Chatbot" (source identifier)
```

---

## 🔐 Security Features

✅ AJAX Nonce validation  
✅ WordPress capability checks (`manage_options`)  
✅ Input sanitization on all POST data  
✅ Prepared SQL statements  
✅ Error message sanitization  
✅ HTTPS-only API communication  

---

## 🚀 Performance Improvements

- Singleton MCB Service pattern (loaded once)
- Efficient database queries with prepared statements
- Async error logging (non-blocking)
- GPU-accelerated CSS animations
- Minimal JavaScript dependencies

---

## 🔧 Database Changes

### New Columns (already existed, now used)
- `wp_edubot_enquiries.utm_data` (JSON)
- `wp_edubot_enquiries.click_id_data` (JSON)
- `wp_edubot_enquiries.gclid` (varchar)
- `wp_edubot_enquiries.fbclid` (varchar)
- `wp_edubot_enquiries.ip_address` (varchar)

### Existing Table Enhanced
- `wp_edubot_mcb_sync_log` - Now tracks all marketing parameters

---

## 📊 API Changes

### MCB Payload Updated
Previous fields + New fields:

**NEW:**
```json
{
  "UTMSource": "google",
  "UTMMedium": "cpc",
  "UTMCampaign": "school-admissions",
  "UTMContent": "banner-ad",
  "UTMTerm": "best-schools",
  "GClickID": "Cj0KCQj...",
  "FBClickID": "6829348923...",
  "IPAddress": "192.168.1.100",
  "CapturedFrom": "EduBot Chatbot"
}
```

---

## 🧪 Testing Completed

✅ PHP syntax validation (all files)  
✅ JavaScript console validation  
✅ CSS rendering (responsive + dark mode)  
✅ AJAX nonce validation  
✅ Database queries  
✅ Admin capability checks  
✅ XAMPP deployment  
✅ GitHub commit & push  

---

## 🐛 Bug Fixes

- None in this release (all features are new additions)

---

## ⚡ Performance Impact

- **Frontend:** +8KB CSS + 4KB JS (minified)
- **Backend:** Minimal impact - uses existing MCB service
- **Database:** Same tables, enhanced data usage
- **API:** No change to MCB endpoint, enhanced payload

---

## 📚 Documentation

- `MCB_ADMIN_INTERFACE_COMPLETE.md` - Full implementation guide
- `MCB_SYNC_IMPLEMENTATION_COMPLETE.md` - MCB sync system docs
- `MCB_SYNC_SYSTEM_EXPLAINED.md` - How MCB works

---

## 🔄 Deployment

**Deployed To:**
- ✅ XAMPP: `D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\`
- ✅ GitHub: Commit `7dfb0dd`

**Files Synced:**
- ✅ class-edubot-mcb-admin.php
- ✅ class-edubot-mcb-service.php (updated)
- ✅ edubot-pro.php (version updated)
- ✅ js/edubot-mcb-admin.js (new)
- ✅ css/edubot-mcb-admin.css (new)

---

## 📝 Breaking Changes

**None** - This is a backward-compatible update. All existing functionality remains unchanged.

---

## 🔮 Future Enhancements

- Bulk MCB sync operations
- Detailed sync history page
- Advanced filtering by MCB status
- Webhook support for MCB confirmations
- Custom field mapping configuration

---

## 🎯 Version Info

**Version:** 1.5.0  
**Release:** November 9, 2025  
**Status:** ✅ Production Ready  
**PHP Requirement:** 7.4+  
**WordPress:** 5.0+  

---

**Next Version Target:** 1.6.0 (Q4 2025)
