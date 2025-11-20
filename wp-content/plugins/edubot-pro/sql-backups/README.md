# Database Backups Directory

**Last Updated:** November 8, 2025

## Files in This Directory

### 1. 📦 Complete Backup (With Data)
**File:** `edubot-plugin-backup-2025-11-08.sql`
- **Size:** 969 KB
- **Type:** Full backup (schema + data)
- **Tables:** 15
- **Use Case:** Disaster recovery, production backup, database migration
- **Sensitivity:** ⚠️ Contains sensitive data (API keys, phone numbers, personal info)

### 2. 📋 Schema Only (No Data)
**File:** `edubot-plugin-schema-2025-11-08.sql`
- **Size:** 17 KB
- **Type:** Schema only backup
- **Tables:** 15
- **Use Case:** Version control, development, database structure reference
- **Sensitivity:** ✅ Safe (no personal or sensitive data)

### 3. 🔄 Restore Script
**File:** `restore_database.php`
- **Executes:** SQL restore operations
- **Usage:** `php restore_database.php backup_filename.sql`
- **Purpose:** Easy restoration from backup
- **Requirements:** WordPress wp-load.php in same directory

### 4. 📖 Documentation
**File:** `BACKUP_README.md`
- Complete backup documentation
- Restoration instructions
- Data integrity information
- Restore checklist

---

## Quick Start

### Create New Backup
```bash
# Full backup (with data)
php backup_plugin_tables.php

# Schema only (no data)
php backup_plugin_schema.php
```

### Restore Database
```bash
php restore_database.php edubot-plugin-backup-2025-11-08.sql
```

### Using MySQL CLI
```bash
mysql -u root -p demo < edubot-plugin-backup-2025-11-08.sql
```

---

## Backup Schedule

| Frequency | Purpose | Type |
|-----------|---------|------|
| Daily | Disaster recovery | Full backup |
| Weekly | Version control | Schema only |
| Before major changes | Safety checkpoint | Full backup |
| Development | Reference | Schema only |

---

## What's Backed Up

### Tables (15 Total)

**Core Data:**
- `wp_edubot_enquiries` - Admission enquiries
- `wp_edubot_applications` - Full applications
- `wp_edubot_school_configs` - School settings

**API & Integration:**
- `wp_edubot_api_integrations` - API credentials
- `wp_edubot_api_logs` - API call logs
- `wp_edubot_mcb_settings` - MyClassBoard config
- `wp_edubot_mcb_sync_log` - Sync history

**Analytics & Tracking:**
- `wp_edubot_attribution_journeys` - Lead journeys
- `wp_edubot_attribution_sessions` - Sessions
- `wp_edubot_attribution_touchpoints` - Touchpoints
- `wp_edubot_conversions` - Conversions
- `wp_edubot_visitor_analytics` - Visitor data
- `wp_edubot_visitors` - Visitor records
- `wp_edubot_logs` - System logs
- `wp_edubot_report_schedules` - Report schedules

---

## Important Notes

### Data Sensitivity
⚠️ **Complete backups contain:**
- API keys and tokens
- WhatsApp phone IDs and business account IDs
- Email API credentials
- Student personal information
- Parent contact details

**Recommendation:** Store complete backups in secure location only. Use schema-only backups for version control.

### Storage Location
- **Local Backups:** `sql-backups/`
- **Remote Backups:** Should be encrypted and stored separately
- **Version Control:** Only schema backups (no data)

### Backup Retention Policy

| Backup Type | Retention |
|------------|-----------|
| Daily full backups | 7 days |
| Weekly schema backups | Indefinite (version control) |
| Before major changes | Until confirmed stable |
| Production snapshots | 30 days minimum |

---

## Restore Procedures

### Scenario 1: Data Corruption
```bash
php restore_database.php edubot-plugin-backup-2025-11-08.sql
```

### Scenario 2: Migration to New Server
1. Export full backup
2. Transfer to new server
3. Create empty database
4. Run restore script

### Scenario 3: Development Environment Setup
```bash
# Use schema-only to create empty structure
mysql -u root -p development < edubot-plugin-schema-2025-11-08.sql
```

---

## Verification After Restore

- [ ] All 15 tables exist
- [ ] Record counts match
- [ ] API configurations intact
- [ ] Foreign keys valid
- [ ] Indexes present
- [ ] Plugins activated
- [ ] Settings accessible

---

## Troubleshooting

**Issue:** "Table already exists" error
- **Solution:** Drop tables first or use fresh database

**Issue:** Character encoding errors
- **Solution:** Ensure MySQL has UTF-8 support enabled

**Issue:** Out of memory during restore
- **Solution:** Split large backup into smaller chunks

**Issue:** Foreign key constraint errors
- **Solution:** Restore may disable/re-enable foreign keys

---

## Backup Metadata

**Database:** demo  
**Server:** localhost (XAMPP)  
**WordPress Version:** 6.x  
**PHP Version:** 7.4+  
**MySQL Version:** 5.7+

---

## Related Files

- `backup_plugin_tables.php` - Backup creation script (in root)
- `backup_plugin_schema.php` - Schema backup script (in root)
- `wp-config.php` - Database configuration
- `wp-content/plugins/edubot-pro/` - Plugin files
