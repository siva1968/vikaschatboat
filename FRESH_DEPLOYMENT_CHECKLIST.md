# EduBot Pro Analytics - Fresh Deployment Checklist

**Status:** ✅ Ready for Fresh Installation  
**Date:** November 4, 2025  
**Version:** 1.4.1 + Database Fix  
**Commit:** e2ae2ee

## Pre-Deployment Checklist

### ✅ Environment Verification

- [ ] **WordPress Version:** 6.4+ installed
- [ ] **PHP Version:** 7.4+ (run `php -v`)
- [ ] **MySQL Version:** 5.7+ (check from phpMyAdmin)
- [ ] **InnoDB Storage Engine:** Enabled in MySQL
- [ ] **Database Permissions:** User has CREATE/ALTER/DROP/INDEX rights

### ✅ WordPress Configuration

- [ ] **wp-config.php Updated:**
  - [ ] `WP_DEBUG` = true
  - [ ] `WP_DEBUG_LOG` = true (only once!)
  - [ ] `WP_DEBUG_DISPLAY` = true
  - [ ] `SAVEQUERIES` = true
  - [ ] No duplicate constant definitions

- [ ] **Database Details Correct:**
  - [ ] DB_NAME = 'demo'
  - [ ] DB_USER = 'prasadmasina'
  - [ ] DB_PASSWORD = 'Sita@1968@manu'
  - [ ] DB_HOST = 'localhost'

### ✅ Plugin Code

- [ ] **Latest Code:** Pull from git master (commit e2ae2ee or later)
- [ ] **File Integrity:** Check all files exist:
  - [ ] edubot-pro.php (main file)
  - [ ] includes/ (14 core classes)
  - [ ] admin/ (admin interface)
  - [ ] assets/ (CSS/JS)
  - [ ] languages/ (i18n files)

## Deployment Steps

### Step 1: Backup Database

```sql
-- Create backup
BACKUP DATABASE demo TO DISK = 'D:\backup\demo_pre_activation.bak';

-- Or export from phpMyAdmin:
-- 1. Select database 'demo'
-- 2. Export → SQL format → Download
```

**Time:** ~1-2 minutes  
**Importance:** CRITICAL

### Step 2: Clear Old Plugin

```powershell
# Stop WordPress services first
# Then delete old plugin folder:

Remove-Item "D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro" -Recurse -Force
```

**Time:** ~30 seconds  
**Verification:** Folder should be deleted

### Step 3: Deploy Updated Plugin

```powershell
$sourceDir = "c:\Users\prasa\source\repos\AI ChatBoat"
$targetDir = "D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro"

# Create target directory
New-Item -ItemType Directory -Path $targetDir -Force

# Copy all files
Copy-Item -Path "$sourceDir\*" -Destination "$targetDir" -Recurse -Force -Exclude ".git"

# Verify
Get-ChildItem $targetDir -Depth 1
```

**Time:** ~1-2 minutes  
**Verification:** All files copied successfully

### Step 4: Activate Plugin

**In WordPress Admin:**

1. Navigate to: `http://localhost/demo/wp-admin`
2. Go to: **Plugins → Installed Plugins**
3. Find: **EduBot Pro - Analytics Platform**
4. Click: **Activate**

**Expected:** 
- Plugin activates successfully
- No error messages
- New admin menu appears

**Time:** ~5 seconds  
**Verification:** Check step 5

### Step 5: Verify Installation

**Check Debug Log:**
```powershell
Get-Content "D:\xamppdev\htdocs\demo\wp-content\debug.log" -Tail 20

# Should show:
# ✓ EduBot Pro activated successfully. Version: 1.4.1
# ✓ Tables initialized: enquiries, attribution_sessions, ...
```

**Check Database Tables:**
```sql
-- Connect to MySQL
mysql -u prasadmasina -p -h localhost demo

-- Run query:
SHOW TABLES LIKE 'wp_edubot%';

-- Should see all 8 tables:
-- wp_edubot_enquiries
-- wp_edubot_attribution_sessions
-- wp_edubot_attribution_touchpoints
-- wp_edubot_attribution_journeys
-- wp_edubot_conversions
-- wp_edubot_api_logs
-- wp_edubot_report_schedules
-- wp_edubot_logs
```

**Check WordPress Admin:**
- [ ] "EduBot Analytics" menu appears in left sidebar
- [ ] No admin notices or errors
- [ ] Dashboard widgets load without errors

**Time:** ~1 minute  
**Verification:** All 8 tables exist, no errors

## Post-Activation Configuration

### Step 6: Configure API Credentials

1. Go to: **EduBot Analytics → API Settings**
2. Configure each platform:
   - Facebook Conversion API
   - Google Ads API
   - TikTok Conversions API
   - LinkedIn Conversion API

**Time:** ~5-10 minutes  
**Verification:** Green checkmarks on API status

### Step 7: Set Up Email Reports

1. Go to: **EduBot Analytics → Reports**
2. Add a test report:
   - Name: "Test Daily Report"
   - Type: Daily
   - Recipient: Your email
   - Frequency: Daily at 9:00 AM

**Time:** ~2-3 minutes  
**Verification:** Report appears in list

### Step 8: Configure Attribution Model

1. Go to: **EduBot Analytics → Settings**
2. Select attribution model:
   - First-Touch
   - Last-Touch (default)
   - Linear
   - Time-Decay
   - U-Shaped

**Time:** ~1 minute  
**Verification:** Selection saved

## Testing

### Functionality Tests

- [ ] **Dashboard Loads:** No JavaScript errors
- [ ] **Data Display:** Charts render properly
- [ ] **Export Function:** CSV export works
- [ ] **Report Generation:** Email received
- [ ] **API Integration:** Status shows "Connected"

### Database Tests

```sql
-- Test inserts work
INSERT INTO wp_edubot_enquiries 
  (enquiry_number, student_name, email, status) 
VALUES 
  ('TEST-001', 'Test Student', 'test@example.com', 'pending');

-- Verify foreign key works
INSERT INTO wp_edubot_attribution_sessions 
  (enquiry_id, user_session_key, attribution_model) 
VALUES 
  (1, 'sess-001', 'last-click');

-- Should succeed without errors
```

**Time:** ~2-3 minutes  
**Verification:** Inserts succeed without errors

### Performance Tests

```sql
-- Check query performance
SHOW INDEX FROM wp_edubot_enquiries;
SHOW INDEX FROM wp_edubot_attribution_sessions;

-- All key columns should have indexes
```

**Time:** ~1 minute  
**Verification:** All indexes present

## Troubleshooting Reference

### Error: "Can't create table (errno: 150)"
**Status:** ✅ FIXED in v1.4.1
**Solution:** Updated activator ensures tables created in proper order

### Error: "Duplicate constant WP_DEBUG_LOG"
**Status:** ✅ FIXED in v1.4.1
**Solution:** Removed duplicate definition from wp-config.php

### Tables Not Appearing
1. Check debug.log for error messages
2. Verify MySQL has InnoDB: `SHOW ENGINES`
3. Check user permissions: `SHOW GRANTS FOR 'prasadmasina'@'localhost'`
4. Manually run activation: Deactivate → Activate plugin

### Header Information Errors
1. Disable `WP_DEBUG_DISPLAY`: Set to `false`
2. Check no PHP errors before `<?php` tag
3. Remove any BOM from PHP files

## Sign-Off Checklist

**Ready for Production?**

- [ ] All 8 database tables created
- [ ] No errors in debug log
- [ ] Admin menu appears
- [ ] API settings page loads
- [ ] Dashboard displays data
- [ ] Email reports configured
- [ ] All tests pass

**Approval:** ___________  
**Date:** ___________

## Files Included in Deployment

```
edubot-pro/
├── edubot-pro.php                 (Main plugin file)
├── uninstall.php                  (Cleanup on uninstall)
├── admin/
│   ├── class-edubot-admin.php
│   ├── class-dashboard-widget.php
│   ├── class-api-settings-page.php
│   ├── css/
│   ├── js/
│   ├── partials/
│   └── views/
├── includes/
│   ├── class-edubot-activator.php (✅ FIXED)
│   ├── class-attribution-tracker.php
│   ├── class-attribution-models.php
│   ├── class-conversion-api-manager.php
│   ├── class-performance-reports.php
│   ├── class-cron-scheduler.php
│   ├── class-edubot-logger.php
│   ├── database/
│   │   ├── class-db-schema.php (NEW)
│   │   └── migration-001-create-attribution-tables.php
│   └── ... (12 more core classes)
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── languages/
│   └── ... (translation files)
└── ... (documentation files)
```

## Support & Documentation

- **API Reference:** API_REFERENCE.md
- **Configuration:** CONFIGURATION_GUIDE.md
- **Troubleshooting:** TROUBLESHOOTING_GUIDE.md
- **Deployment:** DEPLOYMENT_GUIDE.md
- **Database Fix Details:** DATABASE_FIX_PERMANENT.md

## Success Criteria

Installation is successful when:

1. ✅ Plugin activates without errors
2. ✅ All 8 database tables created
3. ✅ No "errno: 150" foreign key errors
4. ✅ No "duplicate constant" warnings
5. ✅ Admin menu appears
6. ✅ Dashboard loads and displays data
7. ✅ API settings page accessible
8. ✅ Reports can be created
9. ✅ Debug log shows successful activation
10. ✅ No warnings or errors in admin

---

**Status:** ✅ READY FOR DEPLOYMENT  
**Version:** 1.4.1 with Database Fix  
**Commit:** e2ae2ee  
**Last Updated:** November 4, 2025
