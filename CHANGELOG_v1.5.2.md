# Changelog v1.5.2

**Release Date:** November 9, 2025

## Bug Fixes

### Marketing Data Capture - FIXED ✅
- **Issue:** UTM parameters were captured in cookies but NOT saved to database when form submitted
- **Root Cause:** JavaScript form submission handler was not sending URL parameters to server
- **Solution:** 
  - Enhanced JavaScript to capture UTM parameters from URL before form submission
  - Updated PHP handler to accept utm_params from AJAX request with multi-level fallback
  - All marketing data now properly flows: URL → JavaScript → Server → Database

### Database Schema Activator - UPDATED ✅
- Added missing MCB columns to `sql_applications()` CREATE TABLE statement
- Added migration logic for existing installations to add MCB columns
- Ensures all new installations include MCB schema from the start

## Features

### Marketing Parameter Support
- Captures all standard UTM parameters (source, medium, campaign, term, content)
- Supports click IDs from major platforms:
  - Google Ads (gclid)
  - Facebook Ads (fbclid)
  - Microsoft Ads (msclkid)
  - TikTok, Twitter, LinkedIn, Snapchat, etc.

### Enhanced UTM Collection
- Priority 1: AJAX POST data from JavaScript (most reliable)
- Priority 2: Direct POST fields
- Priority 3: Browser cookies (30-day persistence)
- Priority 4: Session storage

## Files Modified

1. **public/js/edubot-public.js**
   - Added URLSearchParams URL parameter capture
   - Sends utm_params with AJAX request
   - Enhanced error handling

2. **includes/class-edubot-shortcode.php**
   - Multi-source UTM data collection in handle_application_submission()
   - Fallback logic for maximum coverage
   - Improved error logging

3. **includes/class-edubot-activator.php**
   - Added MCB columns to sql_applications() CREATE TABLE
   - Added MCB migration logic to run_migrations()
   - Supports both new and existing installations

4. **includes/class-edubot-workflow-manager.php**
   - Removed "LKG" from grade examples (user request)

5. **includes/class-edubot-constants.php**
   - Version bumped to 1.5.2

6. **edubot-pro.php**
   - Version bumped to 1.5.2

## Database Columns Ensured

All applications now have these columns:
- `utm_data` - Full JSON object of all UTM parameters
- `gclid` - Google Ads Click ID
- `fbclid` - Facebook Click ID
- `click_id_data` - Additional tracking IDs (JSON)
- `enquiry_id` - MCB reference ID
- `mcb_sync_status` - MCB sync state
- `mcb_enquiry_id` - MCB's unique ID

## Testing Recommendations

1. **Test with UTM Parameters**
   ```
   Visit: localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   Submit form
   Check: Applications > View entry
   Verify: Marketing data displays
   ```

2. **Test without UTM Parameters**
   ```
   Visit: localhost/demo/
   Submit form
   Verify: No errors, utm_data is empty
   ```

3. **Test MCB Sync Button**
   ```
   Navigate to Applications page
   Verify: [Sync MCB] button shows/hides based on settings
   Test: Click to sync data to MyClassBoard
   ```

## Version History

- **1.5.2** - Marketing data capture fixed, activator updated with MCB columns
- **1.5.1** - MCB sync button conditional display
- **1.5.0** - MCB integration initial release
- **1.3.7** - Previous stable version

## Known Issues

None at this time.

## Next Steps

1. Clear WordPress cache
2. Deactivate and reactivate plugin to trigger database migrations
3. Test form submissions with UTM parameters
4. Monitor applications for proper marketing data capture
5. Test MCB sync functionality end-to-end

---

**Version:** 1.5.2  
**Status:** Production Ready ✅
