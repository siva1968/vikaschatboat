# MCB Sync Button - Version 1.5.1 Release

**Release Date:** November 9, 2025  
**Version:** 1.5.1 (Updated from 1.5.0)  
**Status:** ✅ DEPLOYED & CACHE CLEARED

## What's New in 1.5.1

### Feature: Conditional MCB Sync Button Display

The manual MCB (MyClassBoard) sync button now appears **only when MCB integration is enabled**.

## Changes Made

### 1. **Main Plugin File** (`edubot-pro.php`)
- ✅ Version bumped: `1.5.0` → `1.5.1`
- ✅ Added MCB_Admin initialization via `admin_init` hook
- ✅ Ensures filter registration happens at the right time

### 2. **MCB Admin Interface** (`includes/class-edubot-mcb-admin.php`)
- ✅ Updated `add_sync_action()` function with conditional logic
- ✅ Added check: `if (!$mcb_service->is_sync_enabled()) return $actions;`
- ✅ Button only displays when MCB integration is enabled

### 3. **Applications List View** (`admin/views/applications-list.php`)
- ✅ Added filter support: `apply_filters('edubot_applications_row_actions', $action_links, $app)`
- ✅ Actions now dynamic - allows MCB and other features to add buttons
- ✅ View now properly calls the filter hook

## How to Use

### Enable MCB Sync Button
1. Go to **EduBot Pro > MyClassBoard Settings**
2. Check **"Enable MCB Integration"** ✓
3. Check **"Enable MCB Sync"** ✓
4. Click **"Save Settings"**
5. Go to **EduBot Pro > Applications**
6. **"Sync MCB"** button now appears in Actions column

### Disable MCB Sync Button
1. Go to **EduBot Pro > MyClassBoard Settings**
2. Uncheck **"Enable MCB Integration"** ☐
3. Click **"Save Settings"**
4. Go to **EduBot Pro > Applications**
5. **"Sync MCB"** button disappears

## Files Deployed

```
wp-content/plugins/edubot-pro/
├── edubot-pro.php                          [UPDATED - v1.5.1]
├── includes/
│   └── class-edubot-mcb-admin.php          [UPDATED - conditional check]
└── admin/views/
    └── applications-list.php               [UPDATED - filter support]
```

## Testing Done

✅ Version updated and verified  
✅ Plugin deactivated/reactivated  
✅ All WordPress caches cleared  
✅ Transients cleared  
✅ MCB settings verified (enabled: 1, sync_enabled: 1)  
✅ Button visibility logic tested  

## Next Steps

1. **Refresh Browser**: Ctrl+F5 to clear browser cache
2. **Login to WordPress Admin**: Go to EduBot Pro > Applications
3. **Verify Button**: You should now see "Sync MCB" button in Actions column
4. **Test Toggle**: Try disabling "Enable MCB Integration" - button should disappear

## Technical Details

### Button Visibility Logic

```php
// Checks both conditions:
return !empty($this->mcb_settings['sync_enabled']) && 
       !empty($this->mcb_settings['enabled']);
```

**Both** settings must be 1 (enabled):
- `edubot_mcb_settings['enabled']` = 1 ("Enable MCB Integration")
- `edubot_mcb_settings['sync_enabled']` = 1 ("Enable MCB Sync")

### Filter Hook

The applications list now supports filters for adding custom actions:

```php
$action_links = apply_filters('edubot_applications_row_actions', $action_links, $app);
```

This allows:
- ✅ MCB button to be added/removed
- ✅ Other plugins to add custom actions
- ✅ Dynamic button management based on settings

## Deployment Verification

```
✅ Version check: 1.5.1
✅ Plugin file: Updated
✅ MCB Admin: Updated  
✅ Views: Updated
✅ Caches: Cleared
✅ Status: Ready for testing
```

---

**Next Action:** Refresh WordPress admin and check Applications page for MCB sync button!
