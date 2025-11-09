# MyClassBoard Integration for EduBot Pro - Complete Analysis & Implementation

## 📋 Executive Summary

This document provides a comprehensive analysis of the MyClassBoard integration implementation for EduBot Pro, including:

1. **Database Structure Analysis**
2. **Integration Architecture**
3. **API Synchronization Flow**
4. **Component Documentation**
5. **Configuration Guide**
6. **Troubleshooting & Support**

---

## 🗄️ PART 1: DATABASE STRUCTURE ANALYSIS

### 1.1 EduBot Core Tables

#### `wp_edubot_enquiries` (Primary Enquiry Table)
```sql
Columns: 23+
PRIMARY KEY: id
UNIQUE KEY: enquiry_number

Key Fields for MCB Sync:
├── enquiry_number (VARCHAR 50) - Unique enquiry ID
├── student_name (VARCHAR 255) - Student name
├── parent_name (VARCHAR 255) - Parent/guardian name
├── email (VARCHAR 255) - Primary contact email
├── phone (VARCHAR 20) - Primary contact phone
├── date_of_birth (DATE) - DOB for age verification
├── grade (VARCHAR 50) - Academic grade/class
├── board (VARCHAR 50) - Educational board (CBSE/CAIE)
├── academic_year (VARCHAR 20) - School year
├── address (TEXT) - Physical address
├── source (VARCHAR 50) - Lead source (chatbot/website/etc)
├── created_at (DATETIME) - Submission timestamp
├── status (VARCHAR 50) - Enquiry status
├── whatsapp_sent (TINYINT) - Notification tracking
├── email_sent (TINYINT) - Notification tracking
├── sms_sent (TINYINT) - Notification tracking
└── [NEW] mcb_sync_status (VARCHAR 20) - Sync status (pending/synced/failed)
```

#### `wp_edubot_api_integrations` (API Configuration)
```sql
Columns: 26
PRIMARY KEY: id
UNIQUE KEY: site_id

Contains:
├── whatsapp_* (WhatsApp API config)
├── email_* (Email API config)
├── sms_* (SMS API config)
├── openai_* (OpenAI API config)
└── notification_settings (JSON)
```

#### `wp_edubot_school_configs` (School Configuration)
```sql
Columns: 8
PRIMARY KEY: id

Contains:
├── school_name (VARCHAR 255)
├── config_data (LONGTEXT) - JSON school config
├── api_keys_encrypted (LONGTEXT)
├── branding_settings (LONGTEXT)
├── academic_structure (LONGTEXT)
├── board_settings (LONGTEXT)
└── academic_year_settings (LONGTEXT)
```

### 1.2 New MCB Integration Tables

#### `wp_edubot_mcb_sync_log` (Sync History)
```sql
CREATE TABLE wp_edubot_mcb_sync_log (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    enquiry_id BIGINT(20) NOT NULL,
    request_data LONGTEXT,              -- Sent to MCB
    response_data LONGTEXT,             -- Response from MCB
    success TINYINT(1) DEFAULT 0,       -- 1 = success, 0 = failed
    error_message TEXT,                 -- Error details if failed
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_enquiry (enquiry_id),
    KEY idx_success (success),
    KEY idx_created (created_at)
);
```

#### `wp_edubot_mcb_settings` (Configuration Storage)
```sql
CREATE TABLE wp_edubot_mcb_settings (
    id BIGINT(20) NOT NULL AUTO_INCREMENT,
    site_id BIGINT(20) NOT NULL UNIQUE,
    config_data LONGTEXT NOT NULL,      -- JSON settings
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_site (site_id)
);
```

### 1.3 Database Relationship Diagram

```
┌─────────────────────────────────┐
│  wp_edubot_enquiries            │
├─────────────────────────────────┤
│ id (PK)                         │
│ enquiry_number (UNIQUE)         │◄─────────┐
│ student_name                    │          │
│ email                           │          │
│ phone                           │          │
│ source                          │          │
│ mcb_sync_status (NEW)           │          │
│ mcb_enquiry_id (NEW)            │          │ 1:Many
│ mcb_query_code (NEW)            │          │
└─────────────────────────────────┘          │
                                             │
┌─────────────────────────────────┐          │
│  wp_edubot_mcb_sync_log         │          │
├─────────────────────────────────┤          │
│ id (PK)                         │          │
│ enquiry_id (FK)────────────────────────────┘
│ request_data                    │
│ response_data                   │
│ success (TINYINT)               │
│ error_message                   │
│ created_at                      │
└─────────────────────────────────┘

┌─────────────────────────────────┐
│  wp_edubot_mcb_settings         │
├─────────────────────────────────┤
│ id (PK)                         │
│ site_id (UNIQUE)                │
│ config_data (JSON)              │
│  ├─ enabled                     │
│  ├─ organization_id             │
│  ├─ branch_id                   │
│  ├─ sync_enabled                │
│  ├─ api_key                     │
│  ├─ lead_source_mapping         │
│  └─ other_settings              │
└─────────────────────────────────┘
```

---

## 🔌 PART 2: INTEGRATION ARCHITECTURE

### 2.1 System Components

```
┌────────────────────────────────────────────────────────────┐
│                    INTEGRATION LAYER                       │
└────────────────────────────────────────────────────────────┘
                          ↓
        ┌─────────────────┬──────────────────┐
        ↓                 ↓                  ↓
   ┌─────────┐     ┌──────────┐      ┌────────────┐
   │ Setup   │     │ Core     │      │ Admin      │
   │ Class   │     │ Integration    │ Interface  │
   └─────────┘     └──────────┘      └────────────┘
        ↓                 ↓                  ↓
   Setup DB          Sync Logic        Settings
   Init Hooks        Data Mapping      Dashboard
   Create Tables     API Calls         Logs
```

### 2.2 Core Classes

#### A. `EduBot_MyClassBoard_Integration`
**Purpose:** Core synchronization engine

**Key Methods:**
```php
// Configuration
get_settings()              // Get MCB settings
update_settings($settings)  // Update settings
get_default_settings()      // Default config

// Data Mapping
map_enquiry_to_mcb($enquiry)     // Convert to MCB format
map_grade_to_class($grade)       // Grade → Class mapping
map_grade_to_class_id($grade)    // Grade → Class ID mapping

// Synchronization
sync_enquiry_to_mcb($enquiry_id, $enquiry)  // Perform sync
send_to_mcb($data, $settings)               // Send API call
log_sync($enquiry_id, $request, $response)  // Log attempt

// Monitoring
get_sync_status($enquiry_id)     // Get single sync status
get_sync_stats()                 // Get overall statistics
get_recent_sync_logs($limit)     // Get recent logs

// Utilities
ensure_sync_log_table()          // Create log table if needed
ajax_sync_enquiry()              // Manual sync AJAX handler
```

#### B. `EduBot_MCB_Settings_Page`
**Purpose:** WordPress admin interface for configuration

**Features:**
- Enable/disable integration
- Configure Organization ID & Branch ID
- API key management
- Lead source mapping
- Sync status monitoring
- View sync logs
- Test mode toggle

#### C. `EduBot_MCB_Sync_Dashboard`
**Purpose:** Real-time monitoring dashboard

**Features:**
- Statistics display (total, successful, failed, today, success rate)
- Quick action buttons
- Sync logs with filtering
- Manual retry functionality
- Auto-refresh every 30 seconds

#### D. `EduBot_MCB_Integration_Setup`
**Purpose:** Initialization and setup

**Functions:**
- Load all integration classes
- Register admin menu & pages
- Create database tables
- Setup hooks
- Initialize dashboard widget

### 2.3 Data Flow Sequence

```
1. ENQUIRY SUBMISSION
   └─ User fills EduBot form and submits

2. ENQUIRY STORAGE
   └─ Data saved to wp_edubot_enquiries table

3. TRIGGER EVENT
   └─ do_action('edubot_enquiry_created', $enquiry_id, $enquiry)

4. SYNC INITIATION
   ├─ Check if MCB integration enabled
   ├─ Check if sync enabled
   └─ Check if auto-sync enabled

5. DATA MAPPING
   ├─ Map EduBot fields to MCB format
   ├─ Convert grade to class ID
   ├─ Map lead source to MCB ID
   └─ Create MCB JSON payload

6. API CALL
   ├─ POST to MCB API endpoint
   ├─ Retry if failed (up to 3 times)
   ├─ Wait for response (65 second timeout)
   └─ Parse JSON response

7. RESPONSE HANDLING
   ├─ Check for success indicators
   ├─ Extract MCB enquiry ID and query code
   ├─ Handle duplicates/errors
   └─ Update enquiry with MCB data

8. LOGGING
   ├─ Store request & response in sync_log table
   ├─ Record success/failure
   ├─ Store error message if failed
   └─ Timestamp for audit trail

9. NOTIFICATION (Optional)
   ├─ Update admin if failed
   ├─ Update enquiry status
   └─ Mark sync as complete
```

---

## 🔄 PART 3: SYNCHRONIZATION FLOW

### 3.1 Auto-Sync Flow (Immediate)

```
Enquiry Submitted
    ↓
POST wp_edubot_enquiries
    ↓
do_action('edubot_enquiry_created')
    ↓
[5 second delay]
    ↓
EduBot_MyClassBoard_Integration::sync_enquiry_to_mcb()
    ↓
IF MCB enabled AND sync_enabled AND auto_sync
    ├─ Map to MCB format
    ├─ Attempt API call (up to 3 retries)
    ├─ Parse response
    ├─ Update wp_edubot_enquiries
    └─ Log result to wp_edubot_mcb_sync_log
    ↓
Complete
```

### 3.2 Manual Sync Flow

```
Admin clicks "Sync to MCB" button
    ↓
AJAX: edubot_mcb_sync_enquiry
    ↓
Verify nonce & permissions
    ↓
Get enquiry from wp_edubot_enquiries
    ↓
Call EduBot_MyClassBoard_Integration::sync_enquiry_to_mcb()
    ↓
Return success/error to browser
    ↓
Display result to admin
```

### 3.3 Batch Sync Flow (Future)

```
Scheduled WP-Cron event
    ↓
Get all pending syncs from wp_edubot_mcb_sync_log
    ↓
FOR each pending sync
    ├─ Retry sync attempt
    ├─ Update log if successful
    └─ Increment retry count if failed
    ↓
Log batch operation
    ↓
Send notification if threshold exceeded
```

---

## 🔗 PART 4: DATA MAPPING REFERENCE

### 4.1 Field Mapping

| EduBot Field | MCB Field | MCB Type | Example | Required |
|-------------|-----------|----------|---------|----------|
| student_name | StudentName | string | "John Doe" | YES |
| parent_name | FatherName | string | "Jane Doe" | NO |
| email | FatherEmailID | string | "john@example.com" | YES |
| phone | FatherMobile | string | "9876543210" | YES |
| mother_name | MotherName | string | "Mary Doe" | NO |
| mother_phone | MotherMobile | string | "9876543211" | NO |
| date_of_birth | DOB | date | "01-06-2025" | NO |
| address | Address1 | string | "123 Main St" | NO |
| grade | Class | string | "Grade 5" | NO |
| grade | ClassID | int | 280 | NO |
| academic_year | AcademicYearID | int | 17 | NO |
| source | QueryContactSourceID | int | 273 | NO |
| source | LeadSource | int | 273 | NO |
| enquiry_number | Remarks | string | "ENQ:ENQ20250001" | NO |

### 4.2 Grade to Class ID Mapping

```php
'Pre Nursery'  => 787
'Nursery'      => 273
'PP1'          => 274
'PP2'          => 275
'Grade 1'      => 276
'Grade 2'      => 277
'Grade 3'      => 278
'Grade 4'      => 279
'Grade 5'      => 280
'Grade 6'      => 281
'Grade 7'      => 282
'Grade 8'      => 283
'Grade 9'      => 315
'Grade 10'     => 631
'Grade 11'     => 910
'Grade 12'     => 914
```

### 4.3 Academic Year ID Mapping

```php
'2020-21' => 11
'2021-22' => 12
'2022-23' => 13
'2023-24' => 14
'2024-25' => 15
'2025-26' => 16
'2026-27' => 17  // Default
'2027-28' => 18
```

### 4.4 Lead Source ID Mapping

```php
'chatbot'        => 273   // Chat Bot
'website'        => 231   // Website
'facebook'       => 272   // Facebook
'google_search'  => 269   // Google Search
'google_display' => 270   // Google Display
'instagram'      => 268   // Instagram
'linkedin'       => 267   // LinkedIn
'whatsapp'       => 273   // Chat Bot (use same as chatbot)
'referral'       => 92    // Friends
'email'          => 286   // Email
'walkin'         => 250   // Walk-in
'organic'        => 280   // Organic (default)
```

---

## ⚙️ PART 5: CONFIGURATION GUIDE

### 5.1 Initial Setup

**Step 1: Enable Integration**
1. Go to WordPress Admin → EduBot → MyClassBoard Settings
2. Check "Enable MCB Integration"

**Step 2: Configure Organization & Branch IDs**
1. Enter Organization ID (typically: 21)
2. Enter Branch ID (typically: 113)
3. Contact your MCB administrator for exact values

**Step 3: Configure Data Sync**
1. Check "Enable Data Sync"
2. Check "Sync New Enquiries"
3. Check "Auto Sync" for immediate sync

**Step 4: Configure Lead Sources**
1. Go to Lead Source Mapping tab
2. Map each EduBot source to MCB ID
3. Save configuration

**Step 5: Test Configuration**
1. Enable "Test Mode" (optional)
2. Submit test enquiry
3. Check Sync Logs for results
4. Disable Test Mode when satisfied

### 5.2 Advanced Configuration

**Timeout Configuration**
- Min: 10 seconds
- Default: 65 seconds
- Max: 300 seconds
- Increase if API is slow

**Retry Configuration**
- Min: 1 attempt
- Default: 3 attempts
- Max: 10 attempts
- Higher = slower sync, more reliable

**Sync Mode**
- Auto Sync: Immediate sync on enquiry creation
- Batch Sync: Sync later (requires manual trigger or cron)

---

## 📊 PART 6: MONITORING & STATISTICS

### 6.1 Dashboard Statistics

**Statistics Displayed:**
- **Total Syncs**: Total sync attempts since installation
- **Successful**: Number of successful syncs
- **Failed**: Number of failed syncs
- **Today**: Syncs performed today
- **Success Rate**: Percentage of successful syncs

### 6.2 Sync Log Information

Each log entry contains:
```
Enquiry #          : ENQ20250001
Student Name       : John Doe
Email              : john@example.com
Status             : ✅ Synced or ❌ Failed
Error Message      : (if failed)
Date/Time          : 2025-01-06 14:30:00
Actions            : 🔄 Retry (if failed)
```

### 6.3 Dashboard Widget

WordPress admin dashboard shows:
- Integration status (Active/Inactive)
- Successful syncs count
- Failed syncs count
- Success rate percentage
- Link to full dashboard

---

## 🔧 PART 7: TROUBLESHOOTING GUIDE

### Issue 1: Integration Not Working

**Symptoms:**
- No syncs appearing in logs
- Enquiries not appearing in MCB

**Solutions:**
```
1. Check if integration is enabled
   └─ Go to MyClassBoard Settings → Settings tab
   └─ Verify "Enable MCB Integration" is checked
   └─ Verify "Enable Data Sync" is checked

2. Check if sync is set to auto
   └─ Verify "Auto Sync" is checked
   └─ OR manually sync enquiry for testing

3. Check sync logs for errors
   └─ Go to Sync Logs tab
   └─ Look for error messages
   └─ Copy error text for troubleshooting
```

### Issue 2: Syncs Failing with Errors

**Common Errors:**

```
ERROR: "MCB API request failed"
CAUSE: Network connectivity issue
SOLUTION:
  1. Check internet connection
  2. Verify MCB API is accessible
  3. Try increasing timeout value
  4. Retry the sync

ERROR: "HTTP 400" or "HTTP 401"
CAUSE: Invalid credentials or malformed request
SOLUTION:
  1. Verify Organization ID is correct
  2. Verify Branch ID is correct
  3. Check if lead source IDs are valid
  4. Contact MCB support for verification

ERROR: "Student details already Exists"
CAUSE: Duplicate enquiry in MCB
SOLUTION:
  1. This is normal for duplicate submissions
  2. MCB returns success status
  3. No action needed

ERROR: "Timeout waiting for API response"
CAUSE: MCB API is slow or unresponsive
SOLUTION:
  1. Increase timeout value
  2. Check MCB API status
  3. Try again later
  4. Check internet speed
```

### Issue 3: Slow Sync Performance

**Symptoms:**
- Syncs take a long time to complete
- Users report delays after submission

**Solutions:**
```
1. Increase timeout value
   └─ Go to Settings
   └─ Increase "API Timeout" to 90-120 seconds

2. Check network connectivity
   └─ Run speed test to MCB servers
   └─ Check latency to MCB API

3. Switch to batch sync
   └─ Uncheck "Auto Sync"
   └─ Manually sync in batches later

4. Check MCB server status
   └─ Contact MCB support
   └─ Verify API performance
```

### Issue 4: No Logs Appearing

**Symptoms:**
- Sync Logs tab is empty
- No history of any syncs

**Solutions:**
```
1. Check if auto-sync is enabled
   └─ Verify "Auto Sync" is checked

2. Check if you have enquiries
   └─ Go to EduBot → Applications
   └─ Verify there are enquiries to sync

3. Try manual sync
   └─ Go to Applications
   └─ Select an enquiry
   └─ Click "Sync to MCB"
   └─ Wait and check logs

4. Check database
   └─ Verify wp_edubot_mcb_sync_log table exists
   └─ Run: SELECT COUNT(*) FROM wp_edubot_mcb_sync_log;
```

---

## 📝 PART 8: BEST PRACTICES

### 8.1 Configuration Best Practices

1. **Always Test First**
   - Enable test mode
   - Submit test enquiry
   - Verify in logs before production

2. **Verify Credentials**
   - Double-check Organization ID
   - Double-check Branch ID
   - Test with MCB administrator

3. **Monitor Sync Rate**
   - Check success rate regularly
   - Address failures quickly
   - Aim for 95%+ success rate

4. **Regular Backups**
   - Backup MCB settings
   - Export sync logs periodically
   - Keep records for audit

### 8.2 Operational Best Practices

1. **Monitor Dashboard**
   - Check dashboard widget daily
   - Review failed syncs immediately
   - Retry failures promptly

2. **Review Logs**
   - Check sync logs weekly
   - Look for patterns in failures
   - Address recurring errors

3. **Test Mappings**
   - Test each lead source mapping
   - Verify class ID mappings
   - Update mappings if school changes

4. **Security**
   - Keep API keys secure
   - Don't share credentials
   - Use different keys per environment

---

## 📋 PART 9: QUICK REFERENCE

### File Structure
```
includes/
├── class-myclassboard-integration.php        # Core integration class
├── admin/
│   ├── class-mcb-settings-page.php           # Admin settings interface
│   └── class-mcb-sync-dashboard.php          # Real-time dashboard
└── integrations/
    └── class-mcb-integration-setup.php       # Setup & initialization
```

### Database Tables
```
wp_edubot_mcb_sync_log          # Sync history and logs
wp_edubot_mcb_settings          # Configuration storage
```

### Database Fields (New)
```
wp_edubot_enquiries.mcb_sync_status      # Sync status
wp_edubot_enquiries.mcb_enquiry_id       # MCB enquiry ID
wp_edubot_enquiries.mcb_query_code       # MCB query code
```

### Settings Key
```
Option Name: edubot_mcb_settings
Type: Array
Storage: wp_options table
```

---

## 🎯 PART 10: IMPLEMENTATION CHECKLIST

### Pre-Deployment
- [ ] Verify all files created successfully
- [ ] Test database table creation
- [ ] Verify admin menu appears
- [ ] Test settings page loads
- [ ] Test dashboard loads

### Configuration
- [ ] Set Organization ID
- [ ] Set Branch ID
- [ ] Configure lead source mapping
- [ ] Enable integration
- [ ] Enable sync
- [ ] Enable auto-sync (optional)

### Testing
- [ ] Submit test enquiry
- [ ] Verify in sync logs
- [ ] Check for MCB success indicator
- [ ] Verify statistics update
- [ ] Test manual sync
- [ ] Test retry functionality

### Monitoring
- [ ] Check dashboard daily
- [ ] Monitor sync logs weekly
- [ ] Track success rate
- [ ] Address failures promptly

### Documentation
- [ ] Save integration settings
- [ ] Document Organization ID
- [ ] Document Branch ID
- [ ] Document lead source mapping
- [ ] Share with team members

---

## 📞 SUPPORT & RESOURCES

### Getting Help

1. **Check Sync Logs**
   - Go to EduBot → MyClassBoard Settings → Sync Logs
   - Look for error messages
   - Copy error text

2. **Review Documentation**
   - Refer to MyClassBoard API docs
   - Check EduBot Pro documentation
   - Review this guide

3. **Contact Support**
   - EduBot Pro support team
   - MyClassBoard support team
   - Your system administrator

### Useful Links

- MyClassBoard API Documentation: https://myclassboard.com/api/
- MyClassBoard Support: https://myclassboard.com/support/
- EduBot Pro Documentation: [Your docs URL]
- WordPress Admin: [Your admin URL]/wp-admin/

---

## 📊 VERSION HISTORY

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-01-06 | Initial release |

---

## 📄 APPENDIX

### A. Sample JSON Request

```json
{
    "OrganisationID": "21",
    "BranchID": "113",
    "AcademicYearID": 17,
    "StudentName": "John Doe",
    "FatherName": "Jane Doe",
    "FatherMobile": "9876543210",
    "FatherEmailID": "john@example.com",
    "MotherName": "Mary Doe",
    "MotherMobile": "9876543211",
    "DOB": "01-06-2025",
    "Address1": "123 Main Street, City",
    "QueryContactSourceID": 273,
    "Class": "Grade 5",
    "ClassID": 280,
    "LeadSource": 273,
    "Remarks": "ENQ:ENQ20250001"
}
```

### B. Sample API Response

```json
{
    "Status": "Success",
    "EnquiryID": "12345",
    "QueryCode": "QRY20250001",
    "Message": "Thank You for showing your interest. EnquiryCode is QRY20250001."
}
```

---

**Document Version:** 1.0.0  
**Last Updated:** 2025-01-06  
**Maintained By:** EduBot Pro Integration Team
