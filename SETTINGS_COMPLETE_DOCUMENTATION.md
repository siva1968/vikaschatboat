# EduBot Pro - Complete Settings Documentation

**Last Updated:** November 6, 2025
**Version:** 1.4.2

---

## Table of Contents

1. [Overview](#overview)
2. [Database Structure](#database-structure)
3. [School Configuration Settings](#school-configuration-settings)
4. [API Integration Settings](#api-integration-settings)
5. [WordPress Options](#wordpress-options)
6. [How Settings are Stored](#how-settings-are-stored)
7. [How Settings are Retrieved](#how-settings-are-retrieved)
8. [Backup and Restore](#backup-and-restore)
9. [Quick Reference](#quick-reference)

---

## Overview

EduBot Pro stores configuration and settings in multiple locations:

1. **WordPress Options Table** - Traditional WordPress settings storage
2. **wp_edubot_school_configs** - School-specific configurations (JSON format)
3. **wp_edubot_api_integrations** - API provider credentials and settings (flat structure)

This document provides complete information about all settings and how they're managed.

---

## Database Structure

### 1. wp_edubot_school_configs

**Purpose:** Stores all school-specific configurations including branding, form settings, chatbot settings, etc.

**Table Structure:**

```sql
CREATE TABLE wp_edubot_school_configs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    school_name VARCHAR(255),
    config_data LONGTEXT NOT NULL,  -- JSON format
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_site_config (site_id)
);
```

**Data Format:** `config_data` is stored as JSON with the following structure:

```json
{
    "school_info": {
        "name": "School Name",
        "logo": "URL or base64",
        "colors": {
            "primary": "#4facfe",
            "secondary": "#00f2fe"
        },
        "contact_info": {
            "phone": "+1234567890",
            "email": "info@school.com",
            "address": "123 School St",
            "website": "https://school.com"
        }
    },
    "api_keys": {
        "openai_key": "sk-...",
        "whatsapp_token": "...",
        "whatsapp_phone_id": "614525638411206",
        "whatsapp_provider": "meta",
        "email_service": "zeptomail",
        "email_api_key": "...",
        "email_domain": "mail.epistemo.in",
        "smtp_host": "smtp.gmail.com",
        "smtp_port": 587,
        "smtp_username": "user@gmail.com",
        "smtp_password": "...",
        "sms_provider": "twilio",
        "sms_api_key": "...",
        "sms_sender_id": "SENDER"
    },
    "form_settings": {
        "required_fields": ["student_name", "parent_name", "phone", "email", "grade"],
        "optional_fields": ["address", "previous_school", "sibling_info"],
        "custom_fields": [],
        "academic_years": ["2025-26"],
        "boards": ["CBSE", "ICSE", "IGCSE"],
        "grades": ["Pre-K", "K", "I", "II", ...],
        "collect_parent_photos": false,
        "collect_student_photo": true,
        "require_previous_school": false,
        "collect_sibling_info": false
    },
    "chatbot_settings": {
        "welcome_message": "Hello! Welcome to...",
        "completion_message": "Thank you for...",
        "language": "en",
        "ai_model": "gpt-3.5-turbo",
        "response_style": "friendly",
        "max_retries": 3,
        "session_timeout": 30
    },
    "notification_settings": {
        "whatsapp_enabled": true,
        "email_enabled": true,
        "sms_enabled": false,
        "admin_notifications": true,
        "parent_notifications": true
    },
    "automation_settings": {
        "auto_send_brochure": true,
        "follow_up_enabled": true,
        "follow_up_delay": 24,
        "reminder_sequence": []
    },
    "messages": {
        "welcome": "Hello! Welcome...",
        "completion": "Thank you for...",
        "whatsapp_template": "Dear {parent_name}...",
        "email_subject": "Admission Application Received",
        "email_template": "Dear {parent_name}..."
    }
}
```

---

### 2. wp_edubot_api_integrations

**Purpose:** Stores API provider credentials and integration settings (separate from school_configs for security)

**Table Structure:**

```sql
CREATE TABLE wp_edubot_api_integrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    whatsapp_provider VARCHAR(50),           -- 'meta', 'twilio', etc.
    whatsapp_token VARCHAR(500),             -- ENCRYPTED
    whatsapp_phone_id VARCHAR(100),
    whatsapp_business_account_id VARCHAR(100),
    email_provider VARCHAR(50),              -- 'zeptomail', 'sendgrid', 'mailgun'
    email_from_address VARCHAR(100),
    email_from_name VARCHAR(100),
    email_api_key VARCHAR(500),              -- ENCRYPTED
    email_domain VARCHAR(100),
    sms_provider VARCHAR(50),                -- 'twilio', 'sns', etc.
    sms_api_key VARCHAR(500),                -- ENCRYPTED
    sms_sender_id VARCHAR(50),
    openai_api_key VARCHAR(500),             -- ENCRYPTED
    openai_model VARCHAR(50),                -- 'gpt-3.5-turbo', 'gpt-4'
    notification_settings JSON,              -- Settings for which notifications to send
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_site_integration (site_id)
);
```

**Key Features:**
- All API keys are **ENCRYPTED** in the database
- Provider credentials are centralized in one table
- Separate from school_configs for easier security management
- Supports multiple email/SMS/WhatsApp providers

---

## School Configuration Settings

### school_info

| Setting | Type | Purpose | Example |
|---------|------|---------|---------|
| name | string | School name | "Epistemo" |
| logo | string | School logo URL or base64 | "https://..." |
| colors.primary | string | Primary brand color | "#4facfe" |
| colors.secondary | string | Secondary brand color | "#00f2fe" |
| contact_info.phone | string | School phone | "+919876543210" |
| contact_info.email | string | School email | "info@epistemo.in" |
| contact_info.address | string | School address | "123 School Street" |
| contact_info.website | string | School website | "https://epistemo.in" |

**Usage:**
```php
$config = EduBot_School_Config::getInstance();
$school_name = $config->get_config()['school_info']['name'];
```

---

### form_settings

| Setting | Type | Purpose |
|---------|------|---------|
| required_fields | array | Fields that must be filled in the form |
| optional_fields | array | Optional form fields |
| custom_fields | array | Custom fields added by school |
| academic_years | array | Available academic years for admission |
| boards | array | Education boards available (CBSE, ICSE, etc.) |
| grades | array | Grades available for admission |
| collect_parent_photos | bool | Whether to collect parent photos |
| collect_student_photo | bool | Whether to collect student photos |
| require_previous_school | bool | Require previous school information |
| collect_sibling_info | bool | Collect information about siblings |

**Usage:**
```php
$form_settings = $config->get_config()['form_settings'];
$required_fields = $form_settings['required_fields'];
```

---

### chatbot_settings

| Setting | Type | Purpose | Default |
|---------|------|---------|---------|
| welcome_message | string | Initial message shown to user | "Hello! 👋 Welcome..." |
| completion_message | string | Message after form completion | "Thank you! 🎉" |
| language | string | Chatbot language | "en" |
| ai_model | string | OpenAI model to use | "gpt-3.5-turbo" |
| response_style | string | ChatGPT response style | "friendly" |
| max_retries | int | API retry attempts | 3 |
| session_timeout | int | Session timeout in minutes | 30 |

**Usage:**
```php
$chatbot_settings = $config->get_config()['chatbot_settings'];
$welcome_msg = $chatbot_settings['welcome_message'];
```

---

### notification_settings

| Setting | Type | Purpose | Default |
|---------|------|---------|---------|
| whatsapp_enabled | bool | Enable WhatsApp notifications | true |
| email_enabled | bool | Enable email notifications | true |
| sms_enabled | bool | Enable SMS notifications | false |
| admin_notifications | bool | Send notifications to admin | true |
| parent_notifications | bool | Send notifications to parent | true |

**Usage:**
```php
$notif_settings = $config->get_config()['notification_settings'];
if ($notif_settings['whatsapp_enabled']) {
    // Send WhatsApp notifications
}
```

---

### automation_settings

| Setting | Type | Purpose | Default |
|---------|------|---------|---------|
| auto_send_brochure | bool | Automatically send brochure | true |
| follow_up_enabled | bool | Enable follow-up emails | true |
| follow_up_delay | int | Delay in hours for follow-up | 24 |
| reminder_sequence | array | Automated reminder sequence | [] |

---

### messages

Template messages with variable substitution:

| Message | Variables Available |
|---------|-------------------|
| welcome | {school_name} |
| completion | {school_name} |
| whatsapp_template | {parent_name}, {student_name}, {grade}, {academic_year}, {school_name} |
| email_subject | {school_name} |
| email_template | {parent_name}, {student_name}, {grade}, {academic_year}, {application_number}, {submission_date}, {school_phone}, {school_email} |

---

## API Integration Settings

### Email Configuration

| Setting | Type | Encrypted | Purpose |
|---------|------|-----------|---------|
| email_provider | string | No | Provider type (zeptomail, sendgrid, mailgun, smtp) |
| email_from_address | string | No | Sender email address |
| email_from_name | string | No | Sender name |
| email_api_key | string | **YES** | API key for email provider |
| email_domain | string | No | Email domain for verification |

**Supported Providers:**
- **ZeptoMail** - Indian email service (Zoho's transactional email)
- **SendGrid** - Cloud email service
- **Mailgun** - Email service
- **SMTP** - Standard SMTP server
- **WordPress wp_mail()** - Default WordPress mailer

---

### WhatsApp Configuration

| Setting | Type | Encrypted | Purpose |
|---------|------|-----------|---------|
| whatsapp_provider | string | No | Provider type (meta, twilio, etc.) |
| whatsapp_token | string | **YES** | API authentication token |
| whatsapp_phone_id | string | No | WhatsApp business phone ID |
| whatsapp_business_account_id | string | No | Meta business account ID |

**Example:**
```php
$api_config = EduBot_API_Migration::get_api_settings($blog_id);
$whatsapp_config = array(
    'provider' => $api_config['whatsapp_provider'],
    'token' => $api_config['whatsapp_token'],
    'phone_id' => $api_config['whatsapp_phone_id']
);
```

---

### SMS Configuration

| Setting | Type | Encrypted | Purpose |
|---------|------|-----------|---------|
| sms_provider | string | No | Provider type (twilio, sns, etc.) |
| sms_api_key | string | **YES** | API key for SMS provider |
| sms_sender_id | string | No | Sender ID for SMS |

---

### OpenAI Configuration

| Setting | Type | Encrypted | Purpose |
|---------|------|-----------|---------|
| openai_api_key | string | **YES** | OpenAI API key |
| openai_model | string | No | Model to use (gpt-3.5-turbo, gpt-4, etc.) |

---

## WordPress Options

Additional settings stored as WordPress options:

| Option Name | Type | Purpose |
|------------|------|---------|
| edubot_welcome_message | string | Welcome message (overrides config) |
| edubot_current_school_id | int | Currently selected school |
| edubot_configured_boards | array | Available education boards |
| edubot_default_board | string | Default selected board |
| edubot_board_selection_required | bool | Require board selection in form |
| edubot_academic_calendar_type | string | Calendar type (april-march, january-december) |
| edubot_custom_start_month | int | Custom calendar start month (1-12) |
| edubot_available_academic_years | array | Available academic years |
| edubot_admission_period | string | Admission period (current, next) |
| edubot_default_academic_year | string | Default academic year |

**Access via:**
```php
$welcome_msg = get_option('edubot_welcome_message', 'Default');
update_option('edubot_welcome_message', 'New message');
```

---

## How Settings are Stored

### 1. School Configuration Save

**Class:** `EduBot_School_Config`
**Method:** `update_config()`

```php
// Save school configuration
$config = EduBot_School_Config::getInstance();
$config->update_config($updated_config_array);

// What happens:
// 1. Merges with existing config
// 2. Encrypts API keys using EduBot_Security_Manager
// 3. Converts to JSON
// 4. Saves to wp_edubot_school_configs table
// 5. Clears cache
```

**Database Operation:**
```sql
REPLACE INTO wp_edubot_school_configs 
(site_id, school_name, config_data, status)
VALUES (1, 'School Name', '{json_data}', 'active');
```

---

### 2. API Integration Save

**Class:** `EduBot_API_Settings_Page`
**Method:** `handle_api_settings_save()`

```php
// Saves to wp_edubot_api_integrations table
// Encrypts: whatsapp_token, email_api_key, sms_api_key, openai_api_key
// Does NOT encrypt: provider, phone_id, email addresses, etc.
```

---

### 3. WordPress Options Save

```php
// Standard WordPress functions
update_option('edubot_welcome_message', 'New message');
update_option('edubot_current_school_id', 1);

// Stored in wp_options table
```

---

## How Settings are Retrieved

### 1. Get School Configuration

```php
// Recommended approach (uses caching)
$config = EduBot_School_Config::getInstance();
$full_config = $config->get_config();
$school_name = $config->get_config()['school_info']['name'];

// What happens:
// 1. Checks cache first (returns cached data if available)
// 2. Queries wp_edubot_school_configs for site_id
// 3. Decodes JSON
// 4. Merges with default config
// 5. Caches result
// 6. Returns config
```

---

### 2. Get API Settings

```php
// New approach (reads from api_integrations table)
$api_settings = EduBot_API_Migration::get_api_settings(get_current_blog_id());

// Returns:
array(
    'whatsapp_provider' => 'meta',
    'whatsapp_token' => 'decrypted_token',
    'whatsapp_phone_id' => '614525638411206',
    'email_provider' => 'zeptomail',
    'email_from_address' => 'info@epistemo.in',
    'email_api_key' => 'decrypted_key',
    // ... all other settings
)
```

---

### 3. Get WordPress Options

```php
// Individual option
$welcome_msg = get_option('edubot_welcome_message', 'Default');

// Multiple options
$board = get_option('edubot_default_board', '');
$years = get_option('edubot_available_academic_years', array());
```

---

## Backup and Restore

### Export Settings

**Tools Available:**

1. **export_settings_backup.php** - Export tool with multiple formats
   - **JSON Format** - Full settings in structured format (recommended)
   - **SQL Format** - SQL INSERT statements
   - **HTML Format** - Readable HTML report

**Access:**
```
http://localhost/demo/export_settings_backup.php?format=json
http://localhost/demo/export_settings_backup.php?format=sql
http://localhost/demo/export_settings_backup.php?format=html
```

**What's Exported:**
- ✅ School configurations (full)
- ✅ API integrations (non-sensitive fields)
- ✅ WordPress options
- ✅ Notification settings
- ✅ All timestamps and metadata

**What's NOT Exported (Security):**
- ❌ WhatsApp API tokens
- ❌ Email API keys
- ❌ SMS API keys
- ❌ OpenAI API keys
- ❌ SMTP passwords

---

### Import Settings

**Tool:** `import_settings_restore.php`

**Steps:**
1. Access `http://localhost/demo/import_settings_restore.php`
2. Upload backup file (JSON or SQL)
3. Review import results
4. Verify settings in admin panel

**What Gets Imported:**
- School configurations
- API integrations (will overwrite, keys must be re-entered)
- WordPress options

---

### Manual Backup using MySQL

```bash
# Backup school configs
mysqldump -u root -p demo wp_edubot_school_configs > school_configs_backup.sql

# Backup API integrations
mysqldump -u root -p demo wp_edubot_api_integrations > api_integrations_backup.sql

# Restore
mysql -u root -p demo < school_configs_backup.sql
mysql -u root -p demo < api_integrations_backup.sql
```

---

## Quick Reference

### Most Common Settings Access Patterns

**Get school name:**
```php
$config = EduBot_School_Config::getInstance();
$school_name = $config->get_config()['school_info']['name'];
```

**Check if WhatsApp is enabled:**
```php
$config = EduBot_School_Config::getInstance();
$is_whatsapp_enabled = $config->get_config()['notification_settings']['whatsapp_enabled'];
```

**Get API credentials:**
```php
$api_settings = EduBot_API_Migration::get_api_settings(get_current_blog_id());
$whatsapp_token = $api_settings['whatsapp_token'];
$email_api_key = $api_settings['email_api_key'];
```

**Get message template:**
```php
$config = EduBot_School_Config::getInstance();
$variables = array(
    'parent_name' => 'John Doe',
    'student_name' => 'Jane Doe',
    'grade' => '5',
    'academic_year' => '2025-26'
);
$message = $config->get_message('whatsapp_template', $variables);
```

**Update settings:**
```php
$config = EduBot_School_Config::getInstance();
$current_config = $config->get_config();
$current_config['school_info']['name'] = 'New School Name';
$config->update_config($current_config);
```

**Clear cache (debugging):**
```php
EduBot_School_Config::clear_cache();
```

---

### Database Queries

**Get all school configs:**
```sql
SELECT id, school_name, status, created_at, updated_at 
FROM wp_edubot_school_configs;
```

**Get API integration for a site:**
```sql
SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;
```

**View WordPress EduBot options:**
```sql
SELECT option_name, option_value 
FROM wp_options 
WHERE option_name LIKE 'edubot_%';
```

---

### Related Classes

| Class | Purpose | Location |
|-------|---------|----------|
| `EduBot_School_Config` | Manage school config | `class-school-config.php` |
| `EduBot_API_Migration` | Migrate/read API settings | `class-api-migration.php` |
| `EduBot_API_Settings_Page` | Admin interface for API settings | `admin/class-api-settings-page.php` |
| `EduBot_Security_Manager` | Encrypt/decrypt sensitive data | `class-security-manager.php` |
| `EduBot_Database_Manager` | Database operations | `class-database-manager.php` |

---

### Troubleshooting

**Settings not saving?**
1. Check `wp_edubot_school_configs` table exists
2. Check `wp_edubot_api_integrations` table exists
3. Check WordPress debug log for errors
4. Clear cache: `EduBot_School_Config::clear_cache()`

**Settings showing old values?**
1. Settings are cached - clear cache
2. Check both tables have correct `site_id`
3. Verify `status = 'active'`

**API keys not working?**
1. Check keys are correctly encrypted/decrypted
2. Verify keys haven't expired with provider
3. Check API provider is not rate-limited

---

## Additional Resources

- **Backup Tool:** `export_settings_backup.php`
- **Restore Tool:** `import_settings_restore.php`
- **Admin Panel:** `/wp-admin/admin.php?page=edubot-api-settings`
- **Debug Tool:** `comprehensive_diagnostic.php`

---

**Questions or Issues?** Check the WordPress debug log at `/wp-content/debug.log`
