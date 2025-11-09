# ISSUES IDENTIFIED & FIXES REQUIRED

**Date**: November 6, 2025  
**Status**: Analyzing & Fixing  

---

## Issues Found

### Issue 1: Database Column Missing ❌
**Error**: Unknown column 'visitor_id' in 'field list'  
**Location**: `wp_edubot_visitors` table  
**Cause**: Database schema mismatch - column doesn't exist  
**Fix**: Add missing column to database migration

### Issue 2: Undefined Method ❌
**Error**: Call to undefined method `EduBot_Admin_Dashboard::get_kpi_summary()`  
**Location**: Line 110 in `class-dashboard-widget.php`  
**Cause**: Method called `get_kpi_summary()` but actual method is `get_kpis()`  
**Fix**: Change method name or create the missing method

### Issue 3: Undefined Variable ❌
**Error**: Undefined variable `$btn` in multiple lines  
**Location**: Lines 532, 533, 535, 556 in `class-dashboard-widget.php`  
**Cause**: PHP parsing JavaScript - variable declared in JavaScript context  
**Fix**: This is actually OK (JavaScript code), but PHP warnings need to be suppressed

---

## Critical Fixes Required

1. **Fix Method Name** (High Priority)
   - File: `includes/admin/class-dashboard-widget.php`
   - Line: 110
   - Change: `$this->dashboard->get_kpi_summary()` → `$this->dashboard->get_kpis()`

2. **Add Missing Database Column** (High Priority)
   - Table: `wp_edubot_visitors`
   - Add column: `visitor_id`

3. **Fix Undefined Variable Warnings** (Medium Priority)
   - Escape heredoc or restructure JavaScript code

---

## Fixes to Apply

### Fix 1: Change get_kpi_summary to get_kpis

Replace in `class-dashboard-widget.php` line 110:
```php
// OLD
$stats = $this->dashboard->get_kpi_summary();

// NEW  
$stats = $this->dashboard->get_kpis();
```

### Fix 2: Add visitor_id Column

Run database migration to add missing column.

### Fix 3: Fix JavaScript Variable Warnings

Wrap JavaScript in proper escaping or restructure the code.

