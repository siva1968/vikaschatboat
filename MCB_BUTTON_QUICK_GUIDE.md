# MCB Sync Button - Quick Reference Guide

## What Changed?

The manual MCB sync button on the Applications page now **only appears when MCB integration is enabled**.

## Button Visibility

### ✅ BUTTON SHOWS
When you:
1. Go to **EduBot Pro > MyClassBoard Settings**
2. Check **"Enable MCB Integration"** ✓
3. Check **"Enable MCB Sync"** ✓
4. Click **"Save Settings"**

Then go to **EduBot Pro > Applications** → You'll see the **"Sync MCB"** button

### ❌ BUTTON HIDDEN
When you:
1. Go to **EduBot Pro > MyClassBoard Settings**
2. Uncheck **"Enable MCB Integration"** ☐
3. Click **"Save Settings"**

Then go to **EduBot Pro > Applications** → The **"Sync MCB"** button is hidden

## Why This Change?

- **Better UX:** Reduces confusion - button only shows when it's actually usable
- **Security:** Prevents accidental syncs if MCB is intentionally disabled
- **Clarity:** Immediately shows MCB status through button visibility

## Code Location

```
File: includes/class-edubot-mcb-admin.php
Function: add_sync_action() [Lines 76-110]
```

## Step-by-Step: Enable MCB Sync Button

1. Open WordPress Admin Dashboard
2. Click: **EduBot Pro** (left menu)
3. Click: **MyClassBoard Settings**
4. ✅ Check: "Enable MCB Integration"
5. ✅ Check: "Enable MCB Sync" 
6. Click: **Save Settings** (blue button at bottom)
7. Click: **EduBot Pro** → **Applications**
8. Look in the **Actions** column → **"Sync MCB"** button is visible!

## Step-by-Step: Disable MCB Sync Button

1. Open WordPress Admin Dashboard
2. Click: **EduBot Pro** (left menu)
3. Click: **MyClassBoard Settings**
4. ☐ Uncheck: "Enable MCB Integration"
5. Click: **Save Settings**
6. Click: **EduBot Pro** → **Applications**
7. Look in the **Actions** column → **"Sync MCB"** button is GONE!

## Button States

When MCB is **ENABLED**, the button shows different states:

| State | Button Text | Color | Meaning |
|-------|-----------|-------|---------|
| Ready | Sync MCB | Blue | Ready to sync |
| Synced | ✓ Synced | Green | Already synced to MCB |
| Failed | Retry MCB | Red | Sync failed, click to retry |
| Syncing | Syncing to MCB... | Blue | Currently syncing |

## No Button Changes Needed

These settings still work as before:
- ✅ Auto sync when new applications created
- ✅ Manual AJAX sync requests  
- ✅ Retry failed syncs
- ✅ All sync status tracking

Only the **visibility** of the manual sync button changed - it now respects the MCB integration enabled setting.

## Questions?

- **Button still showing but I disabled MCB?** → Clear browser cache (Ctrl+F5) and refresh WordPress
- **Button not showing but MCB is enabled?** → Both "Enable MCB Integration" AND "Enable MCB Sync" must be checked
- **Button works but settings are off?** → The AJAX handler also checks - syncs won't actually happen if MCB is disabled

---

**Feature:** Conditional MCB Sync Button Display  
**Status:** ✅ Active  
**Impact:** UI/UX Enhancement - No functional changes
