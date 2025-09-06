# EduBot Pro - Installation & Troubleshooting Guide

## Quick Fix for Current Error

The error you were experiencing is due to incorrect file paths and missing error handling. This has been **FIXED** in the latest version.

## ⚠️ Current Status: Runtime Errors Detected

**New Issues Found:** The plugin activated successfully, but there are runtime errors that need fixing:

### 🔧 Issues Being Fixed:
1. **Missing Methods:** Several classes are missing required methods
2. **Path Issues:** Admin view files have incorrect paths (missing slash)
3. **Database Errors:** SQL syntax issues in analytics queries
4. **Cron Events:** Missing callback methods causing WordPress cron failures

### ✅ What's Working:
- ✅ Plugin activation (no more fatal file errors)
- ✅ Admin menu appears
- ✅ Database tables created
- ✅ Core file structure intact

### 🔄 Currently Fixing:
- Adding missing methods to classes
- Fixing admin view file paths
- Correcting database queries
- Implementing proper cron callbacks

### Current Plugin Structure ✅

Your plugin is now correctly organized like this:
```
wp-content/plugins/edubot-pro/
├── edubot-pro.php ✅ (main plugin file with enhanced error handling)
├── includes/ ✅
│   ├── class-edubot-activator.php ✅
│   ├── class-edubot-deactivator.php ✅
│   ├── class-edubot-core.php ✅ (updated with safe file loading)
│   ├── class-edubot-academic-config.php ✅
│   ├── class-edubot-loader.php ✅
│   ├── class-edubot-i18n.php ✅
│   ├── class-api-integrations.php ✅
│   ├── class-branding-manager.php ✅
│   ├── class-chatbot-engine.php ✅
│   ├── class-database-manager.php ✅
│   ├── class-notification-manager.php ✅
│   ├── class-school-config.php ✅
│   └── class-security-manager.php ✅
├── admin/ ✅
│   ├── class-edubot-admin.php ✅
│   ├── css/ ✅
│   ├── js/ ✅
│   └── partials/ ✅
├── public/ ✅
│   ├── class-edubot-public.php ✅
│   ├── css/ ✅
│   └── js/ ✅
└── README.md ✅
```

### What Was Fixed:
1. **File Path Issue:** Changed `EDUBOT_PRO_PLUGIN_DIR` to `EDUBOT_PRO_PLUGIN_PATH` with proper trailing slashes
2. **Error Handling:** Added `file_exists()` checks before all file inclusions
3. **Class Safety:** Added `class_exists()` checks before instantiation
4. **User Feedback:** Clear error messages with file structure guidance

### Step 3: Ready to Activate! 🎉

Your plugin is now ready for activation:

1. **Go to WordPress Admin** → Plugins
2. **Find "EduBot Pro"** in the list
3. **Click "Activate"** 
4. **Check for success indicators:**
   - ✅ No fatal errors during activation
   - ✅ "EduBot Pro" menu appears in WordPress admin
   - ✅ All settings pages load correctly
   - ✅ Database tables are created automatically

If you encounter any issues, the plugin will now show clear error messages with guidance on how to fix them.

## Common Installation Issues

### Issue 1: "Failed opening required" Error
**Cause:** Incorrect file paths or missing files
**Solution:** 
- Ensure all files are in the correct directory structure
- Check file permissions (755 for folders, 644 for files)
- Verify the main plugin file is in the root of the plugin folder

### Issue 2: "Class not found" Error
**Cause:** Missing include files or incorrect class names
**Solution:**
- Check that all class files exist in the `includes/` folder
- Verify class names match file names exactly
- Ensure proper PHP syntax in all files

### Issue 3: Database Table Errors
**Cause:** Plugin activation failed or database permissions
**Solution:**
- Deactivate and reactivate the plugin
- Check WordPress database permissions
- Verify MySQL version compatibility (5.6+)

### Issue 4: Admin Pages Not Loading
**Cause:** Missing admin class or template files
**Solution:**
- Check `admin/` folder contains all necessary files
- Verify admin class is properly included
- Check for PHP syntax errors in admin files

## Pre-Installation Checklist

Before installing EduBot Pro, ensure your environment meets these requirements:

✅ **WordPress Version:** 5.0 or higher  
✅ **PHP Version:** 7.4 or higher  
✅ **MySQL Version:** 5.6 or higher  
✅ **Required PHP Extensions:**
- curl (for API calls)
- openssl (for encryption)
- json (for data handling)
- mbstring (for text processing)

✅ **WordPress Permissions:**
- Ability to create database tables
- File system write permissions
- Ability to schedule cron events

✅ **Server Resources:**
- Memory limit: 128MB minimum (256MB recommended)
- Execution time: 30 seconds minimum
- Storage: 50MB free space

## Installation Steps

### Manual Installation

1. **Download the Plugin**
   - Extract the `edubot-pro.zip` file
   - Verify the folder structure matches the requirements above

2. **Upload via FTP/cPanel**
   ```bash
   # Upload to the correct location
   /wp-content/plugins/edubot-pro/
   ```

3. **Set Permissions**
   ```bash
   # Folders: 755
   find /wp-content/plugins/edubot-pro/ -type d -exec chmod 755 {} \;
   
   # Files: 644
   find /wp-content/plugins/edubot-pro/ -type f -exec chmod 644 {} \;
   ```

4. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "EduBot Pro" and click "Activate"

### WordPress Admin Installation

1. **Upload ZIP File**
   - Go to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the `edubot-pro.zip` file
   - Click "Install Now"

2. **Activate**
   - Click "Activate Plugin" after installation

## Post-Installation Setup

### 1. Initial Configuration
After successful activation:

1. **Go to EduBot Pro Dashboard**
   - Navigate to WordPress Admin → EduBot Pro
   - You should see the main dashboard

2. **Configure API Settings**
   - Go to EduBot Pro → API Integrations
   - Add your OpenAI API key
   - Test the connection

3. **Set Up Your First School**
   - Go to EduBot Pro → School Settings
   - Add your school information
   - Configure branding and colors

4. **Academic Configuration**
   - Go to EduBot Pro → Academic Configuration
   - Set up your grade/class system
   - Configure academic year settings
   - Choose your educational board (if applicable)

### 2. Testing the Installation

Run these tests to ensure everything is working:

**Test 1: Admin Access**
- ✅ Can access EduBot Pro menu in WordPress admin
- ✅ All sub-menu items load without errors
- ✅ Settings pages display correctly

**Test 2: Database Tables**
Check if these tables were created:
- `wp_edubot_school_configs`
- `wp_edubot_applications` 
- `wp_edubot_analytics`
- `wp_edubot_chat_sessions`

**Test 3: API Integration**
- ✅ OpenAI API connection test passes
- ✅ Can save and retrieve settings
- ✅ No PHP errors in error logs

**Test 4: Frontend Display**
- ✅ Chatbot widget appears when shortcode is used
- ✅ CSS and JavaScript files load correctly
- ✅ No console errors in browser

## Troubleshooting Commands

### Check File Permissions
```bash
# Check current permissions
ls -la /wp-content/plugins/edubot-pro/

# Fix permissions if needed
chmod 755 /wp-content/plugins/edubot-pro/
chmod 644 /wp-content/plugins/edubot-pro/edubot-pro.php
```

### Check Database Tables
```sql
-- Run in phpMyAdmin or database client
SHOW TABLES LIKE 'wp_edubot_%';

-- Check specific table structure
DESCRIBE wp_edubot_school_configs;
```

### Enable WordPress Debug Mode
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Error Logs
- **Location:** `/wp-content/debug.log`
- **Look for:** Lines containing "edubot" or "EduBot"

## Advanced Troubleshooting

### Plugin Conflicts
If experiencing issues:

1. **Deactivate Other Plugins**
   - Temporarily deactivate all other plugins
   - Activate EduBot Pro
   - Test functionality
   - Reactivate other plugins one by one

2. **Theme Conflicts**
   - Switch to a default WordPress theme (Twenty Twenty-Three)
   - Test plugin functionality
   - If it works, the issue is theme-related

3. **Server Configuration**
   - Check PHP error logs
   - Verify server meets minimum requirements
   - Test with increased memory limits

### Database Issues

If database tables aren't created:

```php
// Run this code in WordPress admin → Tools → Site Health → Debug Info
global $wpdb;
$activator_path = WP_PLUGIN_DIR . '/edubot-pro/includes/class-edubot-activator.php';
if (file_exists($activator_path)) {
    require_once $activator_path;
    Edubot_Activator::activate();
    echo "Database tables recreated!";
} else {
    echo "Activator file not found at: " . $activator_path;
}
```

## Support Resources

### Getting Help
- **Documentation:** Check README.md files in the plugin folder
- **Error Logs:** Always check WordPress error logs first
- **Debug Mode:** Enable WordPress debug mode for detailed error information

### Common Error Messages and Solutions

**"Failed opening required"**
→ File path issue - check folder structure

**"Class 'Edubot_Core' not found"**
→ Core class file missing - verify includes/ folder

**"Table doesn't exist"**
→ Database activation failed - reactivate plugin

**"Headers already sent"**
→ PHP syntax error or whitespace - check for PHP errors

**"Fatal error: Maximum execution time"**
→ Server timeout - increase execution time or optimize code

## Success Indicators

You'll know the installation was successful when:

✅ **No error messages** during activation  
✅ **Admin menu appears** with "EduBot Pro"  
✅ **All submenu pages load** without errors  
✅ **Database tables exist** (check via phpMyAdmin)  
✅ **Settings can be saved** and retrieved  
✅ **API test connections work**  
✅ **Frontend chatbot displays** when shortcode is used  

Once these criteria are met, your EduBot Pro installation is ready for configuration and use!
