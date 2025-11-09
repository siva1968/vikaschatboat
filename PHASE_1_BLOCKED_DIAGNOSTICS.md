# Phase 1 Blocked - Missing Dependencies Diagnostic

**Status:** ⚠️ BLOCKED - Plugin Bootstrap Error  
**Date:** November 5, 2025  
**Issue:** Missing required files error on plugin activation

---

## 🔍 DIAGNOSTIC FINDINGS

### Files Status
```
✅ admin/class-edubot-admin.php            3,574 lines    FOUND
✅ public/class-edubot-public.php          852 lines      FOUND
✅ includes/class-edubot-activator.php     1,016 lines    FOUND
✅ includes/class-edubot-deactivator.php   EXISTS         FOUND
✅ edubot-pro.php                          204 lines      FOUND
```

### Root Cause Analysis

**Problem:** WordPress showing "Missing required files" error even though files exist

**Possible Causes:**
1. **Plugin not properly registered** - Main plugin file not loading dependencies correctly
2. **Missing require statements** - edubot-pro.php not including necessary files
3. **PHP version mismatch** - Code using unsupported PHP syntax
4. **Namespace/class conflicts** - Class names conflicting with other plugins
5. **Corrupted plugin cache** - WordPress plugin cache outdated
6. **Undefined constants** - Missing plugin constant definitions

---

## 🔧 IMMEDIATE FIX STEPS (Do These First)

### Step 1: Clear WordPress Plugin Cache
```bash
# Delete plugin cache
rm -r wp-content/plugins/edubot-pro/
# Reinstall from repo
git clone https://github.com/siva1968/edubot-pro.git wp-content/plugins/edubot-pro/
```

### Step 2: Verify Requires in Main Plugin File
Check that `edubot-pro.php` loads all dependencies:

```php
// Should have these requires:
require_once plugin_dir_path(__FILE__) . 'includes/class-edubot-loader.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-edubot-i18n.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-edubot-admin.php';
require_once plugin_dir_path(__FILE__) . 'public/class-edubot-public.php';
```

### Step 3: Check PHP Error Logs
```bash
# View WordPress debug log
tail -f wp-content/debug.log

# Look for:
# - Fatal errors
# - Parse errors
# - Class not found
# - Function already declared
```

### Step 4: Verify WordPress Installation
```bash
# Check WordPress version
wp --version

# Check PHP version
php --version

# Required: PHP 7.4+
```

---

## 📋 DEPENDENCY CHECK

### Required Files to Load in Order

**1. Autoloader** (if exists)
- `includes/class-edubot-autoloader.php`
- Purpose: Auto-load all classes

**2. Constants** 
- `includes/class-edubot-constants.php`
- Purpose: Define plugin constants

**3. Loader Class**
- `includes/class-edubot-loader.php`
- Purpose: Register hooks and filters

**4. Internationalization**
- `includes/class-edubot-i18n.php`
- Purpose: Load translation files

**5. Activator/Deactivator**
- `includes/class-edubot-activator.php`
- `includes/class-edubot-deactivator.php`
- Purpose: Plugin lifecycle hooks

**6. Admin Class**
- `admin/class-edubot-admin.php`
- Purpose: Admin functionality

**7. Public Class**
- `public/class-edubot-public.php`
- Purpose: Public-facing functionality

---

## 🚀 QUICK RECOVERY OPTIONS

### Option A: Deactivate & Reactivate
```bash
wp plugin deactivate edubot-pro
wp plugin activate edubot-pro
```

### Option B: Reset Plugin
```bash
# Back up database
wp db export backup-before-reset.sql

# Deactivate and delete
wp plugin deactivate edubot-pro
rm -r wp-content/plugins/edubot-pro/

# Reinstall fresh
cd wp-content/plugins/
git clone https://github.com/siva1968/edubot-pro.git

# Activate
wp plugin activate edubot-pro
```

### Option C: Manual Fix in wp-config.php
```php
// Add before 'That's all, stop editing!'
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check error logs at:
// wp-content/debug.log
```

---

## 📝 REQUIRED FILE CHECKLIST

Verify each file exists and has proper syntax:

### Main Plugin File
- [ ] `edubot-pro.php` exists
- [ ] Has `@wordpress-plugin` header
- [ ] Version defined correctly
- [ ] All requires present

### Admin Directory
- [ ] `admin/class-edubot-admin.php` exists
- [ ] `admin/class-edubot-admin-secured.php` exists (secured version)
- [ ] `admin/` directory writable

### Public Directory
- [ ] `public/class-edubot-public.php` exists
- [ ] `public/` directory writable

### Includes Directory
- [ ] `includes/class-edubot-activator.php` exists
- [ ] `includes/class-edubot-deactivator.php` exists
- [ ] `includes/class-edubot-loader.php` exists
- [ ] `includes/class-edubot-i18n.php` exists (if needed)
- [ ] `includes/class-edubot-constants.php` exists (if needed)
- [ ] `includes/` directory writable

### Verify No Syntax Errors
```bash
php -l admin/class-edubot-admin.php
php -l public/class-edubot-public.php
php -l includes/class-edubot-activator.php
# All should output: "No syntax errors detected"
```

---

## 🔴 CRITICAL: BEFORE PHASE 1 BEGINS

Complete these steps in order:

### 1. Verify WordPress Installation
```bash
# WordPress up to date?
wp core version

# Required: WP 5.0+
```

### 2. Check PHP Version
```bash
php -v
# Required: PHP 7.4+ (7.4, 8.0, 8.1, 8.2)
```

### 3. Syntax Check All Classes
```bash
# Check each PHP file for syntax errors
php -l edubot-pro.php
php -l admin/class-edubot-admin.php
php -l public/class-edubot-public.php
php -l includes/class-edubot-activator.php
php -l includes/class-edubot-deactivator.php

# Should see: "No syntax errors detected"
```

### 4. Check File Permissions
```bash
# All plugin files readable/executable?
ls -la admin/
ls -la public/
ls -la includes/

# Should have: rw-r--r-- or rwxr-xr-x
```

### 5. Enable Debug Logging
```php
// In wp-config.php, add:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### 6. Check Error Log
```bash
tail -50 wp-content/debug.log
# Look for specific error messages about missing files
```

---

## 📊 STATUS TRACKING

### Current State
```
Plugin Status:     ⚠️ NOT ACTIVATING
Error Message:     "Missing required files"
Files Present:     ✅ YES
Files Readable:    ? UNKNOWN
PHP Version:       ? UNKNOWN
WordPress Version: ? UNKNOWN
Debug Log:         ? UNKNOWN
```

### Post-Recovery State (Target)
```
Plugin Status:     ✅ ACTIVATED
Error Message:     NONE
Files Present:     ✅ YES
Files Readable:    ✅ YES
PHP Version:       ✅ 7.4+
WordPress Version: ✅ 5.0+
Debug Log:         ✅ CLEAN
```

---

## 🎯 NEXT STEPS

### Immediate (Now)
1. [ ] Run diagnostic: `php -l` on all main files
2. [ ] Check `wp-content/debug.log` for actual error
3. [ ] Verify file permissions are correct
4. [ ] Verify PHP/WordPress versions meet requirements

### Short Term (1-2 hours)
5. [ ] Fix any syntax errors found
6. [ ] Fix any permission issues
7. [ ] Attempt plugin deactivate/reactivate
8. [ ] Verify plugin now loads without error

### Before Phase 1
9. [ ] Plugin successfully activating without errors
10. [ ] Admin pages accessible
11. [ ] No errors in debug log
12. [ ] Ready to begin Phase 1 Security tasks

---

## 📞 TROUBLESHOOTING REFERENCE

### If you see "Class not found: EduBot_Admin"
```
Solution: 
- Check admin/class-edubot-admin.php exists
- Check requires statement in edubot-pro.php
- Check file is readable: ls -la admin/class-edubot-admin.php
```

### If you see "Parse error in class-edubot-admin.php"
```
Solution:
- Run: php -l admin/class-edubot-admin.php
- Fix line number shown in error
- Check for unmatched braces, quotes, semicolons
```

### If you see "Cannot declare class, already declared"
```
Solution:
- Check for duplicate includes in edubot-pro.php
- Search: grep "class-edubot-admin.php" edubot-pro.php
- Should appear exactly once
```

### If plugin still doesn't activate
```
Final Solution:
1. Backup database: wp db export backup.sql
2. Backup plugin: cp -r edubot-pro/ edubot-pro-backup/
3. Delete plugin: rm -r edubot-pro/
4. Fresh install: git clone https://github.com/siva1968/edubot-pro.git
5. Activate: wp plugin activate edubot-pro
6. Check logs: tail -f wp-content/debug.log
```

---

## 💾 RECOVERY COMMAND SCRIPT

Save as `recover-plugin.sh`:

```bash
#!/bin/bash

echo "🔧 Recovering EduBot Pro Plugin..."

# 1. Enable debug
echo "define('WP_DEBUG', true);" >> wp-config.php
echo "define('WP_DEBUG_LOG', true);" >> wp-config.php
echo "define('WP_DEBUG_DISPLAY', false);" >> wp-config.php

# 2. Check syntax
echo "✓ Checking PHP syntax..."
php -l edubot-pro.php
php -l admin/class-edubot-admin.php
php -l public/class-edubot-public.php

# 3. Check permissions
echo "✓ Setting permissions..."
chmod 755 admin/ public/ includes/
chmod 644 admin/*.php public/*.php includes/*.php

# 4. Deactivate
wp plugin deactivate edubot-pro

# 5. Reactivate
wp plugin activate edubot-pro

# 6. Check log
echo "✓ Recent errors:"
tail -20 wp-content/debug.log

echo "✅ Recovery complete!"
```

**Run with:**
```bash
chmod +x recover-plugin.sh
./recover-plugin.sh
```

---

**Status:** ⏸️ PHASE 1 BLOCKED  
**Action Required:** Complete diagnostic steps above  
**Next Review:** After plugin successfully activates

