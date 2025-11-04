# EduBot Pro - Configuration Reference Guide

**Version:** 1.4.1  
**Last Updated:** November 5, 2025

---

## 📋 WordPress Options Reference

All EduBot configuration is stored in WordPress options. Modify via:
1. WordPress admin UI (EduBot → Settings)
2. Programmatically via `update_option()`
3. Database directly (phpMyAdmin)

---

## API Configuration Options

### Facebook Conversion API

**Option Keys:**

| Key | Type | Max Length | Default | Required |
|-----|------|-----------|---------|----------|
| `edubot_facebook_app_id` | string | 500 | empty | Yes |
| `edubot_facebook_app_secret` | string | 500 | empty | Yes |
| `edubot_facebook_access_token` | string | 500 | empty | Yes |

**Set Programmatically:**

```php
update_option('edubot_facebook_app_id', 'YOUR_APP_ID');
update_option('edubot_facebook_app_secret', 'YOUR_APP_SECRET');
update_option('edubot_facebook_access_token', 'YOUR_ACCESS_TOKEN');

// Verify set correctly
$app_id = get_option('edubot_facebook_app_id');
```

**Firebase Validation:**

```php
// Check if configured
if (get_option('edubot_facebook_app_id') && 
    get_option('edubot_facebook_app_secret') && 
    get_option('edubot_facebook_access_token')) {
    echo 'Facebook is configured';
}
```

---

### Google Ads API

**Option Keys:**

| Key | Type | Max Length | Default | Required |
|-----|------|-----------|---------|----------|
| `edubot_google_client_id` | string | 500 | empty | Yes |
| `edubot_google_client_secret` | string | 500 | empty | Yes |
| `edubot_google_refresh_token` | string | 500 | empty | Yes |

**Set Programmatically:**

```php
update_option('edubot_google_client_id', 'YOUR_CLIENT_ID');
update_option('edubot_google_client_secret', 'YOUR_CLIENT_SECRET');
update_option('edubot_google_refresh_token', 'YOUR_REFRESH_TOKEN');
```

---

### TikTok Conversions API

**Option Keys:**

| Key | Type | Max Length | Default | Required |
|-----|------|-----------|---------|----------|
| `edubot_tiktok_app_id` | string | 500 | empty | Yes |
| `edubot_tiktok_app_secret` | string | 500 | empty | Yes |
| `edubot_tiktok_access_token` | string | 500 | empty | Yes |

**Set Programmatically:**

```php
update_option('edubot_tiktok_app_id', 'YOUR_APP_ID');
update_option('edubot_tiktok_app_secret', 'YOUR_APP_SECRET');
update_option('edubot_tiktok_access_token', 'YOUR_ACCESS_TOKEN');
```

---

### LinkedIn Conversion API

**Option Keys:**

| Key | Type | Max Length | Default | Required |
|-----|------|-----------|---------|----------|
| `edubot_linkedin_client_id` | string | 500 | empty | Yes |
| `edubot_linkedin_client_secret` | string | 500 | empty | Yes |
| `edubot_linkedin_access_token` | string | 500 | empty | Yes |

**Set Programmatically:**

```php
update_option('edubot_linkedin_client_id', 'YOUR_CLIENT_ID');
update_option('edubot_linkedin_client_secret', 'YOUR_CLIENT_SECRET');
update_option('edubot_linkedin_access_token', 'YOUR_ACCESS_TOKEN');
```

---

## Tracking Configuration

### Session Tracking

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_enable_session_tracking` | boolean | true | Track user sessions |
| `edubot_session_ttl` | integer | 1800 | Session duration in seconds (30 min) |
| `edubot_track_utm_parameters` | boolean | true | Capture UTM parameters |
| `edubot_track_referrer` | boolean | true | Track referring domain |

**Configure:**

```php
// Enable/disable session tracking
update_option('edubot_enable_session_tracking', true);

// Set session timeout (seconds)
update_option('edubot_session_ttl', 1800); // 30 minutes

// Enable UTM tracking
update_option('edubot_track_utm_parameters', true);

// Enable referrer tracking
update_option('edubot_track_referrer', true);
```

### Conversion Tracking

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_enable_conversion_tracking` | boolean | true | Track conversions |
| `edubot_conversion_types` | array | [...] | Types to track |
| `edubot_send_to_platforms` | boolean | true | Send to ad platforms |

**Configure:**

```php
// Enable conversion tracking
update_option('edubot_enable_conversion_tracking', true);

// Define what counts as conversion
update_option('edubot_conversion_types', [
    'form_submission',
    'enquiry',
    'purchase',
    'newsletter_signup'
]);

// Send to platforms
update_option('edubot_send_to_platforms', true);
```

---

## Attribution Configuration

### Attribution Model

**Option Keys:**

| Key | Type | Default | Values |
|-----|------|---------|--------|
| `edubot_attribution_model` | string | 'linear' | 'first_touch', 'last_touch', 'linear', 'time_decay', 'u_shaped' |

**Configure:**

```php
// Set attribution model
update_option('edubot_attribution_model', 'linear');

// Options:
// 'first_touch'  - 100% to first channel
// 'last_touch'   - 100% to last channel
// 'linear'       - Equal weight to all
// 'time_decay'   - More weight to recent (half-life: 7 days)
// 'u_shaped'     - 40% first, 40% last, 20% middle
```

### U-Shaped Custom Weights

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_u_shaped_first_weight` | float | 0.4 | First touch weight (0-1) |
| `edubot_u_shaped_last_weight` | float | 0.4 | Last touch weight (0-1) |
| `edubot_u_shaped_middle_weight` | float | 0.2 | Middle touches weight (0-1) |

**Configure:**

```php
// U-shaped custom weights (must sum to 1.0)
update_option('edubot_u_shaped_first_weight', 0.5);   // 50%
update_option('edubot_u_shaped_last_weight', 0.3);    // 30%
update_option('edubot_u_shaped_middle_weight', 0.2);  // 20%
```

### Time Decay Settings

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_time_decay_half_life` | integer | 7 | Half-life in days |

**Configure:**

```php
// Set half-life for time decay model
update_option('edubot_time_decay_half_life', 7); // days
// With half-life of 7: 7-day-old touch = 50% weight
```

---

## Report Configuration

### Email Reports

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_daily_report_enabled` | boolean | false | Enable daily reports |
| `edubot_weekly_report_enabled` | boolean | false | Enable weekly reports |
| `edubot_monthly_report_enabled` | boolean | false | Enable monthly reports |

**Configure:**

```php
// Enable daily reports
update_option('edubot_daily_report_enabled', true);

// Enable weekly reports
update_option('edubot_weekly_report_enabled', true);

// Enable monthly reports
update_option('edubot_monthly_report_enabled', true);
```

### Report Timing

**Option Keys:**

| Key | Type | Format | Default |
|-----|------|--------|---------|
| `edubot_daily_report_time` | string | 'HH:MM' | '09:00' |
| `edubot_weekly_report_time` | string | 'HH:MM' | '09:00' |
| `edubot_weekly_report_day` | string | Day name | 'monday' |
| `edubot_monthly_report_time` | string | 'HH:MM' | '09:00' |
| `edubot_monthly_report_day` | integer | 1-31 | 1 |

**Configure:**

```php
// Daily report at 9 AM
update_option('edubot_daily_report_time', '09:00');

// Weekly report Monday at 9 AM
update_option('edubot_weekly_report_time', '09:00');
update_option('edubot_weekly_report_day', 'monday');

// Monthly report on 1st at 9 AM
update_option('edubot_monthly_report_time', '09:00');
update_option('edubot_monthly_report_day', 1);
```

### Report Recipients

**Option Keys:**

| Key | Type | Format | Default |
|-----|------|--------|---------|
| `edubot_report_recipients` | array | email[] | [] |
| `edubot_report_recipients_daily` | array | email[] | [] |
| `edubot_report_recipients_weekly` | array | email[] | [] |
| `edubot_report_recipients_monthly` | array | email[] | [] |

**Configure:**

```php
// All recipients
update_option('edubot_report_recipients', [
    'admin@example.com',
    'manager@example.com'
]);

// Daily report recipients only
update_option('edubot_report_recipients_daily', [
    'analyst@example.com'
]);

// Weekly report recipients only
update_option('edubot_report_recipients_weekly', [
    'admin@example.com',
    'cmo@example.com'
]);

// Monthly report recipients only
update_option('edubot_report_recipients_monthly', [
    'ceo@example.com'
]);
```

### Report Content

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_report_include_kpis` | boolean | true | Include KPI summary |
| `edubot_report_include_channels` | boolean | true | Include channel breakdown |
| `edubot_report_include_campaigns` | boolean | true | Include campaign analysis |
| `edubot_report_include_trends` | boolean | true | Include trending data |
| `edubot_report_include_devices` | boolean | true | Include device breakdown |

**Configure:**

```php
// Customize report content
update_option('edubot_report_include_kpis', true);
update_option('edubot_report_include_channels', true);
update_option('edubot_report_include_campaigns', true);
update_option('edubot_report_include_trends', true);
update_option('edubot_report_include_devices', true);
```

---

## Data Retention

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_log_retention_days` | integer | 90 | Keep logs this many days |
| `edubot_session_retention_days` | integer | 180 | Keep sessions this many days |
| `edubot_conversion_retention_days` | integer | 365 | Keep conversions this many days |
| `edubot_report_retention_days` | integer | 365 | Keep reports this many days |

**Configure:**

```php
// Delete logs older than 90 days
update_option('edubot_log_retention_days', 90);

// Delete sessions older than 6 months
update_option('edubot_session_retention_days', 180);

// Keep conversions for 1 year
update_option('edubot_conversion_retention_days', 365);

// Keep reports for 1 year
update_option('edubot_report_retention_days', 365);
```

---

## Performance Configuration

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_enable_caching` | boolean | true | Enable query caching |
| `edubot_cache_duration` | integer | 300 | Cache TTL in seconds |
| `edubot_batch_processing` | boolean | true | Batch process records |
| `edubot_batch_size` | integer | 100 | Records per batch |

**Configure:**

```php
// Enable caching for KPI queries
update_option('edubot_enable_caching', true);

// Cache for 5 minutes
update_option('edubot_cache_duration', 300);

// Enable batch processing for large datasets
update_option('edubot_batch_processing', true);

// Process 100 records at a time
update_option('edubot_batch_size', 100);
```

---

## Debug & Logging

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_debug_mode` | boolean | false | Enable debug logging |
| `edubot_debug_api_calls` | boolean | false | Log API requests/responses |
| `edubot_debug_queries` | boolean | false | Log database queries |
| `edubot_log_level` | string | 'error' | Minimum log level |

**Configure:**

```php
// Enable debug mode
define('EDUBOT_DEBUG', true);
update_option('edubot_debug_mode', true);

// Log API calls
update_option('edubot_debug_api_calls', true);

// Log database queries
update_option('edubot_debug_queries', true);

// Log levels: 'debug', 'info', 'warning', 'error'
update_option('edubot_log_level', 'info');
```

---

## Dashboard Configuration

### Widget Display

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_dashboard_widget_analytics` | boolean | true | Show analytics widget |
| `edubot_dashboard_widget_conversions` | boolean | true | Show conversions widget |
| `edubot_dashboard_widget_channels` | boolean | true | Show channels widget |

**Configure:**

```php
// Hide/show widgets on dashboard
update_option('edubot_dashboard_widget_analytics', true);
update_option('edubot_dashboard_widget_conversions', true);
update_option('edubot_dashboard_widget_channels', true);
```

### Widget Refresh

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_widget_refresh_interval` | integer | 300 | Refresh every N seconds |

**Configure:**

```php
// Refresh widgets every 5 minutes
update_option('edubot_widget_refresh_interval', 300);
```

---

## PII Hashing

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_hash_algorithm` | string | 'sha256' | Algorithm for PII hashing |
| `edubot_hash_salt` | string | '' | Optional salt for hashing |

**Configure:**

```php
// Use SHA256 for PII hashing (GDPR compliant)
update_option('edubot_hash_algorithm', 'sha256');

// Add optional salt for extra security
update_option('edubot_hash_salt', 'your-secret-salt');
```

---

## API Rate Limiting

**Option Keys:**

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `edubot_rate_limit_enabled` | boolean | true | Enable API rate limiting |
| `edubot_rate_limit_requests` | integer | 1000 | Requests per period |
| `edubot_rate_limit_period` | integer | 3600 | Period in seconds (1 hour) |

**Configure:**

```php
// Enable rate limiting
update_option('edubot_rate_limit_enabled', true);

// Max 1000 requests per hour
update_option('edubot_rate_limit_requests', 1000);
update_option('edubot_rate_limit_period', 3600);
```

---

## Multi-Site Configuration

For WordPress multisite, prefix options with site ID:

```php
// In multisite, options are auto-scoped per site
// Manually in database queries:
SELECT * FROM wp_2_options WHERE option_name LIKE 'edubot_%';
// (wp_2_options for site ID 2)
```

**Configure per site:**

```php
switch_to_blog(2);
update_option('edubot_attribution_model', 'linear');
restore_current_blog();
```

---

## Configuration via Hooks

### Filter to modify attribution model

```php
add_filter('edubot_attribution_model', function($model) {
    return 'u_shaped'; // Override stored setting
});
```

### Filter to modify API credentials

```php
add_filter('edubot_api_credentials', function($creds) {
    $creds['facebook']['app_id'] = getenv('FACEBOOK_APP_ID');
    return $creds;
});
```

### Filter to modify report recipients

```php
add_filter('edubot_report_recipients', function($recipients, $report_type) {
    if ($report_type === 'daily') {
        $recipients[] = 'daily@example.com';
    }
    return $recipients;
}, 10, 2);
```

---

## Configuration via wp-cli

If WP-CLI is installed:

```bash
# Get all EduBot options
wp option list --search="edubot_"

# Update option
wp option update edubot_attribution_model "linear"

# Get specific option
wp option get edubot_daily_report_enabled

# Delete option
wp option delete edubot_debug_mode
```

---

## Validation & Defaults

When setting options programmatically, values are validated:

| Option | Valid Values |
|--------|--------------|
| Attribution Model | first_touch, last_touch, linear, time_decay, u_shaped |
| Boolean Options | true, false, 1, 0, 'on', 'off' |
| Integer Options | Must be numeric ≥ 0 |
| Float Options | Must be numeric 0.0-1.0 |
| Email Arrays | Array of valid email addresses |
| Time Strings | 'HH:MM' format (00:00-23:59) |

**Validation Example:**

```php
// Invalid - will be rejected or cast
update_option('edubot_attribution_model', 'invalid_model');
// → Defaults to 'linear'

update_option('edubot_cache_duration', 'not_a_number');
// → Defaults to 300

update_option('edubot_report_recipients', 'invalid-email');
// → Error if invalid email
```

---

## Backup & Export

### Export Configuration

```bash
# Export all EduBot options to JSON
wp option list --search="edubot_" --format=json > edubot-config-backup.json
```

### Import Configuration

```bash
# Import from JSON
# (Requires custom script or manual restoration)
```

### Manual Database Backup

```sql
-- Backup EduBot options
SELECT * FROM wp_options 
WHERE option_name LIKE 'edubot_%' 
INTO OUTFILE '/tmp/edubot-options.sql';

-- Restore
LOAD DATA INFILE '/tmp/edubot-options.sql' 
INTO TABLE wp_options;
```

---

## Related Resources

- [Setup Guide](./SETUP_GUIDE.md) - Initial setup
- [API Reference](./API_REFERENCE.md) - Class documentation
- [Troubleshooting](./TROUBLESHOOTING_GUIDE.md) - Common issues

