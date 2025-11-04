# EduBot Pro - Troubleshooting & FAQ Guide

**Version:** 1.4.1  
**Last Updated:** November 5, 2025  
**Last Tested:** November 5, 2025

---

## 🔍 Troubleshooting Guide

### Category: Installation & Activation

#### Q: Plugin not appearing in WordPress admin menu

**Symptoms:**
- EduBot menu not visible in sidebar
- No EduBot pages accessible

**Diagnosis Steps:**

```php
// 1. Check if plugin is activated
wp plugin list | grep edubot-pro

// 2. Check for PHP errors
tail -50 /wp-content/debug.log

// 3. Verify database tables were created
SELECT * FROM information_schema.tables 
WHERE table_schema = 'wordpress_db' 
AND table_name LIKE 'wp_edubot%';
```

**Solutions:**

```
Priority 1: User doesn't have admin capability
- Solution: Check user role in WordPress Users
- User must have "manage_options" capability

Priority 2: Plugin not fully activated
- Solution: Deactivate → Reactivate from Plugins page

Priority 3: Database tables not created
- Solution: Use phpMyAdmin to import schema (see API_REFERENCE.md)

Priority 4: PHP version too old
- Check: PHP must be 7.4+ (recommended 8.0+)
- Update server or disable EduBot until upgraded
```

---

#### Q: "Fatal error: Call to undefined function get_option()"

**Symptoms:**
- White screen on plugin activation
- Error in debug.log

**Cause:** WordPress not fully loaded when plugin initializes

**Solution:**

```php
// In includes/class-edubot-core.php
// Ensure main plugin class uses proper initialization hook:

// ✗ Wrong (runs too early)
EduBot_Core::init();

// ✓ Correct (runs after WordPress loads)
add_action('init', [EduBot_Core::class, 'init']);
```

---

### Category: API Configuration

#### Q: API credentials not saving

**Symptoms:**
- Form submits but fields remain empty
- No error message displayed
- Data not saving to database

**Diagnosis Steps:**

```php
// 1. Check user capability
if (!current_user_can('manage_options')) {
    die('User lacks permission');
}

// 2. Check option in database
wp option get edubot_facebook_app_id

// 3. Check for nonce errors
// Look in debug.log for "nonce" or "verification"

// 4. Check character limits
strlen($value) > 500 // Must be <= 500 characters
```

**Solutions:**

```
Priority 1: User doesn't have admin permission
- Solution: Ensure user has Admin role

Priority 2: Nonce validation failed
- Refresh page
- Try saving again
- Clear browser cache

Priority 3: Data too long (> 500 chars)
- Trim whitespace: trim($value)
- Use shorter credentials

Priority 4: Character encoding issues
- Ensure UTF-8 encoding
- Remove special characters if present

Priority 5: Database character limit
- Increase field size if custom install
- Default: 500 chars should be sufficient
```

**Manual Database Set:**

```php
// If form won't work, set directly in database:

// Via WordPress:
update_option('edubot_facebook_app_id', 'YOUR_VALUE');

// Via PHP:
$wpdb->update(
    $wpdb->options,
    ['option_value' => 'YOUR_VALUE'],
    ['option_name' => 'edubot_facebook_app_id']
);

// Verify:
echo get_option('edubot_facebook_app_id');
```

---

#### Q: "Test Connection" button shows error

**Symptoms:**
- Red "Connection Failed" message
- No error details shown

**Common Causes:**

```
1. API credentials incomplete
   - Check all 3 fields are filled
   - No typos or extra spaces

2. API credentials invalid
   - Credentials expired
   - Wrong App ID or Secret
   - Token revoked

3. Network blocked
   - Firewall blocks outgoing HTTPS
   - Proxy not configured
   - ISP blocks API domains

4. API rate limits
   - Too many test requests
   - Wait 1 hour before retrying

5. API service down
   - Facebook/Google/TikTok/LinkedIn status page
   - Check their status pages
```

**Diagnosis:**

```php
// Enable debug mode to see actual error:
define('EDUBOT_DEBUG', true);
update_option('edubot_debug_api_calls', true);

// Check error logs:
tail -100 /wp-content/debug.log | grep "API\|Connection"

// Manual test in code:
$api = EduBot_Conversion_API_Manager::get_instance();
try {
    $result = $api->test_facebook_connection();
    var_dump($result);
} catch (Exception $e) {
    echo $e->getMessage();
}
```

**Solution:**

```
1. Verify credentials from platform:
   - Facebook: developers.facebook.com
   - Google: console.cloud.google.com
   - TikTok: business.tiktok.com
   - LinkedIn: linkedin.com/developers

2. Regenerate tokens if expired:
   - Log into platform
   - Create new access token
   - Update in EduBot

3. Check network connectivity:
   - Test from server: curl https://api.example.com
   - Check firewall rules
   - Configure proxy if needed

4. Retry test after waiting:
   - Wait 15+ minutes
   - Retry test connection
```

---

### Category: Tracking & Conversions

#### Q: No conversions appearing in dashboard

**Symptoms:**
- Dashboard shows 0 conversions
- No data even after submitting forms

**Diagnosis Steps:**

```php
// 1. Check if tracking is enabled
echo get_option('edubot_enable_conversion_tracking'); // Should be 1 or true

// 2. Check if data exists in database
$wpdb->get_results("SELECT * FROM wp_edubot_conversions LIMIT 10");

// 3. Check if sessions created
$wpdb->get_results("SELECT * FROM wp_edubot_attribution_sessions LIMIT 10");

// 4. Check logs for errors
$wpdb->get_results("
    SELECT * FROM wp_edubot_logs 
    WHERE level = 'error' 
    ORDER BY created_at DESC LIMIT 10
");
```

**Solutions:**

```
Priority 1: Tracking not enabled
- Go to EduBot → Settings
- Check "Enable Conversion Tracking"
- Save settings

Priority 2: No forms configured
- EduBot doesn't auto-track all forms
- Requires integration with Contact Form 7, Gravity Forms, etc.
- Or manual tracking via code

Priority 3: Form not being submitted
- Test form manually
- Check form plugin is active
- Verify form permissions

Priority 4: UTM parameters not set
- Check URLs include utm_source, utm_campaign
- Without UTM, some attribution models won't work

Priority 5: Data not syncing to APIs
- Check if "Send to Ad Platforms" is enabled
- Verify API credentials are valid
- Check API connections
```

**Manual Tracking Test:**

```php
// In your theme or plugin, trigger a conversion:
$tracker = EduBot_Attribution_Tracker::get_instance();
$result = $tracker->track_conversion(
    'test@example.com',
    'test_conversion',
    'completed',
    ['source' => 'manual_test']
);
var_dump($result); // Should return conversion ID (int)

// Check database:
wp db query "SELECT * FROM wp_edubot_conversions WHERE user_email = 'test@example.com'"
```

---

#### Q: Duplicate conversions for same email

**Symptoms:**
- Same email appears multiple times for single action
- Conversion count seems inflated

**Cause:** Multiple tracking hooks firing for same event

**Solution:**

```php
// 1. Identify duplicate source
// Check debug log for multiple "track_conversion" calls
update_option('edubot_debug_queries', true);

// 2. Add deduplication in tracking code
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM wp_edubot_conversions 
     WHERE user_email = %s 
     AND conversion_type = %s 
     AND DATE(created_at) = %s",
    $email,
    $type,
    date('Y-m-d')
));

if ($existing) {
    return $existing; // Return existing conversion ID
}

// 3. Use hooks to prevent duplicate tracking
// Ensure only one hook fires per form submission
```

---

### Category: Email Reports

#### Q: Reports not being sent automatically

**Symptoms:**
- Scheduled reports enabled but not sending
- Manual report generation works

**Cause:** WP-Cron not running

**Diagnosis:**

```php
// Check if WP-Cron is disabled
if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
    echo "WP-Cron is DISABLED";
}

// Check if schedules are registered
wp cron test

// See next scheduled report
wp cron event list | grep edubot

// Check cron execution history
SELECT COUNT(*) FROM wp_edubot_report_schedules 
WHERE sent_at IS NOT NULL;
```

**Solutions:**

```
Priority 1: WP-Cron disabled
In wp-config.php, change:
define('DISABLE_WP_CRON', false); // Must be false

Priority 2: System cron not configured
Add to server crontab (every 15 minutes):
*/15 * * * * curl https://yourdomain.com/wp-cron.php?doing_wp_cron > /dev/null

Priority 3: Reports disabled
Go to EduBot → Performance Reports
Check "Enable Daily/Weekly/Monthly Reports"

Priority 4: No recipients configured
Add email recipients in Performance Reports page

Priority 5: Reports scheduled but condition not met
Daily reports run at 09:00 AM
Check server timezone is correct:
wp eval 'echo current_time("mysql")'
```

**Manual Report Trigger:**

```bash
# Force immediate execution via CLI:
wp cron event run edubot_daily_report --force

# Or via URL:
curl "https://yourdomain.com/wp-cron.php?doing_wp_cron"
```

---

#### Q: Emails going to spam folder

**Symptoms:**
- Report emails not in inbox
- Appear in spam/junk folder

**Cause:** Email server or client filtering

**Solutions:**

```
Priority 1: Add sender to contacts
- Ask recipient to mark as "Not Spam"
- Add sender email to contacts

Priority 2: Configure SPF, DKIM, DMARC
- SPF: v=spf1 include:sendgrid.net ~all
- DKIM: Add key in mail provider
- DMARC: v=DMARC1; p=none

Priority 3: Use reputable mail service
- WordPress wp_mail() uses server SMTP
- For better delivery, use SendGrid, Mailgun, etc.
- Add plugin: "Easy WP SMTP" or "Mailgun for WordPress"

Priority 4: Check email content
- Remove too many links
- Avoid ALL CAPS
- Use professional templates

Priority 5: Domain reputation
- Check at SendersScore.org
- Monitor bounce rates
- Remove invalid emails from list
```

**Test Email Delivery:**

```php
// Send test email
$to = 'test@example.com';
$subject = 'EduBot Test Email';
$message = 'This is a test email from EduBot Pro';
$headers = 'From: admin@yourdomain.com';

$result = wp_mail($to, $subject, $message, $headers);
if ($result) {
    echo 'Email queued for delivery';
} else {
    echo 'Email delivery failed';
}
```

---

### Category: Performance Issues

#### Q: Dashboard loads very slowly

**Symptoms:**
- EduBot admin pages take 5+ seconds to load
- High server CPU usage

**Diagnosis:**

```php
// Enable query logging
add_filter('query', function($query) {
    error_log($query);
    return $query;
});

// Check number of queries
global $wpdb;
echo "Queries: " . count($wpdb->queries);

// Check slow queries
wp db query "SHOW PROCESSLIST"
```

**Solutions:**

```
Priority 1: Enable caching
update_option('edubot_enable_caching', true);
update_option('edubot_cache_duration', 300); // 5 minutes

Priority 2: Too much data in tables
- Archive old conversions to separate database
- Run monthly: OPTIMIZE TABLE wp_edubot_conversions;
- Delete logs older than 90 days

Priority 3: Missing database indexes
- Check if all indexes exist (see API_REFERENCE.md)
- Create missing indexes manually via phpMyAdmin

Priority 4: Large period queried
- Reduce dashboard period (week instead of year)
- Use filters to narrow date range

Priority 5: API platforms slow to respond
- Disable "Send to Ad Platforms" temporarily
- Check ad platform status pages
- Switch to async API calls if available
```

---

#### Q: High database usage / disk space issues

**Symptoms:**
- Database growing rapidly
- Disk space alerts
- Database queries slow

**Cause:** Too many logs and old data

**Solution:**

```php
// Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
FROM information_schema.TABLES 
WHERE table_schema = 'wordpress_db' 
AND table_name LIKE 'wp_edubot%'
ORDER BY data_length DESC;

// Clean up logs (older than 90 days)
DELETE FROM wp_edubot_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

// Archive old conversions
// (Transfer to archive table before deleting)

// Optimize tables
OPTIMIZE TABLE wp_edubot_logs;
OPTIMIZE TABLE wp_edubot_conversions;
OPTIMIZE TABLE wp_edubot_attribution_sessions;
```

**Automated Cleanup:**

```php
// Add to wp-config.php to auto-cleanup:
add_action('wp', function() {
    if (rand(1, 100) === 1) { // Once per 100 requests
        $logger = EduBot_Logger::get_instance();
        $logger->cleanup_old_logs(90); // Keep 90 days
    }
});
```

---

### Category: Security Issues

#### Q: "Nonce verification failed" when saving settings

**Symptoms:**
- Error message when saving API credentials
- Form not submitting

**Cause:** Security token expired

**Solutions:**

```
Priority 1: Page session expired
- Solution: Refresh page and try again

Priority 2: Multiple tabs open
- Solution: Close other EduBot tabs
- Try saving in single tab only

Priority 3: Nonce field missing
- Verify form has: wp_nonce_field('edubot_settings', 'edubot_nonce')
- Check nonce action matches

Priority 4: Timestamp misalignment
- Check server time: date -u
- Check WordPress time: wp eval 'echo current_time("mysql")'
- Ensure times match
```

---

#### Q: "Sorry, you do not have permission to access this page"

**Symptoms:**
- See error when accessing EduBot pages
- Not a 404 error

**Cause:** User role insufficient

**Solution:**

```php
// Check current user role
wp user get-global-groups

// Give user admin capability
wp user set-role admin_user administrator

// Or add specific capability
$user = wp_get_current_user();
$user->add_cap('manage_options');

// Verify capability
if (current_user_can('manage_options')) {
    echo 'User has access';
}
```

---

### Category: Data Issues

#### Q: Attribution models showing incorrect percentages

**Symptoms:**
- Credit not adding up to 100%
- Some channels missing

**Cause:** Incomplete session data or filter issues

**Diagnosis:**

```php
// Check sessions for user
$sessions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM wp_edubot_attribution_sessions 
     WHERE user_email = %s 
     ORDER BY created_at ASC",
    $email
));

// Manually calculate attribution
$touchpoints = wp_list_pluck($sessions, 'channel');
$linear_result = EduBot_Attribution_Models::calculate_linear($touchpoints);
print_r($linear_result);
```

**Solutions:**

```
1. Verify attribution model setting:
   echo get_option('edubot_attribution_model');

2. Check sessions exist:
   SELECT COUNT(*) FROM wp_edubot_attribution_sessions;

3. Validate session data:
   SELECT * FROM wp_edubot_attribution_sessions LIMIT 5;

4. Test attribution calculation:
   $models = new EduBot_Attribution_Models();
   $result = $models->calculate_linear($touchpoints);
   print_r($result);
```

---

#### Q: "Session not found" errors in logs

**Symptoms:**
- Error logs contain "Session ID not found"
- Conversions not linking to sessions

**Cause:** Session expired before conversion

**Solution:**

```php
// Increase session TTL (Time To Live)
update_option('edubot_session_ttl', 3600); // 1 hour (default 30 min)

// Track sessions more aggressively
update_option('edubot_track_utm_parameters', true);
update_option('edubot_track_referrer', true);

// Check session timestamps
SELECT user_email, 
       first_touch_time, 
       last_touch_time,
       TIMESTAMPDIFF(SECOND, first_touch_time, last_touch_time) as session_duration
FROM wp_edubot_attribution_sessions
LIMIT 10;
```

---

## ❓ Frequently Asked Questions

### General Questions

**Q: Can EduBot track conversions from other websites?**

A: No, EduBot only tracks on your WordPress site. For multi-site tracking, you need:
- Facebook Pixel on all sites
- Google Analytics cross-domain tracking
- Manual API calls to EduBot from external sources

---

**Q: Does EduBot work with WooCommerce?**

A: Yes! EduBot can track:
- Product purchases as conversions
- Cart abandonment
- Order status changes

Enable in Settings and select "WooCommerce Order" as conversion type.

---

**Q: How long does data retention take?**

A: By default:
- Logs: 90 days
- Sessions: 180 days
- Conversions: 365 days (1 year)
- Reports: 365 days (1 year)

Configure retention in Settings or via:
```php
update_option('edubot_log_retention_days', 90);
```

---

**Q: Can I export conversion data?**

A: Yes, multiple ways:
1. Dashboard → Export as CSV
2. Reports → Email in HTML format
3. Direct database query:
   ```sql
   SELECT * FROM wp_edubot_conversions INTO OUTFILE 'export.csv';
   ```

---

### Technical Questions

**Q: What's the maximum number of API credentials I can store?**

A: Current: 12 credentials (3 each for 4 platforms)

Future versions may support custom platforms. Contact support for enterprise needs.

---

**Q: Can I customize the attribution models?**

A: Yes! Create custom model via filter:

```php
add_filter('edubot_custom_attribution', function($model, $touchpoints) {
    if ($model === 'custom_model') {
        // Your calculation
        return $calculated_credits;
    }
    return $model;
}, 10, 2);
```

---

**Q: Does EduBot store passwords or personal data?**

A: No. EduBot:
- Never stores user passwords
- Hashes PII (email, phone) before sending to APIs
- Doesn't store sensitive form data
- Complies with GDPR, CCPA, PIPEDA

---

**Q: How do I migrate data to another WordPress installation?**

A: Steps:
1. Export database tables (all wp_edubot_* tables)
2. Export WordPress options (all edubot_* options)
3. Import to new installation
4. Update API credentials (should import fine)
5. Verify dashboard on new site

---

### Billing & Licensing Questions

**Q: What's the pricing model?**

A: Contact sales@example.com for current pricing

---

**Q: Does EduBot require third-party payments?**

A: API platforms (Facebook, Google, TikTok, LinkedIn) may charge for their APIs
EduBot itself does not add additional fees

---

**Q: Can I use EduBot on multiple sites?**

A: Depends on license:
- Single-site license: 1 domain only
- Multi-site license: Unlimited domains
- Contact sales for licensing details

---

### Support & Resources

**Q: Where can I get support?**

A: Multiple channels:
1. Check this troubleshooting guide
2. Email: support@example.com
3. Community forums: forum.example.com
4. Priority support (for paid plans)

---

**Q: How often is EduBot updated?**

A: Monthly security updates
Quarterly feature releases
Check Plugins → EduBot Pro → View Details

---

**Q: Can I contribute to EduBot?**

A: Yes! EduBot is open-source
GitHub: https://github.com/example/edubot-pro
Submit issues, fork, create pull requests

---

## 🔧 Advanced Troubleshooting

### Enable Comprehensive Logging

```php
// In wp-config.php:
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('EDUBOT_DEBUG', true);

// Check logs:
tail -200 /wp-content/debug.log
```

### Database Query Analysis

```bash
# Enable slow query log
mysql -u root -p -e "SET GLOBAL slow_query_log = 'ON';"
mysql -u root -p -e "SET GLOBAL long_query_time = 1;"

# Check logs
tail -50 /var/log/mysql/slow-query.log
```

### Memory & CPU Analysis

```bash
# Check PHP memory limit
wp config get memory_limit

# Increase if needed
wp config set WP_MEMORY_LIMIT 256M

# Monitor CPU usage
top -p $(pgrep -f php)
```

---

## Related Resources

- [Setup Guide](./SETUP_GUIDE.md)
- [API Reference](./API_REFERENCE.md)
- [Configuration Guide](./CONFIGURATION_GUIDE.md)

