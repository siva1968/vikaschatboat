# EduBot Pro - Complete Setup & Installation Guide

**Version:** 1.4.1  
**Last Updated:** November 5, 2025  
**Installation Time:** 15-30 minutes  
**Difficulty:** Intermediate

---

## 📋 Requirements

### Server Requirements
- **WordPress:** 5.5+ (tested on 6.4+)
- **PHP:** 7.4+ (recommended 8.0+)
- **MySQL:** 5.7+ (recommended 8.0+)
- **HTTPS:** Required for live usage
- **SSL Certificate:** Valid certificate for domain
- **Cron:** WordPress cron enabled (WP_DISABLE_CRON = false)

### API Platform Access
- Facebook Business Account with Ads Manager
- Google Ads Account with API access
- TikTok Business Account
- LinkedIn Campaign Manager Account

### Additional Plugins (Optional)
- WooCommerce (if tracking e-commerce conversions)
- Gravity Forms / Contact Form 7 (for form tracking)
- MonsterInsights (for Google Analytics integration)

---

## 🚀 Installation Steps

### Step 1: Download & Install Plugin

```bash
# Option A: Via WordPress Admin
1. Go to Plugins → Add New
2. Search for "EduBot Pro"
3. Click "Install Now"
4. Click "Activate"

# Option B: Manual Upload
1. Download edubot-pro.zip
2. Extract to /wp-content/plugins/edubot-pro/
3. Go to Plugins → Installed Plugins
4. Click "Activate" under EduBot Pro
```

### Step 2: Verify Installation

After activation, verify:

```
✅ Plugin activated in Plugins menu
✅ EduBot Admin menu appears in WordPress sidebar
✅ No PHP errors in debug log
✅ Database tables created:
   - wp_edubot_attribution_sessions
   - wp_edubot_conversions
   - wp_edubot_attributions
   - wp_edubot_report_schedules
   - wp_edubot_logs
```

### Step 3: Configure API Credentials

Navigate to: **EduBot → API Settings**

You'll see 4 tabs for platform configuration:

#### Facebook Setup

1. Go to [developers.facebook.com](https://developers.facebook.com)
2. Create or select an app
3. Add "Conversions API" product
4. Generate access token:
   - Scopes needed: `ads_management`, `offline_access`
5. Copy credentials:
   - **App ID:** Found in Settings → Basic
   - **App Secret:** Found in Settings → Basic
   - **Access Token:** Generate in Tools → Access Token Debugger

6. Paste in EduBot:
   - App ID → "Facebook App ID"
   - App Secret → "Facebook App Secret"
   - Access Token → "Facebook Access Token"

7. Click "Test Connection" to verify

#### Google Ads Setup

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create new project or select existing
3. Enable APIs:
   - Google Ads API
   - Google Analytics Reporting API (optional)

4. Create OAuth 2.0 credentials:
   - Application type: Web application
   - Authorized redirect URIs: `https://yourdomain.com/wp-admin/admin.php?page=edubot-api-settings`

5. Get credentials:
   - **Client ID:** Found in Credentials section
   - **Client Secret:** Found in Credentials section

6. Generate refresh token:
   ```bash
   # Use OAuth flow or Google's Playground
   # https://developers.google.com/oauthplayground
   ```

7. Paste in EduBot:
   - Client ID → "Google Client ID"
   - Client Secret → "Google Client Secret"
   - Refresh Token → "Google Refresh Token"

#### TikTok Setup

1. Go to [business.tiktok.com](https://business.tiktok.com)
2. Create Business Center or sign in
3. Go to Settings → Developer Tools
4. Create new app:
   - App Name: Your site name
   - App Type: Business Service
   - Use case: E-commerce or Lead generation

5. Copy credentials:
   - **App ID:** Found in App Details
   - **App Secret:** Found in App Details

6. Generate access token:
   - Scope: `business_data`
   - Use OAuth flow or token generation

7. Paste in EduBot:
   - App ID → "TikTok App ID"
   - App Secret → "TikTok App Secret"
   - Access Token → "TikTok Access Token"

#### LinkedIn Setup

1. Go to [linkedin.com/developers](https://linkedin.com/developers)
2. Create new app
3. Go to Auth tab
4. Authorized redirect URLs: `https://yourdomain.com/wp-admin/`
5. Copy credentials:
   - **Client ID**
   - **Client Secret**

6. Generate access token via OAuth flow
7. Paste in EduBot:
   - Client ID → "LinkedIn Client ID"
   - Client Secret → "LinkedIn Client Secret"
   - Access Token → "LinkedIn Access Token"

### Step 4: Enable Attribution Tracking

Navigate to: **EduBot → Settings**

#### Tracking Options

```
☑ Enable Session Tracking
  → Tracks when users interact with your site

☑ Enable Conversion Tracking
  → Tracks form submissions, enquiries, purchases

☑ Send to Ad Platforms
  → Sends conversion data to Facebook, Google, TikTok, LinkedIn

☑ Enable Automated Reports
  → Generates daily/weekly/monthly reports
```

#### Select Attribution Model

```
Options:
○ First Touch (100% to first channel)
○ Last Touch (100% to last channel)
● Linear (Equal weight to all channels)
○ Time Decay (More weight to recent)
○ U-Shaped (40% first, 40% last, 20% middle)
```

### Step 5: Configure Email Reports

Navigate to: **EduBot → Performance Reports**

#### Enable Reports

1. Check "Enable Daily Reports"
2. Check "Enable Weekly Reports" 
3. Check "Enable Monthly Reports"

#### Set Timing

```
Daily Report:
  Time: 09:00 AM
  Recipient: admin@yoursite.com

Weekly Report:
  Time: 09:00 AM
  Day: Monday
  Recipient: admin@yoursite.com, manager@yoursite.com

Monthly Report:
  Time: 09:00 AM
  Day: 1st
  Recipient: admin@yoursite.com
```

#### Add Recipients

1. Click "Add Recipient"
2. Enter email address
3. Choose which reports to receive
4. Click "Save"

### Step 6: Configure Dashboard Widgets

Navigate to: **WordPress Dashboard**

Click "Screen Options" (top right)

Check:
- ☑ EduBot Analytics Summary
- ☑ EduBot Recent Conversions
- ☑ EduBot Top Channels

Widgets will appear on dashboard and update in real-time.

### Step 7: Verify Tracking

1. Go to **EduBot → Dashboard**
2. Check if KPIs are loading
3. Look for test data or recent activity
4. Submit a test form or interaction
5. Verify in dashboard within 5 minutes

---

## 🔧 Advanced Configuration

### Enable WP Debug Logging

In `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

define('EDUBOT_DEBUG', true); // Enable EduBot detailed logging
```

EduBot logs appear in: `/wp-content/debug.log`

### Custom Attribution Model

To use custom weights in U-Shaped model:

```php
add_filter('edubot_u_shaped_weights', function($weights) {
    return [
        'first' => 0.5,    // 50% to first
        'last' => 0.3,     // 30% to last
        'middle' => 0.2    // 20% to middle
    ];
});
```

### Custom Report Template

To customize report email HTML:

```php
add_filter('edubot_report_template_html', function($html, $report_data) {
    // Modify $html
    return $html;
}, 10, 2);
```

### Increase Query Performance

Add to `wp-config.php`:

```php
define('EDUBOT_ENABLE_CACHING', true);
define('EDUBOT_CACHE_DURATION', 300); // seconds
define('EDUBOT_BATCH_PROCESSING', true);
```

---

## 🐛 Troubleshooting Installation

### Database Tables Not Created

**Symptom:** Error message about missing tables

**Solution:**

```php
// In wp-admin → Tools → Site Health → Debug
// Check if edubot tables exist

// If not, manually create:
// 1. Go to phpMyAdmin
// 2. Select your WordPress database
// 3. Click "SQL"
// 4. Copy-paste schema from API_REFERENCE.md
// 5. Click Execute
```

### API Credentials Not Saving

**Symptom:** Settings page shows blank fields

**Possible Causes:**
1. User doesn't have `manage_options` capability
2. Nonce expired (refresh page and try again)
3. Character limit exceeded (max 500 chars per field)

**Solution:**

```php
// Verify in database:
SELECT * FROM wp_options 
WHERE option_name LIKE 'edubot_%';

// Manually set (if necessary):
update_option('edubot_facebook_app_id', 'your_app_id');
```

### Cron Not Running

**Symptom:** Reports not being sent automatically

**Solution:**

```php
// In wp-config.php, ensure:
define('DISABLE_WP_CRON', false); // NOT true

// Manually trigger cron (if needed):
wp-cli cron event run --due-now

// Or add to server crontab:
*/15 * * * * curl https://yoursite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

### AttributionTracker Class Not Found

**Symptom:** Fatal error about missing class

**Solution:**

```php
// Clear plugin cache:
// 1. Go to Plugins → Installed Plugins
// 2. Deactivate EduBot Pro
// 3. Activate EduBot Pro again
// 4. Go to wp-admin and check for errors

// Or manually:
delete_option('edubot_plugin_initialized');
```

---

## 📊 First Data Collection

### Manual Test Flow

1. **Trigger a session:**
   ```
   Visit: https://yourdomain.com/?utm_source=facebook&utm_campaign=test
   (Simulates Facebook traffic)
   ```

2. **Submit a form:**
   ```
   Go to any form on your site
   Fill it out and submit
   (This creates a conversion)
   ```

3. **Check dashboard:**
   ```
   Go to EduBot → Dashboard
   Verify session and conversion appear
   (May take up to 5 minutes)
   ```

4. **Generate test report:**
   ```
   Go to EduBot → Performance Reports
   Click "Generate Test Report"
   (Emails immediately)
   ```

### Expected Dashboard Data

After 1-2 hours:
- ✅ Sessions from at least 1 channel
- ✅ At least 1 conversion
- ✅ Attribution model showing credit distribution
- ✅ KPIs calculating (conversion rate, avg session time)

---

## 🔐 Security Configuration

### API Key Security

1. **Store securely:**
   - Never commit credentials to version control
   - Use environment variables for production

2. **Rotate credentials:**
   - Every 90 days minimum
   - Immediately if compromised
   - After employee departures

3. **Monitor usage:**
   - Check EduBot logs regularly
   - Set up API rate limit alerts
   - Review failed authentication attempts

### User Permissions

Set appropriate roles:

```php
// View-only access (Analytics Manager)
// Ability to view dashboards but not change settings

// Edit access (Marketing Manager)
// Can modify campaigns and reports

// Full access (Admin)
// Can change API credentials and plugin settings
```

### Data Privacy

1. **GDPR Compliance:**
   - PII is hashed before sending to APIs
   - Implement "right to be forgotten"
   - Document data retention policies

2. **Data Retention:**
   - Logs deleted after 90 days (configurable)
   - Old reports archived to external storage
   - User sessions expire after 6 months

---

## 🚀 Performance Optimization

### Recommended Setup

**Small Site (< 1,000 visits/month):**
- Single WordPress database
- Daily reports
- No caching needed

**Medium Site (1,000-10,000 visits/month):**
- Dedicated database connection
- Enable query caching
- Daily + Weekly reports

**Large Site (> 10,000 visits/month):**
- Separate database server
- Redis caching
- All report frequencies
- Consider separate analytics database

### Database Optimization

Run monthly:

```sql
-- Optimize tables
OPTIMIZE TABLE wp_edubot_attribution_sessions;
OPTIMIZE TABLE wp_edubot_conversions;
OPTIMIZE TABLE wp_edubot_attributions;
OPTIMIZE TABLE wp_edubot_logs;

-- Analyze table statistics
ANALYZE TABLE wp_edubot_attribution_sessions;
```

---

## ✅ Installation Checklist

```
Verification Items:
☐ Plugin activated and no errors
☐ Database tables created (5 tables)
☐ API credentials entered (all 4 platforms)
☐ Connection tests passing (green checkmarks)
☐ Tracking enabled in settings
☐ Email reports configured
☐ Dashboard widgets visible
☐ Test session/conversion created
☐ First report generated and sent
☐ Debug log checked (no errors)
☐ WP-Cron enabled
☐ SSL certificate valid
☐ User permissions configured
```

---

## Next Steps

1. **Configure form tracking** - Set up form integration
2. **Connect phone system** - If using WhatsApp/SMS
3. **Set up dashboards** - Custom dashboard configurations
4. **Enable automation** - Advanced workflows
5. **Train team** - How to use dashboards and reports

---

## Support & Resources

- **Documentation:** See [Configuration Guide](./CONFIGURATION_GUIDE.md)
- **Troubleshooting:** See [Troubleshooting Guide](./TROUBLESHOOTING_GUIDE.md)
- **FAQ:** See [FAQ](./FAQ.md)
- **API Reference:** See [API Reference](./API_REFERENCE.md)

