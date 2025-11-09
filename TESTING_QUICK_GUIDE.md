# Quick Action Guide - Test Fixed EduBot Pro

## 🔄 Step 1: Reactivate Plugin (Triggers Database Migration)

1. Open WordPress Admin: `http://localhost/demo/wp-admin`
2. Go to **Plugins** menu
3. Find **EduBot Pro** plugin
4. Click **Deactivate**
5. Wait for page to load
6. Click **Activate**
   - This runs the `activate()` function which executes `run_migrations()`
   - The migration automatically adds the `visitor_id` column if needed

## ✅ Step 2: Verify Plugin Loads Without Errors

1. Click "Dismiss" on any admin notices
2. Go to **Dashboard** (you might need to refresh)
3. Check that:
   - Dashboard loads without fatal errors
   - No white screen of death
   - No "Fatal Error" messages

## 🔍 Step 3: Check Debug Log

1. Open debug log: `D:\xamppdev\logs\` (if accessible)
2. Or check WordPress debug log at bottom of wp-content folder
3. Look for any error entries from our plugin
4. Specifically look for:
   - ❌ Class not found errors → Should NOT appear
   - ❌ Undefined method errors → Should NOT appear
   - ❌ Unknown column errors → Should NOT appear
   - ❌ Undefined variable errors → Should NOT appear

## 📊 Step 4: Verify Dashboard Widget

1. On WordPress Dashboard, look for **EduBot Pro** widget
2. Check that it displays:
   - KPI Summary section
   - Recent Analytics data
3. Click the refresh button (if available)
4. Verify no JavaScript errors in browser console

## 🌐 Step 5: Test Visitor Tracking

1. Open a new browser window
2. Navigate to a page on your site (e.g., `http://localhost/demo`)
3. Visit multiple pages
4. Go back to WordPress Admin → Check database or logs
5. Verify visitor records are being created

## 📝 Step 6: Test with UTM Parameters

1. Open a new browser window
2. Visit your site with UTM parameters:
   ```
   http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=fall_sale
   ```
3. Check database records to verify UTM data captured

## 🐛 Troubleshooting

### If Dashboard Still Won't Load:
1. Check if PHP errors appear: `php -l` on the deployed files
2. Look for parse errors in the debug log
3. Try manually running migration script:
   ```php
   // Visit in browser after logging in as admin:
   // Include from WordPress CLI or create a test page
   ```

### If Visitor_ID Column Still Missing:
1. Run manual migration: `migrations/add_visitor_id_column.php`
2. Or execute SQL manually:
   ```sql
   ALTER TABLE wp_edubot_visitors ADD COLUMN visitor_id varchar(255) UNIQUE NOT NULL AFTER id;
   ```

### If Database Errors Continue:
1. Check table structure:
   ```sql
   DESCRIBE wp_edubot_visitors;
   ```
2. Verify columns exist for all fields used in insert/update

## ✨ Success Criteria

- [x] Plugin activates without errors
- [ ] WordPress admin loads
- [ ] Dashboard displays without fatals  
- [ ] Visitor tracking works
- [ ] No error log entries
- [ ] All features functional

---

**Files to Review If Issues Occur**:
- `includes/class-edubot-activator.php` - Database schema
- `includes/class-visitor-analytics.php` - Visitor tracking code
- `includes/admin/class-dashboard-widget.php` - Dashboard widget
- `edubot-pro.php` - Main bootstrap file

**Need Help?**
Check `RUNTIME_ERRORS_FIXED_NOV_6.md` for detailed fix documentation.
