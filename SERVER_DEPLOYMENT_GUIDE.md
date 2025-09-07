# EduBot Pro - Server Deployment & Fix Guide

## Issue Resolution Summary

The system status page error was caused by missing class existence checks. This has been fixed and the following tools are now available to resolve database column issues.

## 🚀 Quick Fix Steps (Choose One Method)

### Method 1: Database Diagnostic Script (Recommended)
1. **Access the diagnostic script directly:**
   ```
   https://stage.epistemo.in/wp-content/plugins/AI ChatBoat/fix_database.php
   ```
   
2. **This script will automatically:**
   - Check database connection
   - Verify table structure
   - Add missing columns (gclid, fbclid, click_id_data)
   - Create required indexes
   - Update database version to 1.3.1

### Method 2: WordPress Admin Interface
1. **Go to:** `https://stage.epistemo.in/wp-admin/admin.php?page=edubot-system-status`
2. **Look for "Migration Required" section**
3. **Click "Run Database Migration" button**

### Method 3: Manual Database Update (If needed)
If the automated methods don't work, run these SQL commands in your database:

```sql
ALTER TABLE wp_edubot_enquiries ADD COLUMN gclid varchar(255) NULL;
ALTER TABLE wp_edubot_enquiries ADD COLUMN fbclid varchar(255) NULL;
ALTER TABLE wp_edubot_enquiries ADD COLUMN click_id_data longtext NULL;

CREATE INDEX idx_gclid ON wp_edubot_enquiries (gclid);
CREATE INDEX idx_fbclid ON wp_edubot_enquiries (fbclid);
```

## ✅ Expected Results After Fix

### 1. System Status Page
- No more "Class EduBot_Health_Check not found" error
- Shows database column status
- Displays migration button if columns missing

### 2. Database Structure  
- **gclid column**: Ready for Google Ads click tracking
- **fbclid column**: Ready for Facebook click tracking  
- **click_id_data column**: JSON storage for other platforms
- All columns properly indexed for performance

### 3. Admin Interface
- View Details modal will show "Campaign Click IDs" section
- Click IDs from Google/Facebook campaigns will display
- Professional styling with orange accent color

## 📊 Testing Click ID Tracking

After database fix, test with these sample URLs:

**Google Ads Campaign:**
```
https://stage.epistemo.in/your-page/?utm_source=google&utm_medium=cpc&gclid=Cj0KCQjwtest
```

**Facebook Campaign:**
```
https://stage.epistemo.in/your-page/?utm_source=facebook&utm_medium=social&fbclid=IwAR0test123
```

**Multi-Platform:**
```
https://stage.epistemo.in/your-page/?gclid=google123&fbclid=facebook456&ttclid=tiktok789
```

## 🔍 Verification Steps

1. **Check System Status:**
   - Visit: `https://stage.epistemo.in/wp-admin/admin.php?page=edubot-system-status`
   - Verify all columns show "EXISTS"

2. **Test Enquiry Submission:**
   - Submit test enquiry with click ID in URL
   - Check admin interface for click ID display

3. **Database Verification:**
   - Use diagnostic script to confirm all columns exist
   - Check database version is 1.3.1

## 📞 Support Information

If issues persist:
1. Check WordPress error logs
2. Use the diagnostic script for detailed analysis
3. Verify file permissions on plugin directory
4. Ensure WordPress database user has ALTER privileges

## 🎯 What's Fixed

- ✅ System status page fatal error resolved
- ✅ Class existence checks added throughout
- ✅ Database column verification enhanced
- ✅ Migration tools provided
- ✅ Click ID tracking ready for deployment
- ✅ Professional admin interface styling
- ✅ Multi-platform campaign support

The plugin is now ready for production use with comprehensive campaign attribution tracking!
