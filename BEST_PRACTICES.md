# EduBot Pro - Best Practices Guide

**Version:** 1.4.1  
**Last Updated:** November 5, 2025  
**Audience:** Developers, Marketers, DevOps Engineers

---

## 🎯 Attribution Modeling Best Practices

### Choosing the Right Attribution Model

**First Touch Attribution** - Best for:
- Top-of-funnel awareness campaigns
- Understanding initial touchpoints
- New customer acquisition analysis

```
Use when: You want to understand how users first discover you
Example: Attribution to first Google search or Facebook ad
Limitation: Ignores entire conversion journey
```

**Last Touch Attribution** - Best for:
- Bottom-of-funnel conversions
- Direct response campaigns
- Immediate purchase analysis

```
Use when: You want credit for final conversion action
Example: Direct traffic before purchase
Limitation: Ignores all earlier marketing touches
```

**Linear Attribution** - Best for:
- Equal-weight channel analysis
- Balanced marketing mix
- Default/safe option

```
Use when: All channels equally important
Example: Each of 3 channels gets 33% credit
Strength: Fair to all channels
Limitation: Doesn't reflect actual influence
```

**Time Decay Attribution** - Best for:
- Complex multi-touch journeys
- Channels with varying time impact
- Most realistic modeling

```
Use when: Recent touches more influential than old ones
Example: Click yesterday more valuable than click 30 days ago
Half-life: 7 days (50% decay per week)
Strength: Reflects user behavior
Limitation: Most complex to interpret
```

**U-Shaped Attribution** - Best for:
- Balanced first-last balance
- Most common use case
- Default recommendation

```
Use when: Both first and last touches important
Weight distribution: 40% first, 40% last, 20% middle
Strength: Recognizes all touches
Limitation: Complex for reporting
```

### Implementation Recommendation

```php
// 1. Start with Linear (simplest, most fair)
update_option('edubot_attribution_model', 'linear');

// 2. After 30 days data, analyze results
// Compare model outputs in dashboard

// 3. Switch to Time Decay for accuracy
// This reflects real user behavior best
update_option('edubot_attribution_model', 'time_decay');

// 4. Monitor and adjust periodically
// Review quarterly whether model matches your marketing
```

---

## 📊 Dashboard Analytics Best Practices

### KPI Interpretation

**Conversion Rate**
- Formula: Conversions ÷ Sessions × 100%
- Good: 3-5% (industry average)
- Excellent: >10%
- Poor: <1%

```
Interpretation:
- Decreasing: Ad quality decline or website issues
- Increasing: Better targeting or improved UX
- Action: Compare by channel to find issues
```

**Average Session Duration**
- Measure: Time from first to last interaction
- Good: 5+ minutes
- Excellent: 15+ minutes
- Poor: <1 minute (bounce)

```
Interpretation:
- Low: Visitors not engaged, content not relevant
- High: Content engaging, conversion likely
- Action: Improve landing page relevance for low channels
```

**Top Channel**
- Definition: Channel with most conversions
- Action: Increase budget to top channel
- Caution: Last-touch attribution favors bottom-funnel channels

```
Recommendations:
1. Analyze first-touch to find awareness channels
2. Compare channel efficiency (cost per conversion)
3. Allocate budget to best ROI channels
4. Test new channels with small budget
```

### Dashboard Period Selection

**Weekly View:**
- Best for: Daily monitoring, immediate issues
- Use: Tactical decision making
- Limitation: High volatility, small sample size

**Monthly View:**
- Best for: Standard reporting, trends
- Use: Strategic planning
- Recommendation: Default view

**Quarterly View:**
- Best for: Seasonal analysis, trend identification
- Use: Long-term planning
- Limitation: May hide monthly variations

**Yearly View:**
- Best for: Annual performance, year-over-year
- Use: Executive reporting
- Limitation: Too aggregated for tactics

### Custom Dashboard Queries

```php
// Get conversions by campaign
$conversions = $wpdb->get_results("
    SELECT campaign, COUNT(*) as count, AVG(session_duration) as avg_duration
    FROM wp_edubot_conversions
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY campaign
    ORDER BY count DESC
");

foreach ($conversions as $row) {
    echo $row->campaign . ': ' . $row->count . ' conversions';
}
```

---

## 📧 Email Reporting Best Practices

### Report Frequency Selection

**Daily Reports:**
- Best for: High-volume sites (>100 daily conversions)
- Recipient: Analysts, marketers
- Risk: Report fatigue

```
Recommended settings:
- Time: 09:00 AM
- Recipients: 1-2 key analysts
- Content: KPI summary only (no trends)
```

**Weekly Reports:**
- Best for: Most sites (5-100 daily conversions)
- Recipient: Managers, decision-makers
- Sweet spot: Most common choice

```
Recommended settings:
- Day: Monday at 09:00 AM
- Recipients: Manager, director
- Content: Full analytics + trends
```

**Monthly Reports:**
- Best for: Executive reporting
- Recipient: C-level, stakeholders
- Use: Strategic decision-making

```
Recommended settings:
- Day: 1st at 09:00 AM
- Recipients: CMO, VP, CEO
- Content: KPIs, trends, recommendations
```

### Recipient Management

**Recommended Structure:**
```
Daily:  analytics@yourcompany.com
        → Analysts reviewing data continuously

Weekly: marketing@yourcompany.com
        → Manager, team lead reviews trends

Monthly: executive@yourcompany.com
         → C-level strategic planning
```

**Add Recipients Safely:**

```php
// Verify email format before adding
$emails = get_option('edubot_report_recipients', []);
$email = sanitize_email('newuser@example.com');

if (!is_email($email)) {
    error_log('Invalid email format');
    return false;
}

if (!in_array($email, $emails)) {
    $emails[] = $email;
    update_option('edubot_report_recipients', $emails);
}
```

### Custom Report Content

Only include relevant sections:

```php
// Customize what appears in emails
update_option('edubot_report_include_kpis', true);          // Always include
update_option('edubot_report_include_channels', true);      // Often useful
update_option('edubot_report_include_campaigns', true);     // Sometimes useful
update_option('edubot_report_include_trends', false);       // Often too detailed
update_option('edubot_report_include_devices', false);      // Rarely needed
```

---

## 🔐 Security Best Practices

### API Credential Management

**Storage:**
- Store in WordPress database (encrypted by default)
- Never commit to version control
- Use environment variables in production

```php
// ✗ Wrong - Never do this
$app_id = 'APP_ID_HARDCODED_IN_CODE';

// ✓ Correct - Use WordPress options
$app_id = get_option('edubot_facebook_app_id');

// ✓ Production - Use environment variables
$app_id = getenv('FACEBOOK_APP_ID');
if ($app_id) {
    update_option('edubot_facebook_app_id', $app_id);
}
```

**Rotation Schedule:**
- Rotate every 90 days
- Immediately after any staff departure
- After any suspected compromise

```
Process:
1. Generate new token/credential
2. Update in EduBot settings
3. Test connection
4. Delete old credential from platform
5. Log change in your system
```

**Access Control:**
- Admin-only page (manage_options capability)
- Requires login
- HTTPS/SSL required for live

```php
// Verify user has permission
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}

// Verify HTTPS in production
if (!is_ssl() && 'production' === wp_get_environment_type()) {
    wp_die('HTTPS required for API settings');
}
```

### PII Handling

**Best Practices:**
- PII hashed before sending to APIs (SHA256)
- Never log PII to error logs
- Comply with GDPR/CCPA

```php
// ✓ Correct - Hash PII before logging
$email_hash = hash('sha256', $email);
$logger->log_info('Conversion tracked', ['email_hash' => $email_hash]);

// ✗ Wrong - Never log plain PII
$logger->log_info('Conversion', ['email' => $email]); // GDPR violation
```

**Data Retention:**
- Logs: 90 days (auto-delete)
- Sessions: 180 days
- Conversions: 365 days (comply with retention requirements)

---

## ⚡ Performance Optimization

### Database Optimization

**Monthly Maintenance:**

```sql
-- Optimize tables (clears fragmentation)
OPTIMIZE TABLE wp_edubot_conversions;
OPTIMIZE TABLE wp_edubot_attribution_sessions;
OPTIMIZE TABLE wp_edubot_attributions;
OPTIMIZE TABLE wp_edubot_logs;

-- Analyze table statistics
ANALYZE TABLE wp_edubot_conversions;

-- Expected improvement: 10-30% query speed increase
```

**Remove Unnecessary Data:**

```php
// Delete logs older than retention period
$logger = EduBot_Logger::get_instance();
$deleted = $logger->cleanup_old_logs(90); // Keep 90 days
echo "Deleted $deleted log entries";

// Archive old conversions to separate table
INSERT INTO wp_edubot_conversions_archive
SELECT * FROM wp_edubot_conversions 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY);

DELETE FROM wp_edubot_conversions 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 365 DAY);
```

### Query Caching

**Enable and Configure:**

```php
// Enable caching in wp-config.php or settings
update_option('edubot_enable_caching', true);

// Set cache duration to 5 minutes
update_option('edubot_cache_duration', 300);

// Cache KPI queries specifically
add_filter('edubot_cache_key_kpis', function($key) {
    return 'edubot_kpis_' . gmdate('Y-m-d-H');
});
```

**Cache Invalidation:**

```php
// Clear cache when new conversion recorded
add_action('edubot_conversion_tracked', function($conversion_id) {
    delete_transient('edubot_kpis_' . gmdate('Y-m-d'));
    delete_transient('edubot_kpis_' . gmdate('Y-m-d-H'));
});
```

### Batch Processing

For large datasets, use batch processing to avoid memory issues:

```php
// Process 100 conversions at a time
update_option('edubot_batch_processing', true);
update_option('edubot_batch_size', 100);

// In report generation:
for ($i = 0; $i < $total; $i += 100) {
    $batch = $wpdb->get_results(
        "SELECT * FROM wp_edubot_conversions LIMIT 100 OFFSET $i"
    );
    
    foreach ($batch as $conversion) {
        // Process individually
    }
    
    // Free memory
    wp_cache_flush();
}
```

---

## 🚀 Integration Best Practices

### Form Tracking Integration

**Contact Form 7:**

```php
add_action('wpcf7_mail_sent', function($contact_form) {
    $tracker = EduBot_Attribution_Tracker::get_instance();
    
    if (isset($_POST['your_email'])) {
        $email = sanitize_email($_POST['your_email']);
        $tracker->track_conversion(
            $email,
            'contact_form_7',
            'completed',
            ['form_id' => $contact_form->id()]
        );
    }
});
```

**Gravity Forms:**

```php
add_action('gform_after_submission', function($entry, $form) {
    $tracker = EduBot_Attribution_Tracker::get_instance();
    $email = rgar($entry, 'email_field_id');
    
    if ($email) {
        $tracker->track_conversion(
            $email,
            'gravity_form',
            'completed',
            ['form_id' => $form['id']]
        );
    }
}, 10, 2);
```

**WooCommerce Orders:**

```php
add_action('woocommerce_order_status_completed', function($order_id) {
    $order = wc_get_order($order_id);
    $tracker = EduBot_Attribution_Tracker::get_instance();
    
    $tracker->track_conversion(
        $order->get_billing_email(),
        'woocommerce_purchase',
        'completed',
        ['order_id' => $order_id, 'amount' => $order->get_total()]
    );
});
```

---

## 📈 Growth Hacks & Optimization

### A/B Testing Integration

```php
// Track which version user saw
$ab_test = isset($_GET['ab_variant']) ? sanitize_text_field($_GET['ab_variant']) : 'control';

$tracker->track_conversion(
    $email,
    'form_submission',
    'completed',
    ['ab_test_variant' => $ab_test]
);

// Analyze results
$wpdb->get_results("
    SELECT metadata->'$.ab_test_variant' as variant,
           COUNT(*) as conversions,
           COUNT(*) * 100 / (SELECT COUNT(*) FROM wp_edubot_conversions) as percentage
    FROM wp_edubot_conversions
    WHERE DATE(created_at) = CURDATE()
    GROUP BY metadata->'$.ab_test_variant'
");
```

### UTM Campaign Tracking

**Recommended UTM Structure:**

```
https://yoursite.com/?utm_source=facebook&utm_medium=cpc&utm_campaign=summer_sale&utm_content=v1

utm_source:   facebook, google, tiktok, linkedin, email, direct
utm_medium:   cpc, cpm, organic, social, email, referral
utm_campaign: summer_sale, back_to_school, black_friday
utm_content:  creative_v1, creative_v2 (for A/B testing)
utm_term:     keyword (for paid search)
```

**Tracking UTM Values:**

```php
$utm_source = sanitize_text_field($_GET['utm_source'] ?? '');
$utm_campaign = sanitize_text_field($_GET['utm_campaign'] ?? '');

$tracker->track_conversion(
    $email,
    'form_submission',
    'completed',
    [
        'utm_source' => $utm_source,
        'utm_campaign' => $utm_campaign
    ]
);
```

### Channel-Specific Optimization

```php
// Identify underperforming channels
$channels = $dashboard->get_enquiries_by_source('month');

$threshold = 10; // Min acceptable conversion rate

foreach ($channels as $channel) {
    $rate = ($channel['conversions'] / $channel['clicks']) * 100;
    
    if ($rate < $threshold) {
        error_log("Low performance: $channel[name] ($rate%)");
        // Alert team to review creative/targeting
    }
}
```

---

## 🔍 Debugging & Testing

### Enable Debugging

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// In wp-admin
update_option('edubot_debug_mode', true);
update_option('edubot_debug_api_calls', true);
update_option('edubot_debug_queries', true);

// Check logs
tail -100 /wp-content/debug.log
```

### Test Data Generation

```php
// Generate test session and conversion
$emails = [
    'user1@test.com',
    'user2@test.com',
    'user3@test.com'
];

foreach ($emails as $email) {
    $tracker->track_user_session($email, 'facebook', 'Test Campaign');
    $tracker->track_conversion($email, 'test_conversion', 'completed');
}

// Verify in dashboard
wp eval 'echo get_option("edubot_debug_mode") ? "Debug enabled" : "Debug disabled"'
```

---

## 📚 Documentation Maintenance

### Keep Documentation Updated

When making changes:
1. Update code comments
2. Update API_REFERENCE.md
3. Add entry to CHANGELOG
4. Update CONFIGURATION_GUIDE.md if settings change

```
Good comment:
// Track conversion and send to ad platforms
// Only sends if send_to_platforms setting enabled
// Returns conversion ID on success, false on failure
$conversion_id = $tracker->track_conversion($email, $type);

Bad comment:
// Track conversion
$conversion_id = $tracker->track_conversion($email, $type);
```

---

## ✅ Launch Checklist

Before production deployment:

```
Pre-Launch (2 weeks before):
☐ All API credentials obtained and tested
☐ Attribution model selected and documented
☐ Report recipients configured
☐ Email templates reviewed
☐ Tracking events defined

Launch Week:
☐ Plugin activated on staging
☐ All features tested end-to-end
☐ Database tables verified
☐ Reports sent successfully
☐ Documentation reviewed by team

Production Launch:
☐ Backup existing database
☐ Install plugin on production
☐ Activate API credentials
☐ Run 1st full report manually
☐ Monitor logs for 24 hours
☐ Announce to team

Post-Launch (1 week):
☐ Team reviews first dashboard
☐ Verify all reports sending
☐ Check for errors in logs
☐ Gather feedback
☐ Make necessary adjustments
```

---

## Related Resources

- [Setup Guide](./SETUP_GUIDE.md)
- [API Reference](./API_REFERENCE.md)
- [Configuration Guide](./CONFIGURATION_GUIDE.md)
- [Troubleshooting Guide](./TROUBLESHOOTING_GUIDE.md)

