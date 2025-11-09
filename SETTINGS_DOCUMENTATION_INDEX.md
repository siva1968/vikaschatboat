# 📚 EduBot Pro - Settings Documentation Index

**Complete Settings, Backup & Restore System**
**Version:** 1.4.2 | **Date:** November 6, 2025 | **Status:** ✅ Complete

---

## 🎯 Quick Navigation

### For Users (Non-Technical)
1. **Start Here:** [Quick Start Guide](#quick-start-guide)
2. **How to Backup:** [Backup Instructions](#backup-instructions)
3. **How to Restore:** [Restore Instructions](#restore-instructions)
4. **FAQ:** [Frequently Asked Questions](#frequently-asked-questions)

### For Administrators
1. **Overview:** [What Gets Saved](#what-gets-saved)
2. **Tools:** [Available Tools](#available-tools)
3. **Security:** [Security & Protection](#security--protection)
4. **Troubleshooting:** [Troubleshooting Guide](#troubleshooting-guide)

### For Developers
1. **Database Schema:** [DATABASE_SCHEMA_REFERENCE.md](DATABASE_SCHEMA_REFERENCE.md)
2. **Complete Documentation:** [SETTINGS_COMPLETE_DOCUMENTATION.md](SETTINGS_COMPLETE_DOCUMENTATION.md)
3. **Code Examples:** [Code Access Patterns](#code-access-patterns)
4. **API Classes:** [Classes & Methods](#classes--methods)

---

## 🚀 Quick Start Guide

### 1️⃣ Export Your Current Settings (Backup)

**Step 1:** Open in browser
```
http://localhost/demo/export_settings_backup.php?format=html
```

**Step 2:** Review all your settings in the HTML report

**Step 3:** Download as JSON for backup
```
http://localhost/demo/export_settings_backup.php?format=json
```

**Step 4:** Save the JSON file to a safe location

**Estimated Time:** 2 minutes

---

### 2️⃣ Restore Settings from Backup

**Step 1:** Open import tool
```
http://localhost/demo/import_settings_restore.php
```

**Step 2:** Upload your backup file (JSON or SQL)

**Step 3:** Click "Import Settings"

**Step 4:** Review the import results

**Step 5:** Manually re-enter API keys (for security)

**Estimated Time:** 5 minutes

---

### 3️⃣ View All Settings

**HTML Report** (easiest to read)
```
http://localhost/demo/export_settings_backup.php?format=html
```

**JSON File** (programmatic access)
```
http://localhost/demo/export_settings_backup.php?format=json
```

**SQL Statements** (database backup)
```
http://localhost/demo/export_settings_backup.php?format=sql
```

---

## 📋 What Gets Saved

### ✅ Settings Included in Export

**School Information:**
- School name
- Logo
- Brand colors
- Contact information (phone, email, address, website)

**Form Configuration:**
- Required fields (student name, parent name, phone, email, grade)
- Optional fields
- Custom fields
- Academic years
- Education boards
- Grades
- Photo collection settings

**Chatbot Settings:**
- Welcome message
- Completion message
- Language
- AI model
- Response style
- Retry count
- Session timeout

**Notification Settings:**
- WhatsApp enabled/disabled
- Email enabled/disabled
- SMS enabled/disabled
- Admin notifications
- Parent notifications

**Message Templates:**
- Welcome message
- Completion message
- WhatsApp template
- Email subject
- Email body

**API Integrations (non-sensitive):**
- Email provider type
- WhatsApp provider type
- SMS provider type
- OpenAI model
- Provider configuration

**Automation Settings:**
- Auto-send brochure
- Follow-up emails
- Follow-up delay
- Reminder sequences

**WordPress Options (9 settings):**
- Academic years
- Boards
- Calendar type
- School ID
- And more...

---

### ❌ NOT Included (Security Reasons)

**Protected Credentials:**
- ❌ WhatsApp API tokens
- ❌ Email API keys
- ❌ SMS API keys
- ❌ OpenAI API keys
- ❌ SMTP passwords

**Important:** You must manually re-enter these keys after importing.

---

## 🛠️ Available Tools

### 1. Export Settings Tool

**File:** `export_settings_backup.php`
**Location:** `http://localhost/demo/export_settings_backup.php`
**Requires:** Admin privileges

**Features:**
- ✅ 3 export formats (JSON, SQL, HTML)
- ✅ Complete metadata included
- ✅ Automatic download
- ✅ Security masking for sensitive data
- ✅ Beautiful HTML report

**Formats:**

| Format | Best For | File Type |
|--------|----------|-----------|
| JSON | Backup & restore | .json |
| SQL | Database dump | .sql |
| HTML | Review & report | .html |

**Usage Examples:**

```
# HTML Report (view in browser)
http://localhost/demo/export_settings_backup.php?format=html

# JSON Download (for backup)
http://localhost/demo/export_settings_backup.php?format=json

# SQL Download (database backup)
http://localhost/demo/export_settings_backup.php?format=sql
```

---

### 2. Import Settings Tool

**File:** `import_settings_restore.php`
**Location:** `http://localhost/demo/import_settings_restore.php`
**Requires:** Admin privileges

**Features:**
- ✅ File upload interface
- ✅ Format validation
- ✅ Error handling
- ✅ Success reporting
- ✅ Support for JSON and SQL files

**Process:**
1. Upload backup file
2. System validates format
3. Imports all settings
4. Reports results
5. (Manual) Re-enter API keys

---

### 3. Diagnostic Tool

**File:** `comprehensive_diagnostic.php`
**Purpose:** Check system status
**Usage:** Verify all settings are configured correctly

---

## 🔐 Security & Protection

### Encryption Implementation

**What's Encrypted:**
- WhatsApp tokens
- Email API keys
- SMS API keys
- OpenAI API keys
- SMTP passwords

**Where Encrypted:**
- In database storage
- Using WordPress security key
- Decrypted only when needed

**Access Control:**
- Admin privileges required for export/import
- Settings visible only in WordPress admin panel
- No credentials shown in public-facing areas

### Backup Security

**Best Practices:**
1. ✅ Store backups in secure location
2. ✅ Don't email backup files
3. ✅ Don't upload to public servers
4. ✅ Encrypt backup files if storing externally
5. ✅ Keep backup access restricted

**Data Protection:**
- Backups contain non-sensitive fields only
- API keys are NEVER exported
- Phone numbers & emails are included (not sensitive)
- Configuration data is included (not sensitive)

---

## 📊 Database Structure

### 3 Storage Locations

**1. wp_options** (WordPress standard table)
- 9 EduBot settings
- Frequently accessed configuration
- Example: `edubot_current_school_id`, `edubot_available_academic_years`

**2. wp_edubot_school_configs** (Custom table)
- All school configuration as JSON
- Single row per school
- Includes: school info, form settings, chatbot settings, messages

**3. wp_edubot_api_integrations** (Custom table)
- API provider credentials (flat structure)
- Single row per site
- Includes: WhatsApp, Email, SMS, OpenAI configs

**Total Size:** ~20-45 KB per installation

---

## 💻 Code Access Patterns

### Get School Configuration

```php
// Get all settings
$config = EduBot_School_Config::getInstance();
$all_settings = $config->get_config();

// Access specific setting
$school_name = $all_settings['school_info']['name'];
$email_enabled = $all_settings['notification_settings']['email_enabled'];
```

### Get API Configuration

```php
// Get API settings from api_integrations table
$api_settings = EduBot_API_Migration::get_api_settings($blog_id);

// Access specific credential
$email_provider = $api_settings['email_provider'];
$whatsapp_token = $api_settings['whatsapp_token'];  // Decrypted
```

### Get WordPress Option

```php
// Get individual option
$welcome_msg = get_option('edubot_welcome_message', 'Default');

// Update option
update_option('edubot_current_school_id', 2);
```

### Update School Settings

```php
// Modify and save
$config = EduBot_School_Config::getInstance();
$current = $config->get_config();
$current['school_info']['name'] = 'New Name';
$config->update_config($current);  // Auto-encrypts API keys
```

---

## 🎯 Classes & Methods

| Class | Method | Purpose |
|-------|--------|---------|
| `EduBot_School_Config` | `getInstance()` | Get singleton instance |
| | `get_config()` | Read all settings (cached) |
| | `update_config()` | Save all settings |
| | `clear_cache()` | Clear in-memory cache |
| | `get_message()` | Get template with substitution |
| `EduBot_API_Migration` | `get_api_settings()` | Read API credentials |
| | `migrate_api_settings()` | Migrate from options to table |
| `EduBot_Security_Manager` | `encrypt()` | Encrypt sensitive data |
| | `decrypt()` | Decrypt sensitive data |

---

## 📚 Documentation Files

### 1. SETTINGS_BACKUP_RESTORE_GUIDE.md
**Quick start guide for users**
- Simple instructions
- Security notes
- Before/after checklists
- File size: ~400 lines

### 2. SETTINGS_COMPLETE_DOCUMENTATION.md
**Complete technical reference**
- All 60+ settings documented
- Database schema details
- Code examples
- Troubleshooting guide
- File size: ~2,500 lines

### 3. DATABASE_SCHEMA_REFERENCE.md
**Database structure & queries**
- Visual schema diagram
- Table definitions
- Sample queries
- Common operations
- File size: ~1,200 lines

### 4. SETTINGS_IMPLEMENTATION_SUMMARY.md
**Implementation overview**
- Architecture diagram
- Benefits overview
- Verification checklist
- File size: ~600 lines

### 5. SETTINGS_DOCUMENTATION_INDEX.md (This file)
**Navigation & reference**
- Quick links
- Tool descriptions
- Code patterns
- FAQ

---

## ❓ Frequently Asked Questions

### Q: Are my API keys backed up?
**A:** No. For security, API keys are NOT included in backups. You must manually re-enter them after importing.

### Q: Can I restore to a different WordPress site?
**A:** Yes. Export from one site, upload the backup file to another site's import tool. Make sure you have the same plugins installed.

### Q: How often should I backup?
**A:** Before making major changes. At minimum, monthly or whenever you update API credentials.

### Q: What if I lose the backup file?
**A:** Your settings are still in the database. You can re-export anytime from the export tool.

### Q: Can I edit the backup file before importing?
**A:** Yes, JSON format is editable. Use a JSON editor. Be careful with formatting. SQL format is also editable if you know SQL.

### Q: How do I backup just the database?
**A:** Use phpMyAdmin or mysqldump command (see DATABASE_SCHEMA_REFERENCE.md).

### Q: Are WordPress options included in backup?
**A:** Yes. All `edubot_*` options from wp_options table are included.

### Q: What about application/enquiry data?
**A:** No. Backups only include CONFIGURATION settings. Application data is separate and not backed up by these tools.

### Q: Can I backup multiple schools?
**A:** Yes. The export tool backs up all schools for the current site (blog_id).

### Q: Is the backup file secure?
**A:** It contains non-sensitive configuration only. Don't email or upload to public places. Store in secure location.

---

## 🆘 Troubleshooting Guide

### Export Not Working

**Problem:** Export tool shows error
**Solution:**
1. Verify you're logged in as admin
2. Check database tables exist (via phpMyAdmin)
3. Review WordPress debug log
4. Check file permissions

**Debug:**
```
http://localhost/demo/export_settings_backup.php?format=html
Check browser console for errors
Check /wp-content/debug.log
```

---

### Import Failing

**Problem:** Import says "Invalid file" or shows errors
**Solution:**
1. Verify file is JSON or SQL format
2. Check JSON syntax (use JSON validator online)
3. Ensure file is not corrupted
4. Try SQL format instead

**Debug:**
```
Validate JSON file online: jsonlint.com
Check file size (should be <10MB)
Try opening in text editor to verify content
```

---

### Settings Not Showing After Import

**Problem:** Settings appear to not be imported
**Solution:**
1. Refresh browser (clear cache)
2. Check admin panel for new values
3. Run diagnostic tool
4. Check import results carefully

**Debug:**
```
Access: http://localhost/demo/comprehensive_diagnostic.php
Check: Database tables for new data
Review: Import results message
```

---

### API Keys Lost After Import

**Problem:** API keys are blank after importing
**This is expected.** API keys are never exported for security.

**Solution:**
1. Re-enter API keys in EduBot API Settings page
2. Test each provider after adding keys
3. No special action needed

---

## ✅ Verification Checklist

### Before Backup
- [ ] Logged in as admin
- [ ] All settings configured correctly
- [ ] API keys are active and working
- [ ] Test notifications are sending

### Creating Backup
- [ ] Access export tool
- [ ] Review HTML report
- [ ] Export as JSON
- [ ] Save file to safe location
- [ ] Test file is valid (try opening in text editor)

### Before Restore
- [ ] Have database backup ready
- [ ] Know your API keys
- [ ] Test on development copy first
- [ ] Backup file is not corrupted

### After Restore
- [ ] Re-enter API keys
- [ ] Test each notification type
- [ ] Verify settings in admin panel
- [ ] Check comprehensive diagnostic tool

---

## 🔗 Quick Links

### Tools
- **Export Settings:** http://localhost/demo/export_settings_backup.php?format=html
- **Import Settings:** http://localhost/demo/import_settings_restore.php
- **Diagnostic Tool:** http://localhost/demo/comprehensive_diagnostic.php

### Admin Panel
- **API Settings:** /wp-admin/admin.php?page=edubot-api-settings
- **Main Settings:** /wp-admin/admin.php?page=edubot-settings
- **Dashboard:** /wp-admin/

### Documentation
- **Backup/Restore Guide:** SETTINGS_BACKUP_RESTORE_GUIDE.md
- **Complete Documentation:** SETTINGS_COMPLETE_DOCUMENTATION.md
- **Database Schema:** DATABASE_SCHEMA_REFERENCE.md
- **Implementation Summary:** SETTINGS_IMPLEMENTATION_SUMMARY.md

---

## 📞 Support Resources

### Documentation
1. Check [SETTINGS_COMPLETE_DOCUMENTATION.md](SETTINGS_COMPLETE_DOCUMENTATION.md) for all settings
2. Check [DATABASE_SCHEMA_REFERENCE.md](DATABASE_SCHEMA_REFERENCE.md) for database details
3. Check this index for quick reference

### Debugging
1. Run: `comprehensive_diagnostic.php`
2. Check: `/wp-content/debug.log`
3. Review: Import/export error messages
4. Verify: Database tables in phpMyAdmin

### Common Issues
1. See: Troubleshooting section above
2. Run: Diagnostic tool
3. Check: WordPress debug log
4. Verify: Admin privileges

---

## 🎓 Learning Path

### For First-Time Users
1. Read: [Quick Start Guide](#quick-start-guide) (5 min)
2. Do: Export your settings (2 min)
3. Review: HTML report (5 min)
4. Read: [Security & Protection](#security--protection) (5 min)
5. Total Time: ~20 minutes

### For Administrators
1. Read: This entire index (10 min)
2. Understand: [What Gets Saved](#what-gets-saved) (5 min)
3. Know: [Security & Protection](#security--protection) (5 min)
4. Practice: Backup and restore on test site (15 min)
5. Total Time: ~35 minutes

### For Developers
1. Read: [SETTINGS_COMPLETE_DOCUMENTATION.md](SETTINGS_COMPLETE_DOCUMENTATION.md) (30 min)
2. Read: [DATABASE_SCHEMA_REFERENCE.md](DATABASE_SCHEMA_REFERENCE.md) (20 min)
3. Study: [Code Access Patterns](#code-access-patterns) (15 min)
4. Run: Example queries against your database (15 min)
5. Total Time: ~80 minutes

---

## 📊 System Overview

```
┌─────────────────────────────────────────────────────┐
│  EduBot Pro - Settings Management System            │
├─────────────────────────────────────────────────────┤
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  Export Tool                               │    │
│  │  - Read all 3 storage locations            │    │
│  │  - 3 format options (JSON/SQL/HTML)        │    │
│  │  - Mask sensitive data                     │    │
│  └────────────────────────────────────────────┘    │
│                    ↓ ↑                              │
│  ┌────────────────────────────────────────────┐    │
│  │  Database Storage                          │    │
│  │  - wp_options (9 settings)                 │    │
│  │  - wp_edubot_school_configs (JSON)        │    │
│  │  - wp_edubot_api_integrations (flat)      │    │
│  └────────────────────────────────────────────┘    │
│                    ↓ ↑                              │
│  ┌────────────────────────────────────────────┐    │
│  │  Import Tool                               │    │
│  │  - Upload backup files                     │    │
│  │  - Validate format                         │    │
│  │  - Restore to database                     │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
│  ┌────────────────────────────────────────────┐    │
│  │  Diagnostic Tool                           │    │
│  │  - Verify system status                    │    │
│  │  - Check all configurations                │    │
│  │  - Generate reports                        │    │
│  └────────────────────────────────────────────┘    │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.4.2 | Nov 6, 2025 | Complete settings backup/restore system implemented |
| 1.4.1 | Earlier | API migration from options to api_integrations table |
| 1.4.0 | Earlier | Initial plugin version |

---

## ✅ Implementation Status

**Status:** ✅ **COMPLETE & PRODUCTION READY**

- [x] Export tool (JSON, SQL, HTML)
- [x] Import tool (file upload, validation)
- [x] Security masking for API keys
- [x] Complete documentation
- [x] Quick start guide
- [x] Database schema documentation
- [x] Code examples and patterns
- [x] Troubleshooting guide
- [x] FAQ section
- [x] Verification checklist

---

**Last Updated:** November 6, 2025
**EduBot Pro Version:** 1.4.2
**Documentation Version:** 1.0
