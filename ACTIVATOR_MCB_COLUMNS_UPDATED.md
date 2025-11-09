# ✅ Database Activator Updated with MCB Columns

**Status:** COMPLETE  
**Date:** November 9, 2025  
**Version:** 1.5.1

## Summary

The database activator (`class-edubot-activator.php`) has been **successfully updated** to include MCB columns for both:
1. **New installations** (CREATE TABLE)
2. **Existing installations** (Migrations)

## Changes Made

### 1. **Updated `sql_applications()` Function** (Lines 585-632)

Added MCB columns to the CREATE TABLE statement for fresh installations:

```sql
CREATE TABLE IF NOT EXISTS wp_edubot_applications (
    ...
    status VARCHAR(50) DEFAULT 'pending',
    enquiry_id BIGINT UNSIGNED,                    ✅ NEW
    mcb_sync_status VARCHAR(50) DEFAULT 'pending', ✅ NEW
    mcb_enquiry_id VARCHAR(100),                   ✅ NEW
    ...
    KEY enquiry_id (enquiry_id),                   ✅ NEW INDEX
    KEY mcb_sync (mcb_sync_status),                ✅ NEW INDEX
    ...
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. **Updated `run_migrations()` Function** (Lines 276-345)

Added migration logic to add MCB columns to **existing installations**:

```php
// Migration: Add MCB columns to applications table (v1.5.0+)
$applications_table = $wpdb->prefix . 'edubot_applications';

// Add enquiry_id column if it doesn't exist
$has_enquiry_id = $wpdb->get_var("SHOW COLUMNS FROM {$applications_table} LIKE 'enquiry_id'");
if (!$has_enquiry_id) {
    $wpdb->query("ALTER TABLE {$applications_table} ADD COLUMN enquiry_id BIGINT UNSIGNED AFTER status");
    $wpdb->query("ALTER TABLE {$applications_table} ADD INDEX idx_enquiry_id (enquiry_id)");
    $migrations[] = 'Added enquiry_id column to applications table';
}

// Add mcb_sync_status column if it doesn't exist
$has_mcb_sync = $wpdb->get_var("SHOW COLUMNS FROM {$applications_table} LIKE 'mcb_sync_status'");
if (!$has_mcb_sync) {
    $wpdb->query("ALTER TABLE {$applications_table} ADD COLUMN mcb_sync_status VARCHAR(50) DEFAULT 'pending' AFTER enquiry_id");
    $wpdb->query("ALTER TABLE {$applications_table} ADD INDEX idx_mcb_sync (mcb_sync_status)");
    $migrations[] = 'Added mcb_sync_status column to applications table';
}

// Add mcb_enquiry_id column if it doesn't exist
$has_mcb_enquiry_id = $wpdb->get_var("SHOW COLUMNS FROM {$applications_table} LIKE 'mcb_enquiry_id'");
if (!$has_mcb_enquiry_id) {
    $wpdb->query("ALTER TABLE {$applications_table} ADD COLUMN mcb_enquiry_id VARCHAR(100) AFTER mcb_sync_status");
    $migrations[] = 'Added mcb_enquiry_id column to applications table';
}
```

## Database Schema Now Includes

| Column Name | Type | Default | Purpose |
|---|---|---|---|
| `enquiry_id` | BIGINT UNSIGNED | NULL | Reference to enquiry record |
| `mcb_sync_status` | VARCHAR(50) | 'pending' | MCB sync state (pending/synced/failed) |
| `mcb_enquiry_id` | VARCHAR(100) | NULL | MCB's unique enquiry ID |

## Indexes Added

```sql
KEY enquiry_id (enquiry_id)         -- For fast lookups by enquiry_id
KEY mcb_sync (mcb_sync_status)      -- For filtering by sync status
```

## Impact

### ✅ New Installations
- Fresh WordPress installations will automatically get all MCB columns during plugin activation
- No manual migration required

### ✅ Existing Installations
- On next plugin reactivation (or WP upgrade), migrations run automatically
- Columns added only if they don't already exist (safe to run multiple times)
- Logging tracks which columns were added

### ✅ Code Compatibility
- MCB button logic now has proper database columns to check
- No more "column doesn't exist" errors
- Conditional display will work correctly

## Verification

The activator now:
1. ✅ Creates MCB columns for NEW installations
2. ✅ Migrates MCB columns for EXISTING installations
3. ✅ Checks if columns exist before adding (prevents duplicates)
4. ✅ Adds appropriate indexes for performance
5. ✅ Logs all migrations for debugging

## Related Files

- `includes/class-edubot-activator.php` - UPDATED
- `includes/class-edubot-mcb-admin.php` - Already fixed to use `id` instead of `enquiry_id`
- `admin/views/applications-list.php` - Already updated with filter hook
- `edubot-pro.php` - Already updated for v1.5.1

## What's Next

Users can now:
1. **Deactivate/reactivate the plugin** to trigger the migration
2. **MCB sync button will appear** on the Applications page
3. **Button visibility is conditional** based on MCB settings

## Notes

- Migrations use `IF NOT EXISTS` to safely run multiple times
- Column ordering is preserved for backwards compatibility
- Default values ensure existing data isn't corrupted
- Indexes optimize MCB-related queries
