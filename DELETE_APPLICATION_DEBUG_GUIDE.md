# Delete Application - Debugging & Testing Guide

## Changes Made

### 1. Fixed Database Table Reference
**File:** `admin/class-edubot-admin.php` (Line 3291-3310)
- Changed from `wp_edubot_applications` → `wp_edubot_enquiries` ✅
- Applications are stored in enquiries table, not applications table

### 2. Added Comprehensive Debug Logging  
**File:** `admin/class-edubot-admin.php` (Lines 3202-3240 & 3291-3310)
- Log AJAX handler entry
- Log nonce verification result
- Log permission check result
- Log application ID received
- Log delete operation result
- Log database errors if any

### 3. Added JavaScript Logging
**File:** `admin/views/applications-list.php` (Line 347-378)
- Log to browser console when delete is clicked
- Log AJAX URL
- Log success/error responses
- Log network errors with details

## How to Test

### Step 1: Open Browser Console
1. Go to Applications page: `http://localhost/demo/wp-admin/admin.php?page=edubot-applications`
2. Press F12 to open Developer Tools
3. Go to "Console" tab
4. Keep it open

### Step 2: Try to Delete
1. Find any application in the list
2. Click the "Delete" button
3. Click "OK" on the confirmation
4. Watch the console for messages

### Expected Console Output
```
Delete clicked for ID: 1
AJAX URL: http://localhost/demo/wp-admin/admin-ajax.php
AJAX Success: {success: true, data: {message: "Application deleted successfully"}}
```

### Step 3: Check Server Log  
1. View: `D:\xamppdev\htdocs\demo\wp-content\debug.log`
2. You should see:
   ```
   EduBot AJAX: Delete application handler called
   EduBot AJAX: Nonce received: abc123...
   EduBot AJAX: Application ID: 1
   EduBot AJAX: Attempting to delete application 1
   EduBot delete_application: Starting delete for ID 1
   EduBot delete_application: Record exists? Yes
   EduBot: Successfully deleted enquiry ID 1 from enquiries table (rows affected: 1)
   ```

### Step 4: Verify in Database
```sql
SELECT COUNT(*) FROM wp_edubot_enquiries WHERE id = 1;
-- Should return 0 (record deleted)
```

## Troubleshooting

### If Delete Button Doesn't Work:
1. **Check Console for JavaScript errors** - F12 → Console tab
2. **Check if AJAX URL is correct** - Should be `admin-ajax.php`
3. **Check if DELETE log appears** - Search debug.log for "Delete application handler"
4. **Verify nonce is being created** - Check debug.log for "Nonce received"

### If Error: "Security check failed"
- Nonce verification failed
- Check if nonce timing is correct
- Try refreshing the page to get a new nonce

### If Error: "Insufficient permissions"
- Current user doesn't have `manage_options` capability
- Make sure you're logged in as Admin

### If Error: "Invalid application ID"
- The ID wasn't passed correctly
- Check console for ID value

###  If Error: "Failed to delete application"
- Check debug.log for delete_application errors
- Verify record exists in database
- Check database permissions

## Files Deployed

✅ `admin/class-edubot-admin.php` - Fixed delete table + added logging
✅ `admin/views/applications-list.php` - Added JavaScript logging

## Next Steps After Testing

Once you test and tell me what error message you see (if any), I can:
1. Fix the specific issue
2. Add more targeted logging
3. Implement alternative delete method if needed

---

**Current Status:** Logging deployed, ready for testing
