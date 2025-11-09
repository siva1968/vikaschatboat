# EduBot Pro - Settings Backup & Restore Quick Guide

**Created:** November 6, 2025

---

## 🚀 Quick Start

### Export Your Settings (Backup)

**Option 1: HTML Report (Easiest to read)**
```
http://localhost/demo/export_settings_backup.php?format=html
```
This opens a beautiful HTML report showing all settings in a readable format.

**Option 2: JSON Format (Best for restore)**
```
http://localhost/demo/export_settings_backup.php?format=json
```
Downloads a JSON file containing all settings (recommended for backup/restore).

**Option 3: SQL Format (For database import)**
```
http://localhost/demo/export_settings_backup.php?format=sql
```
Downloads SQL statements you can run in phpMyAdmin.

---

### Restore Your Settings

**Step 1:** Go to import tool
```
http://localhost/demo/import_settings_restore.php
```

**Step 2:** Upload your backup file (JSON or SQL)

**Step 3:** Click "Import Settings"

**Step 4:** Verify in WordPress admin panel

---

## 📊 What Gets Saved & Retrieved

### Saved Settings (Complete List)

#### 1. School Information
- School name
- School logo
- Brand colors (primary, secondary)
- Contact info (phone, email, address, website)

#### 2. Form Settings
- Required fields (student name, parent name, phone, email, grade)
- Optional fields
- Custom fields
- Academic years
- Boards (CBSE, ICSE, IGCSE, etc.)
- Grades (Pre-K through XII)
- Photo collection settings
- Previous school requirements

#### 3. Chatbot Settings
- Welcome message
- Completion message
- Language
- AI model (GPT-3.5-turbo or GPT-4)
- Response style
- Max retries
- Session timeout

#### 4. Notification Settings
- WhatsApp enabled/disabled
- Email enabled/disabled
- SMS enabled/disabled
- Admin notifications
- Parent notifications

#### 5. Automation Settings
- Auto-send brochure
- Follow-up emails
- Follow-up delay (hours)
- Reminder sequences

#### 6. Message Templates
- Welcome message
- Completion message
- WhatsApp template
- Email subject
- Email body template

#### 7. API Integration Settings
- Email provider (ZeptoMail, SendGrid, Mailgun, SMTP)
- Email from address and name
- WhatsApp provider (Meta, Twilio)
- WhatsApp phone ID and business account ID
- SMS provider and sender ID
- OpenAI model

#### 8. WordPress Options
- Current school ID
- Configured boards
- Default board
- Academic calendar type
- Available academic years
- Default academic year

---

## 🔐 Security Notes

### What IS Exported
✅ All configuration data
✅ School information
✅ Form settings
✅ Notification settings
✅ Message templates
✅ Non-sensitive fields (email addresses, phone numbers)

### What is NOT Exported (For Security)
❌ WhatsApp API tokens
❌ Email API keys
❌ SMS API keys
❌ OpenAI API keys
❌ SMTP passwords
❌ Any encrypted credentials

**Important:** You must manually re-enter your API keys after importing. They are not included in backups for security reasons.

---

## 📋 Database Tables

### wp_edubot_school_configs
Stores all school configurations as JSON
- ID
- Site ID
- School Name
- Config Data (JSON)
- Status
- Created/Updated timestamps

### wp_edubot_api_integrations
Stores API provider credentials (non-sensitive fields)
- ID
- Site ID
- Email provider, from address, from name, domain
- WhatsApp provider, phone ID, business account ID
- SMS provider, sender ID
- OpenAI model
- Notification settings
- Status
- Created/Updated timestamps

### wp_options (WordPress)
Stores individual EduBot options
- edubot_welcome_message
- edubot_current_school_id
- edubot_configured_boards
- edubot_default_board
- edubot_board_selection_required
- edubot_academic_calendar_type
- edubot_custom_start_month
- edubot_available_academic_years
- edubot_admission_period
- edubot_default_academic_year

---

## 🔧 Access Methods in Code

### Get All School Settings
```php
$config = EduBot_School_Config::getInstance();
$all_settings = $config->get_config();
```

### Get Specific Setting
```php
$config = EduBot_School_Config::getInstance();
$school_name = $config->get_config()['school_info']['name'];
$email_enabled = $config->get_config()['notification_settings']['email_enabled'];
```

### Get API Settings
```php
$api_config = EduBot_API_Migration::get_api_settings(get_current_blog_id());
$email_provider = $api_config['email_provider'];
$whatsapp_provider = $api_config['whatsapp_provider'];
```

### Get WordPress Option
```php
$current_school = get_option('edubot_current_school_id', 1);
$welcome_message = get_option('edubot_welcome_message', '');
```

### Update Settings
```php
$config = EduBot_School_Config::getInstance();
$current_config = $config->get_config();
$current_config['school_info']['name'] = 'New School Name';
$config->update_config($current_config);
```

---

## 📁 Files Created

### Tools
1. **export_settings_backup.php** - Export tool (3 formats: JSON, SQL, HTML)
2. **import_settings_restore.php** - Import/restore tool
3. **SETTINGS_COMPLETE_DOCUMENTATION.md** - Full technical documentation

### Usage
- **Location:** WordPress root directory (`D:\xamppdev\htdocs\demo\`)
- **Access:** Via browser with admin privileges
- **Requires:** Logged-in WordPress admin

---

## ✅ Backup Checklist

Before making changes to your site:
- [ ] Go to export tool
- [ ] Generate HTML report to review all settings
- [ ] Export as JSON for backup
- [ ] Save JSON file to secure location
- [ ] (Optional) Export as SQL for database backup

---

## ⚠️ Before You Import

1. **Have a database backup** - Use phpMyAdmin or command line
2. **Verify the backup file** - Make sure it's the correct one
3. **Check API keys** - Prepare your API keys to re-enter after import
4. **Test on development** - Import to development site first
5. **Review imported settings** - Check admin panel after import

---

## 🆘 Troubleshooting

### Settings not exporting
- Ensure you're logged in as admin
- Check database tables exist in phpMyAdmin
- Check WordPress debug log for errors

### Import failing
- Verify file is valid JSON or SQL
- Check database permissions
- Ensure tables exist
- Review error messages shown

### Settings still showing old values
- Settings may be cached - refresh browser
- Check if settings are in both databases
- Clear WordPress cache if using cache plugin

---

## 📞 Support

For issues or questions:
1. Check `SETTINGS_COMPLETE_DOCUMENTATION.md` for technical details
2. Review WordPress debug log: `/wp-content/debug.log`
3. Run comprehensive diagnostic: `comprehensive_diagnostic.php`

---

## 🎯 Next Steps

1. **Access export tool:**
   ```
   http://localhost/demo/export_settings_backup.php?format=html
   ```

2. **Review your current settings** in the HTML report

3. **Create a backup:**
   ```
   http://localhost/demo/export_settings_backup.php?format=json
   ```

4. **Save the JSON file** to a safe location

5. **For restoration, use:**
   ```
   http://localhost/demo/import_settings_restore.php
   ```

---

**Version:** EduBot Pro v1.4.2
**Last Updated:** November 6, 2025
