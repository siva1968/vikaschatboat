# 🔄 MCB (MyClassBoard) Sync System - Complete Architecture

## Overview

MCB Sync is an automated synchronization system that pushes **admission enquiries** from EduBot to **MyClassBoard (MCB)**, a Student Information System (SIS) used for managing school operations.

---

## 🎯 Purpose

**Goal:** Automatically sync student enquiry data from the EduBot chatbot into MyClassBoard's CRM system

**When it triggers:** When a new admission enquiry is submitted in the chatbot

**What it syncs:** Student name, grade, board, parent contact info, academic year, and more

---

## 🗄️ Database Tables

### 1. `wp_edubot_mcb_settings` - Configuration

Stores MCB integration settings:

```sql
- id (PK)
- org_id (default: 21) - Organization ID in MyClassBoard
- branch_id (default: 113) - Branch ID in MyClassBoard  
- api_timeout (default: 65 seconds)
- retry_attempts (default: 3)
- auto_sync (default: 1/enabled) - Auto-sync on enquiry creation
- enabled (boolean) - Overall MCB integration enabled/disabled
- created_at
- updated_at
```

**Storage:** Via WordPress options table using `get_option()` / `update_option()`

### 2. `wp_edubot_mcb_sync_log` - Sync History

Tracks every MCB sync attempt:

```sql
- id (PK) - Auto-increment
- enquiry_id (FK) - Links to wp_edubot_enquiries
- request_data (JSON) - Request payload sent to MCB
- response_data (JSON) - MCB response received
- success (0/1) - Did sync succeed?
- error_message - Error details if failed
- retry_count - How many times we retried
- created_at - When sync was attempted
- updated_at - Last updated timestamp

Indexes:
- idx_enquiry - For querying by enquiry
- idx_success - For filtering successful/failed syncs
```

### 3. `wp_edubot_enquiries` - Enhanced with MCB fields

Added MCB tracking columns to main enquiries table:

```sql
- mcb_sync_status (VARCHAR) - Status: 'pending', 'synced', 'failed', 'retry'
- mcb_enquiry_id (VARCHAR) - Unique ID returned by MCB
- mcb_query_code (VARCHAR) - Query code from MCB response
```

---

## 🔀 How MCB Sync Works

### Step 1: Enquiry Submission
```
User submits admission enquiry in chatbot
↓
Data saved to wp_edubot_enquiries
↓
Check: Is MCB auto_sync enabled? (get_option('edubot_mcb_auto_sync'))
↓
YES → Proceed to Step 2
NO → Stop here, manual sync needed later
```

### Step 2: Prepare Data
```
Extract enquiry data:
- student_name (required)
- grade → Convert to MCB class_id
- board → Convert to MCB board code
- parent name/email/phone
- academic year

Map to MCB format:
┌─────────────────────────────┐
│ EduBot Field                │ MCB Field
├─────────────────────────────┤
│ student_name                │ StudentName
│ grade (e.g., "Grade 5")    │ ClassID (mapped)
│ board (e.g., "CBSE")       │ BoardID (mapped)
│ parent_name                 │ ParentName
│ phone                       │ ParentMobileNo
│ email                       │ ParentEmailID
│ academic_year               │ AcademicYear
│ enquiry_number              │ EnquiryID (reference)
└─────────────────────────────┘
```

### Step 3: Call MCB API
```
Send to MCB endpoint:
URL: https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails

Request Parameters:
{
  "OrgID": 21,              // From get_option('edubot_mcb_org_id')
  "BranchID": 113,          // From get_option('edubot_mcb_branch_id')
  "StudentName": "Prasad",
  "ClassID": 5,             // Mapped from grade
  "BoardID": 1,             // Mapped from board
  "ParentName": "Mr. Prasad",
  "ParentMobileNo": "+919866133566",
  "ParentEmailID": "smasina@gmail.com",
  "AcademicYear": "2026-27",
  "EnquiryID": "ENQ-2025-001234"
}

Timeout: 65 seconds (configurable)
```

### Step 4: Handle Response
```
MCB API responds with:

SUCCESS:
{
  "Result": "Success",
  "EnquiryID": "ENQ-2025-001234",
  "QueryCode": "MCB-98765432",  ← Unique identifier in MCB
  "Message": "Enquiry saved successfully"
}
↓
Update enquiries record:
- mcb_sync_status = 'synced'
- mcb_enquiry_id = QueryCode
- Log to wp_edubot_mcb_sync_log with success=1

ERROR:
{
  "Result": "Error",
  "Message": "Invalid ClassID for this branch"
}
↓
Update enquiries record:
- mcb_sync_status = 'failed'
- Log error to wp_edubot_mcb_sync_log with success=0
- error_message = "Invalid ClassID for this branch"
- retry_count = 1

RETRY LOGIC (up to 3 attempts):
If failed, mark for retry
- After 5 mins: Retry attempt 2
- After 15 mins: Retry attempt 3
- After 60 mins: Mark permanently failed
```

### Step 5: Log Everything
```
Create record in wp_edubot_mcb_sync_log:

{
  "enquiry_id": 42,
  "request_data": JSON of what we sent,
  "response_data": JSON of what MCB returned,
  "success": 1 or 0,
  "error_message": "null or error text",
  "retry_count": 0 or 1 or 2,
  "created_at": "2025-11-09 15:30:45",
  "updated_at": "2025-11-09 15:30:45"
}
```

---

## ⚙️ Configuration (Admin Settings)

### In WordPress Admin → EduBot Settings → API Settings → MyClassBoard Tab

```
☑ Enable MyClassBoard Integration
  ├─ Organization ID: 21
  ├─ Branch ID: 113
  ├─ API Timeout: 65 seconds
  ├─ Retry Attempts: 3
  └─ ☑ Auto-sync enquiries (auto-enable)

Test Connection → Tests API connectivity
```

**Options stored in wp_options:**
- `edubot_mcb_enabled` (1 = enabled, 0 = disabled)
- `edubot_mcb_org_id` (default: 21)
- `edubot_mcb_branch_id` (default: 113)
- `edubot_mcb_api_timeout` (default: 65)
- `edubot_mcb_retry_attempts` (default: 3)
- `edubot_mcb_auto_sync` (default: 1 = auto-sync enabled)

---

## 📊 Data Flow Diagram

```
┌──────────────────────────┐
│  User Submits Enquiry    │
│  (Chatbot Form)          │
└────────────┬─────────────┘
             │
             ↓
      ┌──────────────────┐
      │ Save to EduBot   │
      │ (wp_edubot_enq   │
      │  quiries)        │
      └──────┬───────────┘
             │
             ↓
    ┌─────────────────────┐
    │ Check MCB Enabled?  │
    │ get_option(         │
    │ 'edubot_mcb_enabled│
    │ ')                  │
    └────┬────────┬──────┘
         │        │
        YES      NO → STOP
         │
         ↓
    ┌──────────────────────┐
    │ Check Auto-sync?     │
    │ get_option(          │
    │ 'edubot_mcb_auto_sync│
    │ ')                   │
    └────┬────────┬───────┘
         │        │
        YES      NO → Mark pending
         │
         ↓
    ┌─────────────────────┐
    │ Map Data to MCB     │
    │ Format             │
    └──────┬──────────────┘
           │
           ↓
    ┌──────────────────────────┐
    │ POST to MCB API           │
    │ /EnquiryService/          │
    │ SaveEnquiryDetails        │
    └────┬────────┬─────────────┘
         │        │
      SUCCESS   FAILURE
         │        │
         ↓        ↓
    ┌────────┐ ┌──────────────┐
    │ Synced │ │ Retry Logic  │
    │ Status │ │ (up to 3x)   │
    └────────┘ └──────┬───────┘
                      │
                      ↓
                 ┌─────────────┐
                 │ Log Record  │
                 │ (success=0) │
                 └─────────────┘
         │
         ↓
    ┌─────────────────┐
    │ Log to Sync Log │
    │ (wp_edubot_mcb_ │
    │ sync_log)       │
    └─────────────────┘
```

---

## 🔄 Sync Status States

| Status | Meaning | Action |
|--------|---------|--------|
| `pending` | Not yet synced to MCB | Will retry automatically |
| `synced` | Successfully sent to MCB | Complete, `mcb_enquiry_id` set |
| `failed` | Sync failed, retries exhausted | Manual intervention needed |
| `retry` | Waiting for retry attempt | Will retry in next cycle |

---

## 📝 Example Sync Log Entry

```json
{
  "id": 15,
  "enquiry_id": 42,
  "request_data": {
    "OrgID": "21",
    "BranchID": "113",
    "StudentName": "Prasad",
    "ClassID": "5",
    "BoardID": "1",
    "ParentName": "Mr. Prasad Sinha",
    "ParentMobileNo": "+919866133566",
    "ParentEmailID": "smasina@gmail.com",
    "AcademicYear": "2026-27",
    "EnquiryID": "ENQ-2025-001234"
  },
  "response_data": {
    "Result": "Success",
    "EnquiryID": "ENQ-2025-001234",
    "QueryCode": "MCB-98765432",
    "Message": "Enquiry saved successfully"
  },
  "success": 1,
  "error_message": null,
  "retry_count": 0,
  "created_at": "2025-11-09 15:30:45",
  "updated_at": "2025-11-09 15:30:45"
}
```

---

## 🛠️ Manual Sync

If an enquiry fails to sync or was created before MCB was enabled:

```php
// In WordPress admin or via plugin:
$mcb_service = new EduBot_MCB_Service();
$mcb_service->sync_enquiry($enquiry_id);

// Or sync all pending:
$mcb_service->sync_pending_enquiries();
```

---

## ⚡ Troubleshooting

### Sync Not Working?

1. **Check if MCB is enabled:**
   ```
   WordPress Admin → EduBot → API Settings → MyClassBoard
   Verify "Enable MyClassBoard Integration" is checked
   ```

2. **Check database records:**
   ```sql
   -- See sync logs
   SELECT * FROM wp_edubot_mcb_sync_log 
   ORDER BY created_at DESC LIMIT 10;
   
   -- Check enquiry MCB status
   SELECT enquiry_number, mcb_sync_status, mcb_enquiry_id 
   FROM wp_edubot_enquiries 
   WHERE id = 42;
   ```

3. **Check error message:**
   ```sql
   SELECT error_message 
   FROM wp_edubot_mcb_sync_log 
   WHERE success = 0 
   ORDER BY created_at DESC LIMIT 1;
   ```

4. **Common Issues:**
   - ❌ `Invalid ClassID` → Grade not mapped correctly
   - ❌ `Invalid BoardID` → Board not in MCB system
   - ❌ `Timeout` → MCB API unreachable (check network)
   - ❌ `Invalid OrgID/BranchID` → Wrong org/branch configuration

---

## 📊 Monitoring

### View Sync Statistics

```php
// Get sync success rate
$total = $wpdb->get_var(
  "SELECT COUNT(*) FROM wp_edubot_mcb_sync_log"
);
$successful = $wpdb->get_var(
  "SELECT COUNT(*) FROM wp_edubot_mcb_sync_log WHERE success = 1"
);
$success_rate = ($successful / $total) * 100;

echo "MCB Sync Success Rate: {$success_rate}%";
```

### Recent Sync Activity

```php
$recent = $wpdb->get_results(
  "SELECT e.enquiry_number, e.student_name, s.success, s.error_message, s.created_at
   FROM wp_edubot_mcb_sync_log s
   JOIN wp_edubot_enquiries e ON s.enquiry_id = e.id
   ORDER BY s.created_at DESC LIMIT 20"
);
```

---

## 🔐 Security

- API credentials stored in `wp_options` table (use WordPress encryption plugins if needed)
- Request/response data logged (consider privacy regulations like GDPR)
- Timeout prevents hanging connections (65 sec default)
- Retry logic prevents overwhelming MCB API

---

## 📚 Key Classes

- **EduBot_MCB_Settings** - Configuration management
- **EduBot_MCB_Service** - Sync logic and API calls
- **EduBot_Sync_Log** - Logging and tracking
- **EduBot_Cron_Scheduler** - Retry scheduling

---

## ✅ Summary

**MCB Sync System:**
- ✅ Automatically pushes enquiries to MyClassBoard
- ✅ Tracks every sync attempt in `wp_edubot_mcb_sync_log`
- ✅ Retries failed syncs up to 3 times
- ✅ Configurable via WordPress admin
- ✅ Detailed logging for troubleshooting
- ✅ Updates enquiry records with MCB IDs upon success

