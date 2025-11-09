# EduBot Pro - Database Schema & Settings Map

**Version:** 1.4.2
**Date:** November 6, 2025

---

## 📊 Visual Database Schema

### Overview

```
┌────────────────────────────────────────────────────────────────────┐
│                    WordPress Database                              │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│  ┌──────────────────┐  ┌────────────────────┐  ┌──────────────┐  │
│  │   wp_options     │  │wp_edubot_school_   │  │wp_edubot_api_│  │
│  │   (Standard WP)  │  │    configs         │  │integrations  │  │
│  │                  │  │   (EduBot Custom)  │  │(EduBot      │  │
│  │ - option_name    │  │                    │  │ Custom)     │  │
│  │ - option_value   │  │ - id               │  │              │  │
│  │ - autoload       │  │ - site_id          │  │ - id         │  │
│  │                  │  │ - school_name      │  │ - site_id    │  │
│  │ 10+ EduBot opts  │  │ - config_data (JSON│  │ - whatsapp_* │  │
│  │                  │  │ - status           │  │ - email_*    │  │
│  │                  │  │ - created_at       │  │ - sms_*      │  │
│  │                  │  │ - updated_at       │  │ - openai_*   │  │
│  │                  │  │                    │  │ - notification │  │
│  │                  │  │                    │  │ - status     │  │
│  │                  │  │                    │  │ - created_at │  │
│  │                  │  │                    │  │ - updated_at │  │
│  └──────────────────┘  └────────────────────┘  └──────────────┘  │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘
```

---

## 📋 Table Details

### 1. wp_options Table

**Purpose:** Store individual WordPress settings

**EduBot Settings Stored:**

```
┌─────────────────────────────────────┬────────────┬──────────────┐
│ Option Name                         │ Type       │ Description  │
├─────────────────────────────────────┼────────────┼──────────────┤
│ edubot_welcome_message              │ string     │ Chat intro   │
│ edubot_current_school_id            │ integer    │ Active school│
│ edubot_configured_boards            │ array      │ CBSE, ICSE   │
│ edubot_default_board                │ string     │ Default board│
│ edubot_board_selection_required     │ boolean    │ Required?    │
│ edubot_academic_calendar_type       │ string     │ april-march  │
│ edubot_custom_start_month           │ integer    │ 1-12         │
│ edubot_available_academic_years     │ array      │ [2024, 2025] │
│ edubot_admission_period             │ string     │ current/next │
│ edubot_default_academic_year        │ string     │ 2025-26      │
└─────────────────────────────────────┴────────────┴──────────────┘
```

**Query to view:**
```sql
SELECT option_name, option_value 
FROM wp_options 
WHERE option_name LIKE 'edubot_%' 
ORDER BY option_name;
```

---

### 2. wp_edubot_school_configs Table

**Purpose:** Store complete school configuration as JSON

**Structure:**

```sql
CREATE TABLE wp_edubot_school_configs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL UNIQUE,
    school_name VARCHAR(255),
    config_data LONGTEXT NOT NULL,  -- JSON format
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**JSON Structure Inside config_data:**

```json
{
    "school_info": {
        "name": "string",
        "logo": "string (URL or base64)",
        "colors": {
            "primary": "#4facfe",
            "secondary": "#00f2fe"
        },
        "contact_info": {
            "phone": "+1234567890",
            "email": "contact@school.com",
            "address": "123 School Street",
            "website": "https://school.com"
        }
    },
    "api_keys": {
        "openai_key": "sk-...",
        "whatsapp_token": "ENCRYPTED",
        "whatsapp_phone_id": "614525638411206",
        "whatsapp_provider": "meta",
        "email_service": "zeptomail",
        "email_api_key": "ENCRYPTED",
        "email_domain": "mail.school.com",
        "smtp_host": "smtp.gmail.com",
        "smtp_port": 587,
        "smtp_username": "user@gmail.com",
        "smtp_password": "ENCRYPTED",
        "sms_provider": "twilio",
        "sms_api_key": "ENCRYPTED",
        "sms_sender_id": "SCHOOL"
    },
    "form_settings": {
        "required_fields": ["student_name", "parent_name", "phone", "email", "grade"],
        "optional_fields": ["address", "previous_school", "sibling_info"],
        "custom_fields": [],
        "academic_years": ["2025-26", "2026-27"],
        "boards": ["CBSE", "ICSE", "IGCSE"],
        "grades": ["Pre-K", "K", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"],
        "collect_parent_photos": false,
        "collect_student_photo": true,
        "require_previous_school": false,
        "collect_sibling_info": false
    },
    "chatbot_settings": {
        "welcome_message": "Hello! 👋 Welcome to...",
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
        "email_template": "Dear {parent_name}...\n\n..."
    }
}
```

**Sample Query:**
```sql
SELECT id, site_id, school_name, status, created_at, updated_at 
FROM wp_edubot_school_configs 
WHERE site_id = 1;

-- View JSON content:
SELECT config_data 
FROM wp_edubot_school_configs 
WHERE site_id = 1;

-- Pretty-print in phpMyAdmin or JSON viewer:
SELECT JSON_PRETTY(config_data) 
FROM wp_edubot_school_configs 
WHERE site_id = 1;
```

---

### 3. wp_edubot_api_integrations Table

**Purpose:** Store API provider credentials and settings (flat structure)

**Structure:**

```sql
CREATE TABLE wp_edubot_api_integrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL UNIQUE,
    
    -- WhatsApp Configuration
    whatsapp_provider VARCHAR(50),           -- 'meta', 'twilio', etc.
    whatsapp_token VARCHAR(500),             -- ENCRYPTED
    whatsapp_phone_id VARCHAR(100),          -- 614525638411206
    whatsapp_business_account_id VARCHAR(100),
    
    -- Email Configuration
    email_provider VARCHAR(50),              -- 'zeptomail', 'sendgrid', 'mailgun', 'smtp'
    email_from_address VARCHAR(100),         -- info@school.com
    email_from_name VARCHAR(100),            -- School Name
    email_api_key VARCHAR(500),              -- ENCRYPTED
    email_domain VARCHAR(100),               -- mail.school.com
    
    -- SMS Configuration
    sms_provider VARCHAR(50),                -- 'twilio', 'sns', etc.
    sms_api_key VARCHAR(500),                -- ENCRYPTED
    sms_sender_id VARCHAR(50),               -- SCHOOL (up to 11 chars)
    
    -- OpenAI Configuration
    openai_api_key VARCHAR(500),             -- ENCRYPTED
    openai_model VARCHAR(50),                -- 'gpt-3.5-turbo', 'gpt-4'
    
    -- Settings
    notification_settings JSON,              -- Notification preferences
    
    -- Status & Timestamps
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Sample Query:**
```sql
SELECT id, site_id, 
       whatsapp_provider, whatsapp_phone_id,
       email_provider, email_from_address,
       sms_provider, sms_sender_id,
       openai_model,
       status, created_at, updated_at
FROM wp_edubot_api_integrations 
WHERE site_id = 1;

-- View notification settings:
SELECT JSON_PRETTY(notification_settings) 
FROM wp_edubot_api_integrations 
WHERE site_id = 1;
```

**notification_settings JSON Example:**
```json
{
    "whatsapp_enabled": true,
    "email_enabled": true,
    "sms_enabled": false,
    "admin_notifications": true,
    "parent_notifications": true
}
```

---

## 🔄 Data Flow Diagram

### Reading Settings

```
Application needs settings
          ↓
┌─────────────────────────────────────────┐
│ EduBot_School_Config::getInstance()     │
└─────────────────────────────────────────┘
          ↓
   Check cache? (in memory)
   │       │
   YES    NO
   │       │
   │       └─→ Query wp_edubot_school_configs table
   │               ↓
   │               Decode JSON
   │               ↓
   │               Decrypt API keys
   │               ↓
   │               Cache in memory
   │               ↓
   └─→ Return to application
          ↓
   Application uses:
   - School name
   - Form settings
   - Chatbot settings
   - Notification settings
   - Message templates
```

### Saving Settings

```
Admin updates settings
          ↓
┌─────────────────────────────────────────┐
│ EduBot_School_Config::update_config()   │
└─────────────────────────────────────────┘
          ↓
Merge with existing config
          ↓
Encrypt API keys
          ↓
Convert to JSON
          ↓
┌──────────────────────────────────────────────┐
│ REPLACE INTO wp_edubot_school_configs        │
│ (site_id, school_name, config_data, status) │
│ VALUES (1, 'School', '{json}', 'active')    │
└──────────────────────────────────────────────┘
          ↓
Clear cache
          ↓
✅ Settings saved
```

---

## 📋 Settings Organization

### By Storage Location

**wp_options (9 settings):**
- Academic calendar configuration
- Board selection settings
- Academic year settings
- Welcome message override

**wp_edubot_school_configs (JSON structure):**
- School information
- Form configuration
- Chatbot behavior
- Message templates
- Automation rules
- API keys (encrypted)

**wp_edubot_api_integrations (flat columns):**
- Email provider credentials (non-encrypted fields)
- WhatsApp provider credentials (non-encrypted fields)
- SMS provider credentials (non-encrypted fields)
- OpenAI model selection
- Notification preferences

---

### By Function

**Configuration (read frequently):**
- School name, logo, colors
- Academic years, grades, boards
- Form required/optional fields
- Chatbot model, language, style

**Notifications (read frequently):**
- Which channels enabled/disabled
- Admin/parent notification flags
- Message templates

**API Credentials (read rarely, sensitive):**
- All API keys (encrypted)
- All tokens (encrypted)
- Provider selections
- Configuration details

---

## 🔐 Encryption Model

**Encrypted Fields** (stored in database encrypted):
```
wp_edubot_school_configs.config_data['api_keys']:
- openai_key
- whatsapp_token
- email_api_key
- smtp_password
- sms_api_key

wp_edubot_api_integrations:
- whatsapp_token
- email_api_key
- sms_api_key
- openai_api_key
```

**Encryption Method:**
- Uses: `EduBot_Security_Manager::encrypt()`
- Decryption uses: `EduBot_Security_Manager::decrypt()`
- Key: WordPress security key (from wp-config.php)

**Non-Encrypted but Sensitive:**
```
- Email addresses
- Phone numbers
- Domain names
- Business IDs
- Configuration details
```

---

## 🛠️ Common Database Operations

### View All Settings

```sql
-- School config
SELECT * FROM wp_edubot_school_configs WHERE site_id = 1;

-- API integrations
SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;

-- WordPress options
SELECT option_name, option_value 
FROM wp_options 
WHERE option_name LIKE 'edubot_%';
```

### Update School Name

```sql
UPDATE wp_edubot_school_configs 
SET school_name = 'New School Name',
    updated_at = NOW()
WHERE site_id = 1;
```

### Update Notification Settings

```sql
UPDATE wp_edubot_api_integrations 
SET notification_settings = JSON_SET(
    notification_settings, 
    '$.whatsapp_enabled', 
    true
),
updated_at = NOW()
WHERE site_id = 1;
```

### Export Config as JSON

```sql
SELECT 
    JSON_OBJECT(
        'school_id', id,
        'site_id', site_id,
        'school_name', school_name,
        'config', JSON_PARSE(config_data),
        'exported_at', NOW()
    ) as export_data
FROM wp_edubot_school_configs 
WHERE site_id = 1;
```

### Backup All Settings

```sql
-- School configs
SELECT * INTO OUTFILE '/tmp/school_configs.sql'
FROM wp_edubot_school_configs;

-- API integrations
SELECT * INTO OUTFILE '/tmp/api_integrations.sql'
FROM wp_edubot_api_integrations;

-- WordPress options
SELECT * INTO OUTFILE '/tmp/wp_options.sql'
FROM wp_options 
WHERE option_name LIKE 'edubot_%';
```

---

## 🎯 Access Patterns in Code

### Access School Settings

```php
// Get all settings
$config = EduBot_School_Config::getInstance();
$all_settings = $config->get_config();

// Access nested values
$school_name = $all_settings['school_info']['name'];
$email_enabled = $all_settings['notification_settings']['email_enabled'];
$welcome_msg = $all_settings['chatbot_settings']['welcome_message'];

// Get specific message with variable substitution
$whatsapp_template = $config->get_message('whatsapp_template', array(
    'parent_name' => 'John Doe',
    'student_name' => 'Jane Doe',
    'grade' => '5',
    'academic_year' => '2025-26'
));
```

### Access API Settings

```php
// Get API config from api_integrations table
$api_settings = EduBot_API_Migration::get_api_settings($blog_id);

// Access specific settings
$email_provider = $api_settings['email_provider'];
$email_from = $api_settings['email_from_address'];
$whatsapp_provider = $api_settings['whatsapp_provider'];
$whatsapp_token = $api_settings['whatsapp_token'];
$openai_model = $api_settings['openai_model'];
```

### Access WordPress Options

```php
// Individual options
$welcome = get_option('edubot_welcome_message', '');
$current_school = get_option('edubot_current_school_id', 1);
$boards = get_option('edubot_configured_boards', array());
$years = get_option('edubot_available_academic_years', array());

// Update option
update_option('edubot_current_school_id', 2);
```

---

## 📊 Data Volume Estimates

**Typical Database Size:**

```
wp_options (EduBot settings):
- 10 options × 100-2000 bytes = ~10 KB

wp_edubot_school_configs (per school):
- 1 row × (5KB JSON) = ~5 KB
- Total: 5-20 KB per school

wp_edubot_api_integrations (per site):
- 1 row × 2 KB = ~2 KB
- Total: 2-5 KB per site

Total per installation: 20-45 KB
```

---

## 🔍 Troubleshooting Queries

### Find what's in the database

```sql
-- Check if tables exist
SHOW TABLES LIKE 'wp_edubot%';

-- Count records
SELECT 'school_configs' as table_name, COUNT(*) as count 
FROM wp_edubot_school_configs
UNION ALL
SELECT 'api_integrations', COUNT(*) 
FROM wp_edubot_api_integrations;

-- Check table sizes
SELECT 
    table_name,
    ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
FROM information_schema.TABLES 
WHERE table_schema = DATABASE()
AND table_name LIKE 'wp_edubot%';
```

### Debug: Show all EduBot data

```sql
-- All school configs
SELECT JSON_PRETTY(config_data) FROM wp_edubot_school_configs WHERE site_id = 1;

-- All API integrations
SELECT * FROM wp_edubot_api_integrations WHERE site_id = 1;

-- All WordPress options
SELECT * FROM wp_options WHERE option_name LIKE 'edubot_%' ORDER BY option_name;
```

---

**Database Schema Version:** 1.4.2
**Last Updated:** November 6, 2025
**Status:** ✅ Production Ready
