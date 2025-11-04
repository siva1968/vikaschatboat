# 🚀 OPTION E IMPLEMENTATION ROADMAP
## Complete Marketing Analytics Platform (22-28 hours)

**Date Started:** November 4, 2025  
**Estimated Duration:** 22-28 hours (5-7 working days)  
**Target Completion:** November 10-12, 2025  
**Scope:** Full analytics platform with dashboard, reports, attribution, and conversion APIs  

---

## 📊 PROJECT OVERVIEW

### Deliverables (4 Major Components)

| Component | Hours | Files | Status |
|-----------|-------|-------|--------|
| Admin Dashboard | 4-6 | 1 PHP + 2 JS + CSS | 🔴 Not Started |
| Automated Reports | 3-4 | 1 PHP + Templates | 🔴 Not Started |
| Attribution Tracking | 8-10 | 1 PHP + 2 Migrations | 🔴 Not Started |
| Conversion APIs | 6-8 | 1 PHP + 4 API Classes | 🔴 Not Started |
| Database Schema | - | 5 Tables (Prisma) | 🔴 Not Started |
| Admin UI Pages | 2-3 | 3 WP Admin Pages | 🔴 Not Started |
| Settings Pages | 1-2 | 2 WP Settings Pages | 🔴 Not Started |
| Tests | 2-3 | PHPUnit Tests | 🔴 Not Started |
| Documentation | 1-2 | 5-6 MD Files | 🔴 Not Started |
| Deployment | 0.5 | Checklist + Guide | 🔴 Not Started |
| **TOTAL** | **28-38** | **20+ files** | **🔴 Pending** |

---

## 📁 FILE STRUCTURE

```
/includes/
├── admin/
│   ├── class-admin-dashboard.php          [NEW] Dashboard widget & queries
│   ├── class-admin-reports.php            [NEW] Reports UI & generation
│   ├── class-admin-attribution.php        [NEW] Attribution analysis page
│   ├── class-admin-settings.php           [NEW] API settings & config
│   ├── templates/
│   │   ├── dashboard-widget.php           [NEW] Dashboard HTML
│   │   ├── reports-page.php               [NEW] Reports page HTML
│   │   ├── attribution-page.php           [NEW] Attribution analysis HTML
│   │   ├── settings-page.php              [NEW] Settings form HTML
│   │   ├── email-report-template.php      [NEW] Email report template
│   │   └── pdf-report-template.php        [NEW] PDF report template
│   └── js/
│       ├── dashboard.js                   [NEW] Dashboard charts/interactivity
│       ├── reports.js                     [NEW] Reports filtering/export
│       └── attribution.js                 [NEW] Attribution visualization
├── analytics/
│   ├── class-performance-reports.php      [NEW] Report generation engine
│   ├── class-attribution-tracker.php      [NEW] Attribution logic & models
│   ├── class-attribution-models.php       [NEW] First/Last/Linear/Time-decay
│   └── class-analytics-queries.php        [NEW] Optimized analytics queries
├── integrations/
│   ├── class-conversion-api-manager.php   [NEW] Main API coordinator
│   ├── apis/
│   │   ├── class-facebook-conversions-api.php     [NEW]
│   │   ├── class-google-ads-conversion-api.php    [NEW]
│   │   ├── class-tiktok-events-api.php            [NEW]
│   │   └── class-linkedin-conversions-api.php     [NEW]
│   └── class-api-logger.php               [NEW] API request/response logging
├── database/
│   └── migrations/
│       ├── xxxx_create_attribution_tables.php     [NEW]
│       └── xxxx_add_api_tracking.php              [NEW]
└── class-edubot-pro.php                   [MODIFY] Add hook registrations
```

---

## 🗄️ DATABASE SCHEMA (5 New Tables)

### Table 1: Attribution Sessions
```sql
CREATE TABLE wp_edubot_attribution_sessions (
  session_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  enquiry_id BIGINT NOT NULL UNIQUE,
  user_session_key VARCHAR(100),
  first_touch_source VARCHAR(50),
  first_touch_timestamp DATETIME,
  last_touch_source VARCHAR(50),
  last_touch_timestamp DATETIME,
  total_touchpoints INT DEFAULT 1,
  attribution_model VARCHAR(20) DEFAULT 'last-click',
  journey_json LONGTEXT,  -- JSON with all touchpoints
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (enquiry_id) REFERENCES wp_edubot_enquiries(id),
  INDEX idx_model (attribution_model),
  INDEX idx_created (created_at)
);
```

### Table 2: Attribution Touchpoints
```sql
CREATE TABLE wp_edubot_attribution_touchpoints (
  touchpoint_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  session_id BIGINT NOT NULL,
  enquiry_id BIGINT NOT NULL,
  source VARCHAR(50),
  medium VARCHAR(50),
  campaign VARCHAR(100),
  platform_click_id VARCHAR(200),
  timestamp DATETIME,
  position_in_journey INT,
  page_title VARCHAR(255),
  page_url TEXT,
  referrer VARCHAR(255),
  device_type VARCHAR(20),
  attribution_weight DECIMAL(5,2) DEFAULT 100.00,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES wp_edubot_attribution_sessions(session_id),
  FOREIGN KEY (enquiry_id) REFERENCES wp_edubot_enquiries(id),
  INDEX idx_session (session_id),
  INDEX idx_source (source),
  INDEX idx_timestamp (timestamp)
);
```

### Table 3: Attribution Journeys
```sql
CREATE TABLE wp_edubot_attribution_journeys (
  journey_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  enquiry_id BIGINT NOT NULL UNIQUE,
  journey_path TEXT,  -- source1 > source2 > source3 > source4
  journey_length INT,
  total_time_minutes INT,
  first_touch_source VARCHAR(50),
  last_touch_source VARCHAR(50),
  conversion_value DECIMAL(10,2),
  attribution_model VARCHAR(20),
  calculated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enquiry_id) REFERENCES wp_edubot_enquiries(id),
  INDEX idx_path (journey_length),
  INDEX idx_model (attribution_model)
);
```

### Table 4: API Logs
```sql
CREATE TABLE wp_edubot_api_logs (
  log_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  enquiry_id BIGINT,
  api_provider VARCHAR(50),  -- facebook, google, tiktok, linkedin
  request_type VARCHAR(50),  -- conversion_event, event
  request_payload LONGTEXT JSON,
  response_status INT,
  response_payload LONGTEXT,
  success BOOLEAN DEFAULT FALSE,
  error_message TEXT,
  retry_count INT DEFAULT 0,
  last_retry DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (enquiry_id) REFERENCES wp_edubot_enquiries(id),
  INDEX idx_provider (api_provider),
  INDEX idx_status (response_status),
  INDEX idx_created (created_at)
);
```

### Table 5: Report Schedules
```sql
CREATE TABLE wp_edubot_report_schedules (
  schedule_id BIGINT PRIMARY KEY AUTO_INCREMENT,
  report_type VARCHAR(50),  -- weekly, monthly, daily
  recipient_email VARCHAR(255),
  recipient_name VARCHAR(100),
  include_dashboard BOOLEAN DEFAULT TRUE,
  include_sources BOOLEAN DEFAULT TRUE,
  include_campaigns BOOLEAN DEFAULT TRUE,
  include_attribution BOOLEAN DEFAULT TRUE,
  frequency VARCHAR(20),  -- daily, weekly, monthly
  day_of_week INT,  -- 0=Sunday, 1=Monday, etc
  time_of_day TIME,
  timezone VARCHAR(50),
  enabled BOOLEAN DEFAULT TRUE,
  last_sent DATETIME,
  next_send DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_enabled (enabled),
  INDEX idx_next_send (next_send)
);
```

---

## 🔧 COMPONENT BREAKDOWN

### COMPONENT 1: Admin Dashboard (4-6 Hours)

#### Files to Create:
1. **`class-admin-dashboard.php`** (300-400 lines)
   - Dashboard widget registration
   - Query methods for statistics
   - Data aggregation functions
   - Caching layer (5-minute TTL)

2. **`templates/dashboard-widget.php`** (200-300 lines)
   - HTML/CSS for dashboard layout
   - Chart containers
   - Stat boxes
   - Filters (date range, source, campaign)

3. **`js/dashboard.js`** (300-400 lines)
   - Chart.js integration
   - Real-time updates
   - Drill-down functionality
   - Export to CSV/PDF

#### Features:
```
Dashboard Widgets:
✓ Total Enquiries (current period)
✓ Enquiries by Source (pie chart)
✓ Enquiries by Campaign (bar chart)
✓ Conversion Trends (line graph)
✓ Top Performing Campaigns (table)
✓ Top Performing Sources (table)
✓ Cost Per Enquiry (if cost data available)
✓ ROI by Source (if cost data available)
✓ Period Comparison (vs previous month/week)
✓ Device Breakdown (mobile/desktop/tablet)
```

#### Queries Required:
- `get_enquiries_by_source()`
- `get_enquiries_by_campaign()`
- `get_enquiries_by_date_range()`
- `get_campaign_performance()`
- `get_source_performance()`
- `get_device_breakdown()`

---

### COMPONENT 2: Automated Reports (3-4 Hours)

#### Files to Create:
1. **`class-performance-reports.php`** (250-350 lines)
   - Report generation engine
   - Report scheduling
   - Email delivery
   - PDF generation

2. **`templates/email-report-template.php`** (150-200 lines)
   - HTML email template
   - Charts/graphs embedded as images
   - Summary section
   - Detailed tables

3. **`templates/pdf-report-template.php`** (100-150 lines)
   - PDF layout
   - Charts embedding
   - Header/footer
   - Professional branding

#### Features:
```
Report Types:
✓ Weekly Performance Report
✓ Monthly Performance Report
✓ Campaign Performance Report
✓ Source Performance Report
✓ Attribution Report
✓ Custom Date Range Report
✓ Comparison Report (vs previous period)
✓ Executive Summary Report

Report Contents:
✓ Total enquiries
✓ Breakdown by source
✓ Breakdown by campaign
✓ Trends (up/down/flat)
✓ Top performers
✓ Bottom performers
✓ Recommendations based on data
✓ Attached CSV/PDF for detailed analysis
```

#### Scheduling:
- Weekly reports: Every Monday at 8 AM
- Monthly reports: First day of month at 8 AM
- Custom schedules: User-configurable

---

### COMPONENT 3: Multi-Touch Attribution (8-10 Hours)

#### Files to Create:
1. **`class-attribution-tracker.php`** (400-500 lines)
   - Session tracking
   - Touchpoint recording
   - Journey reconstruction
   - Attribution calculation

2. **`class-attribution-models.php`** (300-400 lines)
   - First-touch model (100% credit to first)
   - Last-touch model (100% credit to last)
   - Linear model (equal credit to all)
   - Time-decay model (more weight to recent touches)
   - U-shaped model (40-20-40 to first/middle/last)

#### Features:
```
Attribution Capabilities:
✓ Track every page visit in session
✓ Record all UTM parameters at each visit
✓ Reconstruct full customer journey
✓ Apply different attribution models
✓ Calculate credit by channel
✓ Calculate credit by campaign
✓ Identify path to conversion
✓ Measure channel interaction

Model Comparisons:
✓ First-touch: Best for awareness
✓ Last-touch: Best for conversion
✓ Linear: Fair distribution
✓ Time-decay: Emphasizes recent activity
✓ U-shaped: Balance of awareness & conversion
```

#### Database Operations:
```php
// Record touchpoint
$attribution->record_touchpoint(
  $enquiry_id,
  $session_data
);

// Calculate attribution
$attribution->calculate_attribution(
  $enquiry_id,
  'last-touch'  // or 'first-touch', 'linear', 'time-decay'
);

// Get journey
$journey = $attribution->get_journey($enquiry_id);
// Returns: [source1, source2, source3, source4]

// Get attributed credit
$credit = $attribution->get_channel_credit(
  'facebook',
  'last-touch'
);
// Returns: [total_enquiries, attributed_enquiries, percentage]
```

---

### COMPONENT 4: Conversion API Integration (6-8 Hours)

#### Files to Create:
1. **`class-conversion-api-manager.php`** (250-350 lines)
   - Main orchestrator
   - API selection logic
   - Payload formatting
   - Error handling & retries

2. **`apis/class-facebook-conversions-api.php`** (200-250 lines)
   - Facebook API integration
   - Payload formatting per API spec
   - Hash matching (email/phone/name)
   - Pixel mapping

3. **`apis/class-google-ads-conversion-api.php`** (150-200 lines)
   - Google Ads conversion tracking
   - gclid lookup
   - Enhanced conversions (hashed data)

4. **`apis/class-tiktok-events-api.php`** (150-200 lines)
   - TikTok Events API
   - ttclid mapping
   - Event properties

5. **`apis/class-linkedin-conversions-api.php`** (150-200 lines)
   - LinkedIn Conversions API
   - li_fat_id mapping
   - Lead matching

6. **`class-api-logger.php`** (150-200 lines)
   - Request/response logging
   - Error tracking
   - Retry mechanism

#### Features:
```
Facebook Conversions API:
✓ Send conversion events in real-time
✓ First-party data collection
✓ Hash matching for privacy
✓ Offline conversion tracking
✓ Lead value tracking
✓ Pixel and API hybrid mode

Google Ads Conversion API:
✓ Track conversions via gclid
✓ Enhanced conversions (hashed PII)
✓ Auto-tagging compatibility
✓ Real-time conversion reporting

TikTok Events API:
✓ Track conversion events
✓ Lead value tracking
✓ Deduplication with pixel
✓ Real-time event delivery

LinkedIn Conversions API:
✓ First-party conversion tracking
✓ Lead matching
✓ Offline conversion upload
✓ Real-time reporting
```

#### API Payloads Example:

**Facebook Conversions API:**
```json
{
  "data": [{
    "event_name": "Lead",
    "event_time": 1621234567,
    "event_id": "lead_12345",
    "user_data": {
      "em": "hashedEmail@example.com",
      "ph": "hashPhoneNumber",
      "fn": "hashedFirstName",
      "ln": "hashedLastName"
    },
    "custom_data": {
      "value": 0,
      "currency": "USD"
    }
  }],
  "access_token": "FACEBOOK_ACCESS_TOKEN"
}
```

**Google Ads Conversion API:**
```json
{
  "conversions": [{
    "gclid": "TW05aOHNnK0CFA...",
    "conversion_action": "gads_conversion",
    "conversion_date_time": "2025-11-04 10:30:45",
    "conversion_value": 0,
    "currency_code": "USD",
    "user_identifiers": [{
      "hashed_email": "hashedEmail@example.com"
    }]
  }],
  "partial_failure": true
}
```

---

## 📋 IMPLEMENTATION PHASES

### Phase 1: Database & Migrations (1-2 hours) 🔴 Priority: CRITICAL
```
Tasks:
1. Create Prisma migration files
2. Add 5 new tables
3. Add indexes for performance
4. Create seed data (optional)
5. Test migrations on dev environment
```

### Phase 2: Core Components (10-12 hours) 🔴 Priority: CRITICAL
```
Tasks:
1. Create attribution-tracker.php
2. Create attribution-models.php
3. Create analytics-queries.php
4. Create conversion-api-manager.php
5. Create API provider classes (4 files)
6. Create performance-reports.php
7. All components tested locally
```

### Phase 3: Admin UI (4-5 hours) 🟡 Priority: HIGH
```
Tasks:
1. Create dashboard widget
2. Create reports page
3. Create attribution page
4. Create settings page
5. Create templates & JavaScript
6. Add admin menu items
7. Add navigation
```

### Phase 4: Testing & Documentation (2-3 hours) 🟡 Priority: HIGH
```
Tasks:
1. Write PHPUnit tests (50+ tests)
2. Write setup documentation
3. Write usage documentation
4. Write API configuration guide
5. Create troubleshooting guide
6. Create deployment checklist
```

### Phase 5: Deployment (0.5 hour) 🟢 Priority: MEDIUM
```
Tasks:
1. Pre-deployment validation
2. Database migration
3. Configuration setup
4. Feature testing
5. Go-live
6. Post-deployment monitoring
```

---

## 🎯 SUCCESS CRITERIA

### Component 1: Dashboard
- ✓ Dashboard loads in <2 seconds
- ✓ All charts render correctly
- ✓ Data updates within 5 minutes (cache)
- ✓ Filters work correctly
- ✓ CSV/PDF export functions
- ✓ Mobile responsive

### Component 2: Reports
- ✓ Weekly reports auto-send on Monday 8 AM
- ✓ Monthly reports auto-send on 1st at 8 AM
- ✓ Email formatting looks professional
- ✓ All data accurate
- ✓ PDF generation works
- ✓ Custom schedules work

### Component 3: Attribution
- ✓ Touchpoints recorded for 100% of enquiries
- ✓ All 5 attribution models calculate correctly
- ✓ Journey paths accurate
- ✓ Channel credit calculations verified
- ✓ Database queries <500ms

### Component 4: Conversion APIs
- ✓ All 4 platform APIs integrate
- ✓ Conversion events send successfully
- ✓ Retry mechanism works (3 retries on failure)
- ✓ Error logging comprehensive
- ✓ API logs stored correctly
- ✓ Zero sensitive data in logs

---

## 🔐 SECURITY CONSIDERATIONS

```
✓ API Keys: Stored in wp-config.php constants, never in database
✓ Data Privacy: Hash PII before sending to APIs (MD5, SHA256)
✓ GDPR Compliance: Only send data for users who consented
✓ Request Signing: Sign requests with API secrets
✓ Rate Limiting: Implement backoff for API failures
✓ Audit Logging: Log all API requests/responses
✓ Access Control: Restrict admin pages to admins only
✓ Input Validation: Sanitize all inputs before API send
```

---

## 🧪 TESTING STRATEGY

### Unit Tests (30-40 tests)
```php
✓ Attribution model calculations
✓ Report generation
✓ API payload formatting
✓ Database queries
✓ Caching logic
✓ Error handling
```

### Integration Tests (20-30 tests)
```php
✓ End-to-end enquiry flow with tracking
✓ Attribution with multiple touchpoints
✓ API sending with mocked endpoints
✓ Report email generation
✓ Database migrations
```

### Manual Tests (10-15 tests)
```
✓ Dashboard loads and renders
✓ Reports schedule and send
✓ Attribution calculates correctly
✓ API logs show success/failure
✓ Admin pages accessible
✓ Settings save/load correctly
```

---

## 📦 DEPENDENCIES & REQUIREMENTS

### PHP Libraries (to install via Composer)
```json
{
  "twig/twig": "^3.0",           // For email templates
  "mpdf/mpdf": "^8.0",           // For PDF generation
  "guzzlehttp/guzzle": "^7.0",   // For HTTP requests
  "phpmailer/phpmailer": "^6.0"  // Already in WP
}
```

### JavaScript Libraries (via CDN)
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.0.0"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
```

### WordPress Requirements
```
✓ WordPress 5.0+
✓ PHP 7.4+
✓ MySQL 5.7+ or MariaDB 10.2+
✓ WP-Cron enabled (for scheduled reports)
```

---

## 📊 TIME ALLOCATION

| Phase | Task | Hours | Status |
|-------|------|-------|--------|
| Setup | Roadmap & Planning | 1 | ✅ DONE |
| Database | Migrations & Schema | 2 | 🔴 PENDING |
| Core Logic | Attribution & APIs | 12 | 🔴 PENDING |
| Admin UI | Dashboard & Pages | 5 | 🔴 PENDING |
| Testing | Unit & Integration Tests | 3 | 🔴 PENDING |
| Docs | Documentation & Guides | 2 | 🔴 PENDING |
| Deploy | Deployment & Verification | 1 | 🔴 PENDING |
| **TOTAL** | **Option E Complete** | **26** | **🔴 PENDING** |

---

## 🚀 NEXT STEPS (START NOW)

### Immediate Actions:
1. **✅ Phase 1:** Create database migrations
   - File: `/includes/database/migrations/xxxx_create_attribution_tables.php`
   - Create all 5 tables
   - Run Prisma migrate

2. **⏳ Phase 2:** Implement core components
   - Start with `class-attribution-tracker.php`
   - Then `class-attribution-models.php`
   - Then `class-analytics-queries.php`
   - Then conversion API classes

3. **⏳ Phase 3:** Build admin UI
   - Create dashboard widget
   - Create reports page
   - Create settings form

4. **⏳ Phase 4:** Add tests & docs
   - Write PHPUnit tests
   - Write setup documentation

5. **⏳ Phase 5:** Deploy to production
   - Run migrations
   - Configure API keys
   - Verify functionality
   - Monitor error logs

---

## 📞 SUPPORT & ROLLBACK

### If Issues Occur:
1. Check logs: `/wp-content/debug.log`
2. Check API logs: `wp_edubot_api_logs` table
3. Rollback: `prisma migrate resolve --rolled-back <migration>`

### Pre-Deployment Checklist:
- ✓ All tests passing
- ✓ Migrations tested on dev
- ✓ API keys configured
- ✓ Database backups created
- ✓ Rollback plan documented
- ✓ Team notified of changes

---

**Status:** 📋 Roadmap Complete - Ready for Implementation  
**Next Action:** Begin Phase 1 (Database Migrations)  
**Timeline:** 22-28 hours to completion  
