# 🔧 EduBot Database Setup - Missing Tables Fix

**Issue:** ❌ Error: Table 'demo.wp_edubot_enquiries' doesn't exist  
**Cause:** Plugin activator not creating database tables on activation  
**Solution:** Manual table creation script

---

## ✅ What's Been Done

1. ✅ Identified 15 missing EduBot tables
2. ✅ Created comprehensive SQL setup script (`create_edubot_tables.sql`)
3. ✅ Created PHP setup script (`setup-edubot-tables.php`)
4. ✅ Copied script to WordPress installation

**Script Location:** `D:\xamppdev\htdocs\demo\setup-edubot-tables.php`

---

## 🚀 How to Execute

### Option 1: Via Browser (Recommended - Easiest)

1. Open browser and navigate to:
   ```
   http://localhost/demo/setup-edubot-tables.php
   ```

2. You'll see the setup page showing:
   - ✓ Tables being created
   - ✓ Status for each table
   - ✓ Final summary

3. Should show: **"All tables created successfully!"**

4. **After successful setup:**
   - Delete the script from the server
   - Close the browser tab
   - Test the chatbot

---

### Option 2: Via Command Line (Alternative)

```powershell
# Navigate to WordPress directory
cd D:\xamppdev\htdocs\demo

# Create PHP command to run setup
php -r "include 'setup-edubot-tables.php';"
```

---

## 📋 Tables Being Created

| # | Table Name | Purpose |
|---|-----------|---------|
| 1 | `wp_edubot_enquiries` | 🔴 **CORE** - Enquiry data (THIS WAS MISSING!) |
| 2 | `wp_edubot_visitors` | Visitor tracking |
| 3 | `wp_edubot_attribution_journeys` | Customer journey tracking |
| 4 | `wp_edubot_attribution_sessions` | Chat sessions |
| 5 | `wp_edubot_attribution_touchpoints` | Individual interactions |
| 6 | `wp_edubot_applications` | School applications |
| 7 | `wp_edubot_conversions` | Conversion tracking |
| 8 | `wp_edubot_api_integrations` | API credentials |
| 9 | `wp_edubot_api_logs` | API call logs |
| 10 | `wp_edubot_mcb_settings` | MyClassBoard configuration |
| 11 | `wp_edubot_mcb_sync_log` | MCB sync logs |
| 12 | `wp_edubot_school_configs` | School settings |
| 13 | `wp_edubot_visitor_analytics` | Analytics data |
| 14 | `wp_edubot_logs` | Application logs |
| 15 | `wp_edubot_report_schedules` | Report scheduling |

---

## ✅ Expected Output After Running Script

```
EduBot Database Setup

Creating all required EduBot tables...
____________________________________________________

✓ enquiries - Created successfully
✓ visitors - Created successfully
✓ attribution_journeys - Created successfully
✓ attribution_sessions - Created successfully
✓ attribution_touchpoints - Created successfully
✓ applications - Created successfully
✓ conversions - Created successfully
✓ api_integrations - Created successfully
✓ api_logs - Created successfully
✓ mcb_settings - Created successfully
✓ mcb_sync_log - Created successfully
✓ school_configs - Created successfully
✓ visitor_analytics - Created successfully
✓ logs - Created successfully
✓ report_schedules - Created successfully

____________________________________________________

Summary
Tables Created: 15
Tables Skipped: 0
✓ All tables created successfully!

The database is now ready for EduBot Pro.

Next Steps:
1. Delete this script from the server
2. Test enquiry submission on the chatbot
3. Verify data is being saved to the database
```

---

## 🧪 Verify Success

After running the setup script, verify tables exist:

### Via phpMyAdmin:
1. Go to `http://localhost/phpmyadmin`
2. Login to `demo` database
3. Look for `wp_edubot_*` tables
4. Should see all 15 tables listed

### Via WordPress CLI:
```powershell
cd D:\xamppdev\htdocs\demo
wp db query "SHOW TABLES LIKE 'wp_edubot%';"
```

### Via Direct SQL:
```sql
SHOW TABLES LIKE 'wp_edubot%';
```

Should return 15 tables.

---

## 🧩 What Each Table Stores

### Core Table (THE FIX)
**`wp_edubot_enquiries`** - All enquiry submissions
- Enquiry number, student name, parent name, email, phone
- Grade, academic year, date of birth
- Source, status, notes
- Tracking data: IP, user agent, UTM parameters, click IDs
- MCB sync status

### Visitor & Analytics
**`wp_edubot_visitors`** - Website visitor tracking  
**`wp_edubot_visitor_analytics`** - Daily analytics summary

### Attribution & Journey
**`wp_edubot_attribution_journeys`** - Complete customer path from first visit to conversion  
**`wp_edubot_attribution_sessions`** - Individual chat sessions  
**`wp_edubot_attribution_touchpoints`** - Each individual interaction

### Applications & Conversions
**`wp_edubot_applications`** - School applications submitted  
**`wp_edubot_conversions`** - Conversion events tracking

### Integrations
**`wp_edubot_api_integrations`** - WhatsApp, Google, Facebook API credentials  
**`wp_edubot_api_logs`** - All API calls (audit trail)  
**`wp_edubot_mcb_settings`** - MyClassBoard API config  
**`wp_edubot_mcb_sync_log`** - MCB sync history

### Configuration
**`wp_edubot_school_configs`** - Per-school settings  
**`wp_edubot_report_schedules`** - Email report scheduling  
**`wp_edubot_logs`** - General application logs

---

## ⚠️ Troubleshooting

### If script shows "Access denied"
- Make sure you're logged in to WordPress as Admin
- Only Admin users can run this setup

### If tables already exist
- Script shows "skipped" for existing tables
- This is normal and safe
- Only new tables are created

### If you see errors
- Note the exact error message
- Check database permissions
- Verify database character set is utf8mb4

---

## 🔐 Security Note

**After setup completes:**
1. Delete the `setup-edubot-tables.php` file from server
2. Anyone with access could see database info in the script
3. Script should only be used once, then removed

---

## 📝 Next Steps

1. ✅ Run setup script: `http://localhost/demo/setup-edubot-tables.php`
2. ✅ Verify all 15 tables created (check phpMyAdmin)
3. ✅ Delete the setup script from server
4. ✅ Go to chatbot page and submit a test enquiry
5. ✅ Verify enquiry appears in WordPress admin → EduBot Enquiries

---

## 🎯 Expected Result

**After running setup + submitting enquiry:**

```
✓ Enquiry form submits successfully
✓ No database error
✓ Confirmation message appears
✓ Data saved to wp_edubot_enquiries table
✓ MCB sync logs created
✓ Visitor tracked
```

**Chatbot error should now say:**
```
✓ Thank you for your enquiry!
✓ We will contact you soon.
```

(Instead of: ❌ Error: Database insert failed)

---

**Status:** ✅ Setup Script Ready  
**Location:** `D:\xamppdev\htdocs\demo\setup-edubot-tables.php`  
**Tables:** 15 total (1 was critical - wp_edubot_enquiries)  
**Next Action:** Run the script via browser

