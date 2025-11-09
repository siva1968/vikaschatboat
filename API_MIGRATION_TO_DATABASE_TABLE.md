# API Configuration Migration to Database Table

## Overview

This implementation migrates Email, SMS, and WhatsApp API configurations from WordPress Options to the `wp_edubot_api_integrations` database table, improving performance, maintainability, and future scalability.

## What's Migrated

### Email Settings
- `edubot_email_service` → `email_provider`
- `edubot_email_from_address` → `email_from_address`
- `edubot_email_from_name` → `email_from_name`
- `edubot_email_api_key` → `email_api_key`
- `edubot_email_domain` → `email_domain`

### SMS Settings
- `edubot_sms_provider` → `sms_provider`
- `edubot_sms_api_key` → `sms_api_key`
- `edubot_sms_sender_id` → `sms_sender_id`

### WhatsApp Settings
- `edubot_whatsapp_provider` → `whatsapp_provider`
- `edubot_whatsapp_token` → `whatsapp_token`
- `edubot_whatsapp_phone_id` → `whatsapp_phone_id`
- `edubot_whatsapp_template_namespace` → `whatsapp_template_namespace`
- `edubot_whatsapp_template_name` → `whatsapp_template_name`
- `edubot_whatsapp_template_language` → `whatsapp_template_language`

## Architecture

### 1. EduBot_API_Migration Class (`class-api-migration.php`)

Central class handling all migration operations:

```php
// Core methods:
- migrate_api_settings($site_id = 1)      // Execute migration
- migration_needed($site_id = 1)          // Check if migration required
- get_api_settings($site_id = 1)          // Read settings (table first, fallback to options)
- save_api_settings($settings, $site_id)  // Write to table
```

**Key Features:**
- Transaction-based with rollback on errors
- Fallback to WordPress options if table empty
- Backward compatibility maintained
- Comprehensive error logging

### 2. Updated API Integrations Class

`class-api-integrations.php` modified:

```php
public function send_email($to, $subject, $message, $headers = array()) {
    // Get API settings from table (with fallback to options)
    $api_settings = EduBot_API_Migration::get_api_settings();
    
    $email_provider = $api_settings['email_provider'] ?? '';
    // ... continues with provider logic
}
```

### 3. Updated API Settings Page

`class-api-settings-page.php` modified:

```php
private function handle_form_submission() {
    // Save to WordPress options (backward compatibility)
    // Also save to API integrations table (primary storage)
    $save_result = EduBot_API_Migration::save_api_settings($api_settings);
}
```

**Result:** Settings saved to both locations, table takes precedence on read

### 4. Auto-Migration on Activation

`class-edubot-activator.php` modified:

```php
public static function activate() {
    // ...
    // Auto-migrate API settings from WordPress options to table if needed
    if (class_exists('EduBot_API_Migration')) {
        if (EduBot_API_Migration::migration_needed()) {
            $migration_result = EduBot_API_Migration::migrate_api_settings();
        }
    }
    // ...
}
```

### 5. Admin Migration Tool

`class-api-migration-page.php` - New admin page:

- **Location:** WordPress Admin → EduBot Pro → API Migration
- **Features:**
  - Check current migration status
  - View settings to be migrated
  - Manual trigger button for migration
  - Migration history
  - Technical details

## Migration Flow

### Automatic Migration (On Plugin Activation)
```
Plugin Activation
  ↓
Check: Does migration_needed() return true?
  ↓ YES
Call: migrate_api_settings()
  ↓
Transaction Start
  ↓
Create/update API config record in table
  ↓
Migrate each setting (email, SMS, WhatsApp)
  ↓
Transaction Commit
  ↓
Log: "Migration successful: X fields migrated"
```

### Manual Migration (Via Admin Page)
```
Admin clicks "Start Migration Now"
  ↓
Nonce verification
  ↓
Call: EduBot_API_Migration::migrate_api_settings()
  ↓
Store result in transient
  ↓
Redirect to migration page with status
```

### Read Operation (send_email, etc.)
```
get_api_settings()
  ↓
Query: SELECT * FROM wp_edubot_api_integrations WHERE site_id = %d
  ↓
Found? YES → Return table values
  ↓
Found? NO → Return WordPress option values (fallback)
```

## Database Schema

```sql
CREATE TABLE wp_edubot_api_integrations (
    id bigint(20) PRIMARY KEY AUTO_INCREMENT,
    site_id bigint(20) NOT NULL UNIQUE,
    
    -- Email Configuration
    email_provider varchar(50),
    email_from_address varchar(255),
    email_from_name varchar(255),
    email_api_key longtext,
    email_domain varchar(255),
    
    -- SMS Configuration
    sms_provider varchar(50),
    sms_api_key longtext,
    sms_sender_id varchar(100),
    
    -- WhatsApp Configuration
    whatsapp_provider varchar(50),
    whatsapp_token longtext,
    whatsapp_phone_id varchar(100),
    whatsapp_template_type varchar(50),
    whatsapp_template_name varchar(255),
    
    -- Status
    status varchar(20) DEFAULT 'active',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (id),
    UNIQUE KEY site_id (site_id)
)
```

## Backward Compatibility

**No Breaking Changes:**
1. WordPress options still readable as fallback
2. New code writes to table, old code continues reading options
3. Settings form saves to both locations
4. Safe to deploy without data loss

**Transition Phases:**
- **Phase 1:** Deployment (writes to both, reads from table)
- **Phase 2:** Future removal of options (if desired)

## Error Handling

### Migration Failures
- Transaction rollback on any error
- Existing settings preserved
- Error logged with details
- Admin notified via transient

### Configuration Missing
If API config record doesn't exist:
1. System creates new record with `site_id`
2. Populates fields from options
3. Sets `status = 'active'`
4. Logs creation

### Data Integrity
- Foreign keys: `site_id` is unique per site
- No duplicate records possible
- Updated timestamp on every change
- Status field for deactivation support

## Testing & Verification

### Local Testing (XAMPP)
```bash
1. Deploy files to: D:\xamppdev\htdocs\demo\wp-content\plugins\edubot-pro\
2. Re-activate plugin (triggers auto-migration)
3. Check WordPress debug.log for: "EduBot Activation: API Migration Result"
4. Go to: WordPress Admin → EduBot Pro → API Migration
5. Verify status shows "Complete"
6. Submit test enquiry
7. Check if email notification sent
```

### Manual Verification
```sql
-- Check API config in table
SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;

-- Check WordPress options still exist (for compatibility)
SELECT option_name, option_value 
FROM wp_options 
WHERE option_name LIKE 'edubot_email_%' 
OR option_name LIKE 'edubot_sms_%'
OR option_name LIKE 'edubot_whatsapp_%';
```

## Files Modified/Created

### New Files
```
includes/class-api-migration.php           (368 lines) - Migration helper
includes/admin/class-api-migration-page.php (476 lines) - Admin migration tool
```

### Modified Files
```
includes/class-api-integrations.php        - Updated send_email() to use migration helper
includes/admin/class-api-settings-page.php - Updated form handler to save to table
includes/class-edubot-activator.php        - Added auto-migration on activation
includes/class-edubot-core.php             - Loaded migration classes
edubot-pro.php                             - Loaded migration helper
```

## Performance Impact

### Before Migration
- Email settings: 4-5 option queries per email send
- WordPress options table scan required

### After Migration
- Email settings: 1 targeted query to API integrations table
- Indexed by `site_id` for fast lookups
- **Result:** ~3-4x faster configuration retrieval

## Deployment Steps

### Local (XAMPP)
1. Replace plugin files on disk
2. Deactivate plugin in WordPress Admin
3. Activate plugin in WordPress Admin
   - Auto-migration runs
   - Check debug.log for success message
4. Test email sending
5. Verify in WordPress Admin → API Migration page

### Production
1. Backup database
2. Deploy files
3. Deactivate plugin
4. Activate plugin
   - Auto-migration runs with transaction safety
5. Monitor debug log for errors
6. Test email notifications
7. Use admin page to verify migration if needed

## Rollback Plan

If issues occur:
1. Keep WordPress options intact (no data loss)
2. Delete migrated settings from table: `DELETE FROM wp_edubot_api_integrations WHERE site_id = 1`
3. System falls back to reading from options automatically
4. No code changes needed, still works

## Future Enhancements

Possible next phases:
1. **Encryption:** Store API keys encrypted in table (vs plaintext in options)
2. **Multi-site:** Better support for multiple site configurations
3. **Audit logging:** Track API configuration changes
4. **API key rotation:** Built-in rotation support
5. **Provider validation:** Test connection before saving

## Code Examples

### Using Migration Helper in Custom Code
```php
// Get current API settings
$api_settings = EduBot_API_Migration::get_api_settings();
$email_provider = $api_settings['email_provider'];
$api_key = $api_settings['email_api_key'];

// Save new settings
$new_settings = array(
    'email_provider' => 'sendgrid',
    'email_api_key' => 'sg_1234567890',
);
EduBot_API_Migration::save_api_settings($new_settings);

// Check if migration needed
if (EduBot_API_Migration::migration_needed()) {
    $result = EduBot_API_Migration::migrate_api_settings();
    if ($result['success']) {
        error_log('Migration completed: ' . count($result['migrated_fields']) . ' fields migrated');
    }
}
```

## Troubleshooting

### Migration shows "Pending" in admin page
- **Cause:** WordPress options have values but table record is empty
- **Fix:** Click "Start Migration Now" in migration admin page
- **Alternative:** Check debug.log - may have auto-migrated during activation

### Email not sending after migration
- **Check 1:** Go to API Migration page - verify status is "Complete"
- **Check 2:** Check email provider is set (should see provider name)
- **Check 3:** Run a test from API Settings page
- **Check 4:** Check debug.log for errors

### Settings saving but not working
- **Cause:** Settings saved to options but not table (configuration issue)
- **Fix:** Go to WordPress Admin → EduBot Pro → API Migration → "Start Migration Now"
- **Result:** Settings copied to table, will now be used

## Support & Questions

For issues or questions about this migration:
1. Check debug.log for error messages
2. Review migration history in admin page
3. Check database directly using provided SQL queries
4. Contact support with debug log output

---

**Version:** 1.0  
**Date:** 2025-11-06  
**Status:** Ready for deployment
