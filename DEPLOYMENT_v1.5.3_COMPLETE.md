# ✅ Version 1.5.3 Deployment Complete

**Date:** November 10, 2025  
**Status:** ✅ DEPLOYED to live WordPress  
**Version:** 1.5.3 (upgraded from 1.5.2)

## What's New in v1.5.3

### 🎯 Main Feature: MCB Preview Button
- **Location:** WordPress Admin → EduBot → Applications
- **Action:** Click "👁️ Preview" on any enquiry row
- **Result:** Modal popup showing complete MCB data and marketing parameter capture status

### 📊 Marketing Data Diagnostics
- New diagnostic tool: `http://localhost/demo/debug_utm_capture.php`
- Traces UTM parameters through entire flow: URL → Cookies → Session → Database
- Helps identify where marketing data capture breaks

### 🔧 Bug Fixes
- Fixed MCB preview data extraction (now correctly reads from enquiry utm_data)
- Fixed marketing data display in preview modal
- Added capture status indicators (✓ Captured / Not captured)

## Files Deployed

| Component | Files | Status |
|-----------|-------|--------|
| Main Plugin | `edubot-pro.php` | ✅ Deployed (v1.5.3) |
| Core Classes | `includes/*.php` (36 files) | ✅ Deployed |
| Admin Classes | `admin/*.php` (5 files) | ✅ Deployed |
| Admin Views | `admin/views/*.php` | ✅ Deployed |
| JavaScript | `js/edubot-mcb-admin.js` | ✅ Deployed |
| CSS | `css/edubot-mcb-admin.css` | ✅ Deployed |
| Diagnostic Tool | `debug_utm_capture.php` | ✅ Deployed |

## Deployment Location

**Live WordPress Site:** `D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\`

All files have been copied from the repository to the live installation.

## How to See Changes

### 1. Preview Button on Applications Page
```
WordPress Admin → EduBot → Applications
```
Each enquiry row now has a "👁️ Preview" button

### 2. Access MCB Preview Modal
Click any "👁️ Preview" button to see:
- Student information
- Academic information
- MCB configuration
- **Marketing Attribution Data** (new) showing capture status
- Complete JSON payload

### 3. Run Diagnostic Tool (Admin Only)
```
http://localhost/demo/debug_utm_capture.php
```
Shows UTM parameter flow through the system

## Testing Checklist

- [ ] Go to Applications page
- [ ] Find an enquiry
- [ ] Click "👁️ Preview" button
- [ ] Modal opens showing MCB data
- [ ] See marketing parameters with capture status
- [ ] Check if utm_source, utm_medium, utm_campaign show "✓ Captured" or "Not captured"

## If Marketing Shows "Not captured"

Follow these steps to debug:

1. **Visit with UTM parameters:**
   ```
   http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   ```

2. **Fill and submit the form**

3. **Go to Applications → Preview**
   - Should now show marketing parameters as "✓ Captured"

4. **If still "Not captured":**
   - Visit: `http://localhost/demo/debug_utm_capture.php`
   - Check which stage the data is lost at
   - Report findings for further investigation

## Version History

| Version | Release Date | Changes |
|---------|-------------|---------|
| 1.5.2 | Nov 9, 2025 | Previous stable version |
| 1.5.3 | Nov 10, 2025 | MCB Preview Button + Marketing Diagnostics |

## Repository

- **GitHub Repo:** https://github.com/siva1968/edubot-pro
- **Branch:** master
- **Latest Commits:**
  1. `409749f` - Bump version to 1.5.3
  2. `0333240` - Add quick start guide
  3. `b842559` - Add implementation guide
  4. `ccf53fa` - Add diagnostic tool
  5. `cf962fd` - Add MCB preview button to applications page

## Documentation

See the following docs for detailed information:

1. **QUICK_START_MCB_PREVIEW.md** - Quick reference (start here)
2. **IMPLEMENTATION_MCB_PREVIEW_BUTTON.md** - Complete technical guide
3. **MCB_PREVIEW_BUTTON_GUIDE.md** - Detailed troubleshooting

---

**Deployment Completed By:** AI Assistant  
**Time:** November 10, 2025 10:07 AM  
**Next Step:** Test the new features and verify marketing data capture
