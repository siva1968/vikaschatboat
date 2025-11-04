# EduBot Pro - Complete API Reference

**Version:** 1.4.1  
**Last Updated:** November 5, 2025  
**Status:** Production Ready  
**Coverage:** 14 Core Classes | 90%+ Code Coverage

---

## 📑 Table of Contents

1. [Core Classes](#core-classes)
2. [Attribution System](#attribution-system)
3. [Analytics Dashboard](#analytics-dashboard)
4. [Reporting System](#reporting-system)
5. [Admin Interface](#admin-interface)
6. [Database Schema](#database-schema)
7. [API Integrations](#api-integrations)
8. [WordPress Hooks](#wordpress-hooks)

---

## Core Classes

### 1. EduBot_Logger

**Purpose:** Centralized logging system for all plugin events

**Namespace:** `includes/class-edubot-logger.php`

**Methods:**

```php
// Singleton
public static function get_instance(): EduBot_Logger

// Logging
public function log_info(string $message, array $context = []): int|bool
public function log_error(string $message, array $context = []): int|bool
public function log_warning(string $message, array $context = []): int|bool

// Retrieval
public function get_logs(array $filters = []): array
public function get_log_by_id(int $id): object|null

// Management
public function cleanup_old_logs(int $days = 90): int
public function clear_logs(): bool
```

**Usage Example:**

```php
$logger = EduBot_Logger::get_instance();

// Log an event
$logger->log_info('User enquiry submitted', [
    'user_email' => 'user@example.com',
    'channel' => 'facebook',
    'campaign' => 'Summer Campaign'
]);

// Retrieve logs
$logs = $logger->get_logs(['level' => 'error']);
```

**Database Table:** `wp_edubot_logs`

---

### 2. EduBot_Attribution_Tracker

**Purpose:** Multi-touch attribution tracking across channels

**Namespace:** `includes/class-attribution-tracker.php`

**Methods:**

```php
// Singleton
public static function get_instance(EduBot_Logger $logger = null): EduBot_Attribution_Tracker

// Session Tracking
public function track_user_session(
    string $email,
    string $channel,
    string $campaign,
    string $utm_source = ''
): int|bool

// Conversion Tracking
public function track_conversion(
    string $email,
    string $conversion_type,
    string $status = 'completed',
    array $metadata = []
): int|bool

// Data Retrieval
public function get_user_sessions(string $email): array
public function get_user_conversions(string $email): array
```

**Usage Example:**

```php
$tracker = EduBot_Attribution_Tracker::get_instance($logger);

// Track a session
$tracker->track_user_session(
    'user@example.com',
    'facebook',
    'Summer Campaign 2025',
    'facebook_paid'
);

// Track conversion
$tracker->track_conversion(
    'user@example.com',
    'enquiry_form',
    'completed',
    ['form_id' => 'contact_form_1', 'value' => 500]
);

// Get user journey
$sessions = $tracker->get_user_sessions('user@example.com');
```

**Database Tables:**
- `wp_edubot_attribution_sessions`
- `wp_edubot_conversions`
- `wp_edubot_attributions`

---

### 3. EduBot_Attribution_Models

**Purpose:** 5 different attribution models for ROI analysis

**Namespace:** `includes/class-attribution-models.php`

**Methods:**

```php
public static function calculate_first_touch(array $touchpoints): array
public static function calculate_last_touch(array $touchpoints): array
public static function calculate_linear(array $touchpoints): array
public static function calculate_time_decay(array $touchpoints): array
public static function calculate_u_shaped(array $touchpoints): array
```

**Attribution Models:**

1. **First Touch** - 100% credit to first channel
2. **Last Touch** - 100% credit to last channel
3. **Linear** - Equal credit to all channels
4. **Time Decay** - More credit to recent touches (half-life: 7 days)
5. **U-Shaped** - 40% first, 40% last, 20% middle

**Usage Example:**

```php
$touchpoints = [
    ['channel' => 'google', 'timestamp' => '2025-11-01 10:00'],
    ['channel' => 'facebook', 'timestamp' => '2025-11-03 15:30'],
    ['channel' => 'direct', 'timestamp' => '2025-11-05 09:00']
];

// Calculate different models
$first = EduBot_Attribution_Models::calculate_first_touch($touchpoints);
$last = EduBot_Attribution_Models::calculate_last_touch($touchpoints);
$linear = EduBot_Attribution_Models::calculate_linear($touchpoints);
$decay = EduBot_Attribution_Models::calculate_time_decay($touchpoints);
$ushape = EduBot_Attribution_Models::calculate_u_shaped($touchpoints);
```

---

### 4. EduBot_Conversion_API_Manager

**Purpose:** Send conversion data to ad platforms

**Namespace:** `includes/class-conversion-api-manager.php`

**Methods:**

```php
public static function send_to_facebook(array $conversion_data): bool
public static function send_to_google(array $conversion_data): bool
public static function send_to_tiktok(array $conversion_data): bool
public static function send_to_linkedin(array $conversion_data): bool
public static function hash_pii(string $data): string
```

**Usage Example:**

```php
$api = EduBot_Conversion_API_Manager::get_instance();

$conversion = [
    'email' => 'user@example.com',
    'phone' => '+1234567890',
    'conversion_value' => 500,
    'currency' => 'USD',
    'timestamp' => time()
];

// Send to platforms
$api->send_to_facebook($conversion);
$api->send_to_google($conversion);
$api->send_to_tiktok($conversion);
$api->send_to_linkedin($conversion);
```

---

## Attribution System

### EduBot_Admin_Dashboard

**Purpose:** Retrieve analytics data for dashboard

**Namespace:** `includes/admin/class-admin-dashboard.php`

**Methods:**

```php
public function get_kpis(string $period = 'month'): array
public function get_kpi_summary(): array
public function get_enquiries_by_source(string $period = 'month'): array
public function get_enquiries_by_campaign(string $period = 'month'): array
public function get_enquiry_trends(string $period = 'month'): array
public function get_device_breakdown(string $period = 'month'): array
public function get_top_campaigns(int $limit = 5): array
```

**KPI Response Format:**

```php
[
    'total_conversions' => 25,
    'total_clicks' => 200,
    'conversion_rate' => 12.5,
    'avg_session_duration' => 450,
    'top_channel' => ['channel' => 'facebook', 'count' => 8],
    'top_campaign' => ['campaign' => 'Summer Sale', 'count' => 6]
]
```

**Usage Example:**

```php
$dashboard = new EduBot_Admin_Dashboard($logger);

// Get KPIs for different periods
$monthly_kpis = $dashboard->get_kpis('month');
$weekly_kpis = $dashboard->get_kpis('week');

// Get source breakdown
$sources = $dashboard->get_enquiries_by_source('month');

// Get trends
$trends = $dashboard->get_enquiry_trends('month');
```

**Supported Periods:**
- `week` - Last 7 days
- `month` - Last 30 days
- `quarter` - Last 90 days
- `year` - Last 365 days

---

## Reporting System

### EduBot_Performance_Reports

**Purpose:** Automated email report scheduling and delivery

**Namespace:** `includes/class-performance-reports.php`

**Methods:**

```php
// Singleton
public static function get_instance(EduBot_Logger $logger = null): EduBot_Performance_Reports

// Report Generation
public function generate_daily_report(): array
public function generate_weekly_report(): array
public function generate_monthly_report(): array

// Scheduling
public function schedule_daily_report(): bool
public function schedule_weekly_report(): bool
public function schedule_monthly_report(): bool

// Recipients
public function add_recipient(string $email): bool
public function remove_recipient(string $email): bool
public function get_recipients(): array

// History
public function get_report_history(int $limit = 50): array
public function get_report_statistics(): array
```

**Configuration Options:**

```php
// Enable reports
update_option('edubot_daily_report_enabled', true);
update_option('edubot_weekly_report_enabled', true);
update_option('edubot_monthly_report_enabled', true);

// Set report times
update_option('edubot_daily_report_time', '09:00');    // HH:MM format
update_option('edubot_weekly_report_time', '09:00');
update_option('edubot_weekly_report_day', 'monday');   // Or any day

// Recipients
update_option('edubot_report_recipients', [
    'admin@example.com',
    'manager@example.com'
]);
```

**Usage Example:**

```php
$reports = EduBot_Performance_Reports::get_instance($logger);

// Add recipients
$reports->add_recipient('analytics@example.com');

// Enable and schedule
update_option('edubot_daily_report_enabled', true);
$reports->schedule_daily_report();

// Get history
$history = $reports->get_report_history(25);
foreach ($history as $report) {
    echo $report['recipient'] . ' - ' . $report['status'];
}
```

---

### EduBot_Cron_Scheduler

**Purpose:** Manage WP-Cron schedule for reports

**Namespace:** `includes/class-cron-scheduler.php`

**Methods:**

```php
public static function init(): void
public static function setup_on_activation(): void
public static function cleanup_on_deactivation(): void
public static function add_custom_schedules(array $schedules): array
public static function get_next_scheduled(string $report_type): int|bool
public static function get_all_scheduled(): array
```

**Supported Intervals:**
- `hourly` - Every hour
- `twicedaily` - Twice daily
- `daily` - Every day
- `weekly` - Every 7 days (custom)
- `monthly` - Every 30 days (custom)

---

## Admin Interface

### EduBot_Dashboard_Widget

**Purpose:** Quick stats widgets on WordPress dashboard

**Namespace:** `includes/admin/class-dashboard-widget.php`

**Widgets:**

1. **Analytics Summary**
   - Total Enquiries (This Month)
   - Top Channel (By Volume)
   - Conversion Rate (Last 30 days)
   - Average Session Time

2. **Recent Conversions**
   - Last 5 conversions
   - Channel, Date, Status
   - Status color indicators

3. **Top Marketing Channels**
   - Channel breakdown by percentage
   - Volume count per channel
   - Visual progress bars

**Methods:**

```php
public static function get_instance(EduBot_Logger $logger = null): EduBot_Dashboard_Widget
public function register_widgets(): void
public function render_analytics_widget(): void
public function render_recent_conversions_widget(): void
public function render_top_channels_widget(): void
```

---

### EduBot_API_Settings_Page

**Purpose:** Manage API credentials for all platforms

**Namespace:** `includes/admin/class-api-settings-page.php`

**Configuration Options:**

```php
// Facebook
edubot_facebook_app_id
edubot_facebook_app_secret
edubot_facebook_access_token

// Google Ads
edubot_google_client_id
edubot_google_client_secret
edubot_google_refresh_token

// TikTok
edubot_tiktok_app_id
edubot_tiktok_app_secret
edubot_tiktok_access_token

// LinkedIn
edubot_linkedin_client_id
edubot_linkedin_client_secret
edubot_linkedin_access_token
```

**Methods:**

```php
public function register_settings(): void
public function render_page(): void
public function handle_form_submission(): void
public function handle_test_connection(): void
```

---

## Database Schema

### Attribution Tables

**`wp_edubot_attribution_sessions`**

```sql
CREATE TABLE wp_edubot_attribution_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    channel VARCHAR(50) NOT NULL,
    campaign VARCHAR(255),
    utm_source VARCHAR(100),
    first_touch_time DATETIME NOT NULL,
    last_touch_time DATETIME NOT NULL,
    session_duration INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_session (user_email, channel, campaign),
    INDEX idx_user_email (user_email),
    INDEX idx_channel (channel),
    INDEX idx_created_at (created_at)
);
```

**`wp_edubot_conversions`**

```sql
CREATE TABLE wp_edubot_conversions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    conversion_type VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'completed',
    value DECIMAL(10, 2),
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_email (user_email),
    INDEX idx_conversion_type (conversion_type),
    INDEX idx_created_at (created_at)
);
```

**`wp_edubot_attributions`**

```sql
CREATE TABLE wp_edubot_attributions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(255) NOT NULL,
    conversion_id BIGINT,
    session_id BIGINT,
    channel VARCHAR(50),
    attribution_model VARCHAR(50),
    credit DECIMAL(5, 4),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_email (user_email),
    INDEX idx_conversion_id (conversion_id),
    FOREIGN KEY (conversion_id) REFERENCES wp_edubot_conversions(id) ON DELETE CASCADE
);
```

**`wp_edubot_report_schedules`**

```sql
CREATE TABLE wp_edubot_report_schedules (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    period VARCHAR(20),
    status VARCHAR(20),
    sent_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_sent_at (sent_at)
);
```

**`wp_edubot_logs`**

```sql
CREATE TABLE wp_edubot_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    level VARCHAR(20),
    message TEXT,
    context JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
);
```

---

## API Integrations

### Facebook Conversion API

**Setup:**
1. Go to developers.facebook.com
2. Create app or select existing
3. Add "Conversions API" product
4. Generate access token with ads_management permission
5. Save App ID and token in settings

**Data Sent:**
- Email (hashed with SHA256)
- Phone (hashed with SHA256)
- Conversion value and currency
- Timestamp

### Google Ads Conversion API

**Setup:**
1. Go to console.developers.google.com
2. Enable Google Ads API
3. Create OAuth 2.0 credentials
4. Authorize application for refresh token
5. Save Client ID and Refresh Token

**Data Sent:**
- Email (hashed)
- Conversion value
- Conversion time
- Conversion ID

### TikTok Conversions API

**Setup:**
1. Go to business.tiktok.com
2. Create Business Account
3. Generate App ID and Secret
4. Request API access
5. Generate access token

**Data Sent:**
- Email, phone, MAID (hashed)
- Event type (Purchase, Lead, etc.)
- Event value and currency
- Timestamp

### LinkedIn Conversion API

**Setup:**
1. Go to linkedin.com/developers
2. Create new app
3. Request access to Conversions API
4. Generate access token
5. Save credentials

**Data Sent:**
- Email (hashed)
- First name, last name
- Conversion timestamp
- Conversion value

---

## WordPress Hooks

### Filters

```php
// Modify attribution data before saving
apply_filters('edubot_attribution_data', $data)

// Modify conversion payload
apply_filters('edubot_conversion_payload', $payload, $email)

// Customize dashboard KPI labels
apply_filters('edubot_kpi_labels', $labels)

// Modify report recipients
apply_filters('edubot_report_recipients', $recipients)
```

### Actions

```php
// After conversion is tracked
do_action('edubot_conversion_tracked', $conversion_id, $email)

// Before report is sent
do_action('edubot_before_report_sent', $report_data, $recipient)

// After report is sent
do_action('edubot_after_report_sent', $report_id, $recipient, $status)

// On API error
do_action('edubot_api_error', $platform, $error, $response)

// On schedule activation
do_action('edubot_schedule_activated', $schedule_type)

// On schedule deactivation
do_action('edubot_schedule_deactivated', $schedule_type)
```

---

## Error Handling

### Exception Classes

```php
class EduBot_API_Exception extends Exception {}
class EduBot_Database_Exception extends Exception {}
class EduBot_Authentication_Exception extends Exception {}
```

### Try-Catch Example

```php
try {
    $tracker = EduBot_Attribution_Tracker::get_instance($logger);
    $result = $tracker->track_conversion($email, 'enquiry', 'completed');
} catch (Exception $e) {
    $logger->log_error('Conversion tracking failed: ' . $e->getMessage(), [
        'email' => $email,
        'exception' => get_class($e)
    ]);
}
```

---

## Performance Considerations

### Query Optimization
- All tables indexed on frequently searched columns
- Use prepared statements (WordPress $wpdb->prepare)
- Implement query result caching (transients)

### Caching Strategy
- KPI results cached for 5 minutes
- Attribution calculations cached per user
- Clear cache on new conversion

### Database Maintenance
- Run log cleanup monthly (90-day retention)
- Archive old reports quarterly
- Optimize tables monthly

---

## Security Best Practices

### Data Protection
- PII is hashed before sending to APIs (SHA256)
- Passwords stored in WordPress options (secure)
- Nonces used for all admin forms
- Capabilities checked before operations

### API Keys
- Stored encrypted in WordPress options
- Never logged to error logs
- Validated before use
- Rotated periodically

### Access Control
- Admin-only pages require `manage_options`
- Dashboard accessible only to logged-in users
- API endpoints protected with nonces
- User roles strictly enforced

---

## Related Resources

- [Setup Guide](./SETUP_GUIDE.md)
- [Configuration Reference](./CONFIGURATION_GUIDE.md)
- [Troubleshooting](./TROUBLESHOOTING_GUIDE.md)
- [FAQ](./FAQ.md)

