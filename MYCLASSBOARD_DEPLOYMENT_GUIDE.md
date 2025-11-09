# MyClassBoard Integration - Deployment & Verification Guide

## 🚀 DEPLOYMENT CHECKLIST

### Phase 1: File Deployment

**Files Created:**
```
✅ includes/class-myclassboard-integration.php
   - Core integration class
   - 600+ lines
   - Handles all sync logic

✅ includes/admin/class-mcb-settings-page.php
   - Admin settings interface
   - 450+ lines
   - Provides configuration UI

✅ includes/admin/class-mcb-sync-dashboard.php
   - Real-time monitoring dashboard
   - 350+ lines
   - Provides status monitoring

✅ includes/integrations/class-mcb-integration-setup.php
   - Initialization & setup
   - 400+ lines
   - Handles all bootstrapping
```

**Deployment Path:**
```
Source Workspace:
c:\Users\prasa\source\repos\AI ChatBoat\

Target WordPress:
D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\
OR
D:\xamppdev\htdocs\epistemo\wp-content\plugins\epistemo-edubot\
```

### Phase 2: Plugin Integration

**Add to Main Plugin File (edubot-pro.php or class-edubot.php):**

```php
// Add this to the plugin initialization:

// Load MCB Integration
require_once plugin_dir_path( __FILE__ ) . 'includes/integrations/class-mcb-integration-setup.php';

// Initialize MCB integration when plugin loads
add_action( 'plugins_loaded', 'edubot_mcb_integration_init', 20 );

function edubot_mcb_integration_init() {
    if ( class_exists( 'EduBot_MCB_Integration_Setup' ) ) {
        EduBot_MCB_Integration_Setup::init();
    }
}
```

### Phase 3: Database Setup

**Tables Created Automatically:**

1. `wp_edubot_mcb_sync_log`
   - Created on first admin page load
   - Automatic via `ensure_sync_log_table()`

2. `wp_edubot_mcb_settings`
   - Created on WordPress `wp_loaded` hook
   - Automatic via `create_mcb_settings_table()`

**Verification SQL:**
```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_edubot_mcb_%';

-- Expected output:
-- wp_edubot_mcb_settings
-- wp_edubot_mcb_sync_log
```

### Phase 4: Plugin Activation

**Admin Settings:**
1. Go to WordPress Admin
2. Navigate to: EduBot → MyClassBoard Settings
3. Verify page loads without errors

**Dashboard Widget:**
1. Go to WordPress Dashboard
2. Look for "MyClassBoard Sync Status" widget
3. Should show statistics

---

## ✅ VERIFICATION TESTS

### Test 1: File Existence

```bash
# PowerShell Command
$files = @(
    "D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\includes\class-myclassboard-integration.php",
    "D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\includes\admin\class-mcb-settings-page.php",
    "D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\includes\admin\class-mcb-sync-dashboard.php",
    "D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\includes\integrations\class-mcb-integration-setup.php"
)

foreach ($file in $files) {
    if (Test-Path $file) {
        Write-Host "✅ $file EXISTS"
    } else {
        Write-Host "❌ $file MISSING"
    }
}
```

### Test 2: Database Tables

```sql
-- Run in phpMyAdmin or MySQL CLI

-- Check if tables created
SELECT table_name 
FROM information_schema.tables 
WHERE table_schema = 'demo' 
AND table_name LIKE '%mcb%';

-- Expected result:
-- wp_edubot_mcb_sync_log
-- wp_edubot_mcb_settings
```

### Test 3: Admin Menu

1. Log in to WordPress Admin
2. Look for "EduBot" menu in sidebar
3. Verify "MyClassBoard Settings" submenu exists
4. Click to open settings page
5. Verify page loads without errors

### Test 4: Settings Save

1. Go to MyClassBoard Settings page
2. Enter test values:
   ```
   Organization ID: 21
   Branch ID: 113
   ```
3. Check "Enable MCB Integration"
4. Click "Save Settings"
5. Verify success message appears
6. Refresh page
7. Verify values are saved

### Test 5: Test Enquiry Sync

1. Go to EduBot → Applications
2. Create or select a test enquiry
3. Go to MyClassBoard Settings → Sync Logs
4. Should see "No logs yet" message

**Manual Sync Test:**
1. Go to Applications list
2. Find "Sync to MCB" action (if available)
3. Click to manually sync
4. Wait 5 seconds
5. Go to Sync Logs
6. Should see new log entry

### Test 6: Statistics Display

1. Go to MyClassBoard Settings → Sync Status tab
2. Verify statistics display:
   ```
   Total Syncs: [number]
   Successful: [number]
   Failed: [number]
   Success Rate: [percentage]%
   ```

### Test 7: Dashboard Widget

1. Go to WordPress Admin Dashboard
2. Look for "MyClassBoard Sync Status" widget
3. Verify displays:
   ```
   Integration Status: ✅ Active or ⏸ Inactive
   Successful Syncs: [number]
   Failed Syncs: [number]
   Success Rate: [percentage]%
   View Full Dashboard button
   ```

### Test 8: Lead Source Mapping

1. Go to MyClassBoard Settings → Lead Source Mapping tab
2. Verify all 12 lead sources displayed:
   - Chat Bot → 273
   - Website → 231
   - Facebook → 272
   - Google Search → 269
   - Google Display → 270
   - Instagram → 268
   - LinkedIn → 267
   - WhatsApp → 273
   - Referral → 92
   - Email → 286
   - Walk-In → 250
   - Organic → 280

3. Test updating a value
4. Save and verify persistence

---

## 🐛 DEBUGGING & TROUBLESHOOTING

### Debug: Check PHP Errors

```bash
# Check WordPress error log
D:\xamppdev\htdocs\demo\wp-content\debug.log

# Look for MCB-related errors
# Should contain messages like:
# [04-Jan-2025 14:30:00 UTC] EduBot MCB: ...
```

### Debug: Test Class Loading

```php
// Add to WordPress via admin page source or plugin test file:

<?php
if ( class_exists( 'EduBot_MyClassBoard_Integration' ) ) {
    echo '✅ EduBot_MyClassBoard_Integration class loaded';
} else {
    echo '❌ EduBot_MyClassBoard_Integration class NOT loaded';
}

if ( class_exists( 'EduBot_MCB_Settings_Page' ) ) {
    echo '✅ EduBot_MCB_Settings_Page class loaded';
} else {
    echo '❌ EduBot_MCB_Settings_Page class NOT loaded';
}

if ( class_exists( 'EduBot_MCB_Integration_Setup' ) ) {
    echo '✅ EduBot_MCB_Integration_Setup class loaded';
} else {
    echo '❌ EduBot_MCB_Integration_Setup class NOT loaded';
}
?>
```

### Debug: Test Settings Save

```php
<?php
// Test saving settings
$integration = new EduBot_MyClassBoard_Integration();

$test_settings = array(
    'enabled' => 1,
    'organization_id' => '21',
    'branch_id' => '113',
    'sync_enabled' => 1,
);

$result = $integration->update_settings( $test_settings );

if ( $result ) {
    echo '✅ Settings saved successfully';
    
    // Verify saved settings
    $saved = $integration->get_settings();
    echo '<pre>' . print_r( $saved, true ) . '</pre>';
} else {
    echo '❌ Failed to save settings';
}
?>
```

### Debug: Test Enquiry Sync

```php
<?php
// Test sync with sample data
global $wpdb;

$integration = new EduBot_MyClassBoard_Integration();

$sample_enquiry = array(
    'enquiry_number' => 'TEST20250001',
    'student_name' => 'Test Student',
    'parent_name' => 'Test Parent',
    'email' => 'test@example.com',
    'phone' => '9876543210',
    'grade' => 'Grade 5',
    'board' => 'CBSE',
    'academic_year' => '2026-27',
    'address' => 'Test Address',
    'source' => 'chatbot',
);

$result = $integration->sync_enquiry_to_mcb( 999, $sample_enquiry );

echo '<pre>' . print_r( $result, true ) . '</pre>';

// Check logs
$logs = $integration->get_recent_sync_logs( 5 );
echo '<h3>Recent Logs:</h3>';
echo '<pre>' . print_r( $logs, true ) . '</pre>';
?>
```

---

## 📋 POST-DEPLOYMENT TASKS

### Task 1: Configuration

**Required Settings:**
```
✅ Enable MCB Integration: checked
✅ Organization ID: 21 (or your org ID)
✅ Branch ID: 113 (or your branch ID)
✅ Enable Data Sync: checked
✅ Sync New Enquiries: checked
✅ Auto Sync: checked (if immediate sync desired)
```

### Task 2: Test Production Sync

```
Step 1: Create test enquiry
        → Go to website
        → Fill and submit admission form
        → Note enquiry number

Step 2: Monitor logs
        → Go to EduBot → MyClassBoard Settings
        → Sync Logs tab
        → Wait 5-10 seconds
        → Should see new log entry

Step 3: Verify sync
        → Log entry should show ✅ Synced
        → No error message
        → Timestamp should be recent

Step 4: Test MCB
        → Log in to MyClassBoard
        → Check if enquiry appears
        → Verify all data synced correctly
```

### Task 3: Monitor Success Rate

```
Collect data for first week:
- Total syncs attempted
- Successful syncs
- Failed syncs
- Common errors

Target: 95%+ success rate

If below 95%:
- Review error messages
- Check MCB API status
- Verify organization/branch IDs
- Contact MCB support if needed
```

### Task 4: Team Training

```
Train admin users on:
1. Viewing sync status
2. Checking sync logs
3. Manual sync process
4. Retry process
5. Troubleshooting common issues
6. When to contact support
```

### Task 5: Documentation

```
Create local documentation:
1. Screenshot of settings page
2. Lead source mapping table
3. Organization ID & Branch ID
4. Contact info for MCB support
5. Escalation procedure for failures
```

---

## 🔐 SECURITY CHECKLIST

### Pre-Deployment Security

- [ ] No hardcoded API keys in code
- [ ] API keys stored in WordPress options
- [ ] Database queries use prepared statements
- [ ] All user input sanitized
- [ ] All user actions require nonce verification
- [ ] Admin actions require `manage_options` capability

### Post-Deployment Security

- [ ] API key is set and not empty
- [ ] "Test Mode" is disabled in production
- [ ] WordPress debug mode is off (if production)
- [ ] Sync logs don't contain sensitive data
- [ ] Admin access restricted to trusted users
- [ ] Regular backups of settings

---

## 📊 SUCCESS METRICS

### Target Metrics

| Metric | Target | Acceptable | Critical |
|--------|--------|-----------|----------|
| Sync Success Rate | 100% | 95%+ | <90% |
| Average Sync Time | <5s | <10s | >30s |
| API Response Time | <2s | <3s | >5s |
| Data Accuracy | 100% | 99%+ | <95% |
| Dashboard Availability | 99.9% | 99%+ | <95% |

### Monitoring Dashboard

Check weekly:
```
Monday Morning Report:
- Total syncs this week: ___
- Successful: ___ (___%)
- Failed: ___ (___%)
- Errors: [list top 3]
- Action items: [list any]
```

---

## 🆘 COMMON ISSUES & SOLUTIONS

### Issue: Admin page not loading

**Solution:**
```
1. Check PHP error log
2. Verify file permissions (644 for files)
3. Check class dependencies loaded
4. Verify database connection
5. Clear WordPress cache if using caching plugin
```

### Issue: Settings not saving

**Solution:**
```
1. Verify nonce in form
2. Check user capabilities (must be admin)
3. Verify database write permissions
4. Check if option already exists
5. Try clearing browser cache
```

### Issue: Syncs not appearing in logs

**Solution:**
```
1. Verify "Enable MCB Integration" is checked
2. Verify "Enable Data Sync" is checked
3. Check if auto-sync is enabled
4. Try manual sync
5. Check if enquiry exists in database
6. Check database table permissions
```

### Issue: Dashboard widget not showing

**Solution:**
```
1. Log out and back in
2. Check if "Hide" button pressed
3. Verify user is admin
4. Check WordPress error log
5. Clear WordPress cache
6. Try deactivating/reactivating plugin
```

---

## ✨ VERIFICATION SUMMARY

**When deployment is complete, you should have:**

✅ 4 new PHP classes created  
✅ 2 new database tables created  
✅ MyClassBoard Settings admin page accessible  
✅ Real-time sync dashboard working  
✅ Statistics displaying correctly  
✅ Sync logs showing attempts  
✅ Dashboard widget showing status  
✅ Manual sync functionality working  
✅ Lead source mapping configurable  
✅ Database entries visible in phpMyAdmin  

**When tests pass, integration is READY FOR PRODUCTION**

---

## 📞 NEXT STEPS

1. **Immediate (Today):**
   - [ ] Deploy files to WordPress
   - [ ] Activate plugin
   - [ ] Configure organization/branch IDs
   - [ ] Enable integration

2. **Short-term (This Week):**
   - [ ] Create test enquiries
   - [ ] Verify sync to MCB
   - [ ] Monitor logs
   - [ ] Train admin users

3. **Ongoing:**
   - [ ] Monitor sync success rate weekly
   - [ ] Review error logs
   - [ ] Update lead source mappings as needed
   - [ ] Collect feedback from users

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-01-06  
**Status:** Ready for Deployment
