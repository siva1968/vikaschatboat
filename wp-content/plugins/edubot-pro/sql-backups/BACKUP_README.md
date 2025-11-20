# EduBot Plugin Database Backup

**Generated:** November 8, 2025  
**Database:** demo  
**Total Tables:** 15  
**Size:** 969 KB

## Tables Included

### Core Application Tables
1. **wp_edubot_enquiries** - Student admission enquiries
2. **wp_edubot_applications** - Full application submissions
3. **wp_edubot_school_configs** - School configuration settings

### API & Integration Tables
4. **wp_edubot_api_integrations** - WhatsApp, Email, and Third-party API configs
5. **wp_edubot_api_logs** - API call logs and responses

### Notification & Communication
6. **wp_edubot_logs** - System logs
7. **wp_edubot_mcb_settings** - MyClassBoard integration settings
8. **wp_edubot_mcb_sync_log** - MyClassBoard sync logs

### Analytics & Attribution
9. **wp_edubot_attribution_journeys** - Lead journey tracking
10. **wp_edubot_attribution_sessions** - Session attribution data
11. **wp_edubot_attribution_touchpoints** - Customer touchpoint tracking
12. **wp_edubot_conversions** - Conversion records
13. **wp_edubot_visitor_analytics** - Visitor analytics data

### Visitor & Report Tables
14. **wp_edubot_visitors** - Visitor tracking
15. **wp_edubot_report_schedules** - Scheduled reports

## Backup Contents

Each table includes:
- ✅ Full schema (CREATE TABLE statement)
- ✅ All data (INSERT statements)
- ✅ Foreign key constraints
- ✅ Indexes and keys

## How to Restore

### Option 1: Using phpMyAdmin
1. Login to phpMyAdmin
2. Select database: `demo`
3. Go to SQL tab
4. Paste backup SQL script
5. Click Execute

### Option 2: Using Command Line
```bash
mysql -u root -p demo < edubot-plugin-backup-2025-11-08.sql
```

### Option 3: Using WordPress CLI
```bash
wp db import sql-backups/edubot-plugin-backup-2025-11-08.sql
```

## Data Integrity

**Backup Verification:**
- ✅ All 15 tables included
- ✅ Schema structures preserved
- ✅ Data relationships intact
- ✅ Foreign keys maintained
- ✅ Indexes included

## Critical Data Points Backed Up

### API Configurations
- WhatsApp Phone ID: 614525638411206
- Business Account ID: 849100880736420
- Email API keys and tokens
- Template configurations

### Applications Data
- Student information
- Parent contact details
- Grade and board preferences
- Admission enquiry status
- Submission timestamps

### Integration Settings
- MyClassBoard sync status
- Lead source mappings
- Custom field configurations

## Restore Checklist

Before restoring to production:
- [ ] Verify database connection working
- [ ] Ensure MySQL is running
- [ ] Backup current database first
- [ ] Check file permissions
- [ ] Verify disk space available (min 1GB)

After restoring:
- [ ] Test admin login
- [ ] Verify enquiry records visible
- [ ] Check API configurations intact
- [ ] Test email notifications
- [ ] Test WhatsApp notifications
- [ ] Verify analytics data

## File Details

**Filename:** `edubot-plugin-backup-2025-11-08.sql`  
**Format:** SQL (MySQL compatible)  
**Compression:** None  
**Encoding:** UTF-8  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci

## Location

Repository: `sql-backups/`

---

**Important:** This backup contains sensitive data including API keys, phone numbers, and personal information. Handle with care and ensure secure storage.
