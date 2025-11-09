# 📊 EduBot Pro Database Tables Reference

**Created:** November 6, 2025  
**Version:** 1.0  
**Purpose:** Complete documentation of all EduBot database tables and their functions

---

## 📋 Table Overview

| Table Name | Purpose | Type | Records |
|-----------|---------|------|---------|
| `wp_edubot_api_integrations` | API integration settings & credentials | Configuration | Varies |
| `wp_edubot_api_logs` | Log of all API calls (inbound/outbound) | Logging | Thousands |
| `wp_edubot_applications` | School applications from chatbot | Transactional | Thousands |
| `wp_edubot_attribution_journeys` | Student journey tracking (multi-touch) | Transactional | Thousands |
| `wp_edubot_attribution_sessions` | Chat sessions for analytics | Transactional | Thousands |
| `wp_edubot_attribution_touchpoints` | Individual interactions/touchpoints | Transactional | Thousands |
| `wp_edubot_conversions` | Lead conversion tracking | Transactional | Thousands |
| `wp_edubot_logs` | General application logs | Logging | Thousands |
| `wp_edubot_mcb_settings` | MyClassBoard integration settings | Configuration | 1 (singleton) |
| `wp_edubot_mcb_sync_log` | MCB sync attempt logs | Logging | Thousands |
| `wp_edubot_report_schedules` | Email report scheduling config | Configuration | Varies |
| `wp_edubot_school_configs` | School-specific settings | Configuration | 1-N |
| `wp_edubot_visitors` | Website visitor tracking | Transactional | Thousands |
| `wp_edubot_visitor_analytics` | Visitor behavior analytics | Transactional | Thousands |

---

## 🎯 DETAILED TABLE BREAKDOWN

### 1. **wp_edubot_api_integrations** 
**Purpose:** Store API integration configurations and credentials  
**Type:** Configuration  
**Scope:** System-wide  

**Typical Columns:**
```
id              - Unique integration ID
integration_type - (whatsapp, google, facebook, mcb, etc)
api_key         - API credential
api_secret      - Secret key
is_active       - Enable/disable flag
created_at      - Setup timestamp
```

**Used When:**
- Initializing API connections
- Authenticating external platforms
- Managing multiple integration credentials
- Checking which APIs are active

**Related Features:**
- WhatsApp integration
- Google Lead Forms
- Facebook Lead Ads
- MyClassBoard sync

---

### 2. **wp_edubot_api_logs**
**Purpose:** Audit trail of all API communications (both directions)  
**Type:** Logging/Audit  
**Scope:** System-wide  
**Retention:** Long-term (for compliance)  

**Typical Columns:**
```
id              - Log entry ID
api_name        - Which API (whatsapp, google, mcb)
request_type    - GET, POST, etc
endpoint        - API endpoint called
request_body    - What was sent
response_code   - HTTP status (200, 401, 500)
response_body   - What was returned
error_message   - If failed, error text
timestamp       - When it happened
```

**Used When:**
- Debugging API issues
- Tracing failed lead submissions
- Auditing system behavior
- Performance analysis
- Compliance checking

**Example Scenarios:**
- "Why didn't the enquiry sync to MCB?" → Check API logs
- "When did that Google Lead come in?" → Check API logs
- "Did WhatsApp integration fail?" → Check API logs

---

### 3. **wp_edubot_applications**
**Purpose:** School applications submitted through the chatbot  
**Type:** Transactional/Core Data  
**Scope:** Per-enquiry records  
**Growth:** High (100s-1000s per month)  

**Typical Columns:**
```
id              - Application ID
enquiry_id      - Link to parent enquiry
student_name    - Applicant name
class_applied   - Grade/class
email           - Contact email
phone           - Contact number
parent_name     - Parent/guardian
school_id       - Which school
submission_date - When submitted
status          - (pending, accepted, rejected)
document_urls   - Links to uploaded files
```

**Used When:**
- Processing school admissions
- Tracking applications workflow
- Generating application reports
- Following up with applicants
- Integration with school systems

**Related Features:**
- Application form in chatbot
- Document uploads
- Application status tracking
- School-wise reports

---

### 4. **wp_edubot_attribution_journeys**
**Purpose:** Track the complete customer journey from first contact to conversion  
**Type:** Analytics/Attribution  
**Scope:** Per-customer multi-touch record  
**Growth:** High (matches enquiry volume)  

**Typical Columns:**
```
id              - Journey ID
enquiry_id      - Which enquiry
journey_stages  - Array of touchpoints
first_touch     - First interaction source
last_touch      - Last interaction before conversion
multi_touch     - All touchpoint sources
touchpoint_count - How many interactions
conversion_date - When they converted
conversion_value - If applicable
channel_sequence - Path: chatbot → whatsapp → call → etc
```

**Used When:**
- Attribution modeling (which channel gets credit?)
- Understanding conversion paths
- Multi-channel campaign analysis
- ROI calculation
- Marketing optimization

**Example Scenario:**
```
Journey: 
  1. First touch: Google Ads (May 1)
  2. Second: Website (May 2)
  3. Third: Chatbot (May 3)
  4. Conversion: WhatsApp enquiry (May 4)
  
Question: Which channel should get credit?
This table helps answer that.
```

---

### 5. **wp_edubot_attribution_sessions**
**Purpose:** Track individual chat/interaction sessions for analytics  
**Type:** Analytics/Session Tracking  
**Scope:** Per-session records  
**Growth:** Very high (multiple sessions per enquiry)  

**Typical Columns:**
```
id              - Session ID
visitor_id      - Which visitor
session_start   - When session began
session_end     - When session ended
session_duration - How long (in seconds)
messages_count  - How many messages
enquiry_created - Did this session result in enquiry?
enquiry_id      - Link to enquiry if created
device          - Mobile/Desktop
browser         - Browser type
ip_address      - Visitor IP
location        - Geographic location
```

**Used When:**
- Analyzing chatbot engagement
- Session duration metrics
- Conversion rate by session
- User behavior analysis
- Identifying drop-off points

**Example Metric:**
```
Average session duration: 5 minutes
Sessions that converted: 23%
Mobile vs Desktop conversion: 15% vs 28%
```

---

### 6. **wp_edubot_attribution_touchpoints**
**Purpose:** Individual touchpoint/interaction record within a journey  
**Type:** Transactional/Analytics  
**Scope:** Per-interaction (most granular)  
**Growth:** Very high (10-50+ touchpoints per journey)  

**Typical Columns:**
```
id              - Touchpoint ID
journey_id      - Which journey
enquiry_id      - Which enquiry
channel         - Where interaction occurred (chatbot, whatsapp, email, etc)
interaction_type - (message, click, form_fill, etc)
interaction_data - Details of what happened
timestamp       - Exact time
duration        - How long this interaction
user_action     - What user did
system_response - What system did
```

**Used When:**
- Detailed journey analysis
- Understanding exact interaction flow
- Identifying friction points
- Optimization opportunities
- Detailed reporting

**Example Touchpoint Sequence:**
```
1. 10:00 AM - Chatbot: User asked about Grade 10
2. 10:02 AM - Chatbot: System provided course info
3. 10:05 AM - Chatbot: User submitted enquiry
4. 10:06 AM - Email: Confirmation sent
5. 11:30 AM - WhatsApp: Follow-up message
6. 11:35 AM - WhatsApp: User replied with questions
```

---

### 7. **wp_edubot_conversions**
**Purpose:** Track lead conversions (enquiry → actual action/admission)  
**Type:** Transactional/KPI  
**Scope:** Per-conversion record  
**Growth:** High (proportional to enquiry volume)  

**Typical Columns:**
```
id              - Conversion ID
enquiry_id      - Which enquiry converted
visitor_id      - Which visitor
conversion_type - (admission, application, consultation, etc)
conversion_date - When they converted
conversion_value - Monetary value (if applicable)
conversion_source - Which channel led to conversion
conversion_steps - How many steps to convert
conversion_time - How long from first touch to conversion (days)
status          - (confirmed, pending, completed)
notes           - Additional context
```

**Used When:**
- Calculating conversion rates
- ROI analysis
- Revenue tracking
- Sales pipeline management
- Performance metrics

**Key Metric:**
```
Total Enquiries: 1000
Conversions: 250
Conversion Rate: 25%
Average Value: $500
Total Revenue: $125,000
```

---

### 8. **wp_edubot_logs**
**Purpose:** General application logs for debugging and monitoring  
**Type:** Logging/Debugging  
**Scope:** System-wide  
**Retention:** Short-medium term (for troubleshooting)  

**Typical Columns:**
```
id              - Log ID
log_level       - (error, warning, info, debug)
category        - Component (chatbot, api, email, etc)
message         - What happened
context         - Additional details
user_id         - User involved (if applicable)
enquiry_id      - Enquiry involved (if applicable)
timestamp       - When it happened
file            - PHP file where logged
line            - Line number
```

**Used When:**
- Troubleshooting system issues
- Debugging chatbot problems
- Monitoring application health
- Performance investigation
- Error analysis

**Common Log Types:**
```
ERROR: Database connection failed
WARNING: MCB API timeout, retrying...
INFO: Enquiry 12345 created successfully
DEBUG: Form validation - missing phone field
```

---

### 9. **wp_edubot_mcb_settings**
**Purpose:** Store MyClassBoard integration configuration  
**Type:** Configuration (Singleton)  
**Scope:** System-wide  
**Records:** Typically 1 (one configuration per system)  

**Typical Columns/Fields:**
```
enabled               - Is MCB sync active?
api_key              - MCB API key
access_token         - MCB authentication token
api_url              - MCB API endpoint
organization_id      - MCB Organization (usually 21)
branch_id            - MCB Branch (usually 113)
sync_enabled         - Enable sync?
sync_new_enquiries   - Auto-sync new enquiries?
sync_updates         - Sync updates to existing records?
auto_sync            - Real-time sync?
test_mode            - Test vs production?
timeout              - API timeout (seconds)
retry_attempts       - How many retries on failure?
lead_source_mapping  - JSON array of source → MCB ID mappings
```

**Used When:**
- Configuring MCB integration
- Enabling/disabling MCB sync
- Changing MCB credentials
- Updating lead source mappings

**Current Configuration (29 Lead Sources):**
```
chatbot         → 273
whatsapp        → 273
website         → 231
facebook        → 272
google_search   → 269
... (25 more sources)
```

---

### 10. **wp_edubot_mcb_sync_log**
**Purpose:** Track all attempts to sync enquiries to MyClassBoard  
**Type:** Logging/Audit  
**Scope:** Per-sync-attempt record  
**Growth:** High (one entry per enquiry sync attempt)  

**Typical Columns:**
```
id              - Log ID
enquiry_id      - Which enquiry was synced
request_data    - JSON of what was sent to MCB
response_data   - JSON of MCB response
success         - (1 = success, 0 = failed)
error_message   - If failed, why
created_at      - When sync attempted
```

**Used When:**
- Troubleshooting MCB sync failures
- Auditing sync history
- Retry/manual sync
- Sync statistics and reporting
- Identifying sync bottlenecks

**Example:**
```
Sync Attempt: 5:30 PM
Enquiry: 12345 (John Doe)
Status: SUCCESS
MCB Enquiry ID: ENQ-98765
```

**Used by:**
- `get_sync_stats()` - Shows 24 sync stats
- `get_recent_sync_logs()` - Shows last 20 syncs
- Manual troubleshooting

---

### 11. **wp_edubot_report_schedules**
**Purpose:** Schedule automated email reports  
**Type:** Configuration  
**Scope:** System-wide  
**Records:** Varies (multiple schedule configs)  

**Typical Columns:**
```
id              - Schedule ID
report_type     - (daily, weekly, monthly)
frequency       - (every day, every Monday, 1st of month)
time_to_send    - What time (e.g., 9:00 AM)
recipient_email - Where to send
metrics_included - Which metrics in report
is_active       - Enabled/disabled
last_sent       - When last report sent
next_scheduled  - When next report scheduled
```

**Used When:**
- Setting up automated reports
- Daily/weekly/monthly email reports
- Performance dashboards
- Executive summaries
- Scheduled analytics delivery

**Example Reports:**
```
- Daily: Enquiry count, conversion rate, lead source breakdown
- Weekly: Performance trends, source efficiency, cost per lead
- Monthly: Revenue, ROI, trend analysis, forecast
```

---

### 12. **wp_edubot_school_configs**
**Purpose:** Store school-specific configurations and settings  
**Type:** Configuration  
**Scope:** Per-school  
**Records:** 1 per school in system  

**Typical Columns:**
```
id              - Config ID
school_id       - Which school
school_name     - School name
api_key         - School-specific API key
custom_fields   - JSON of custom form fields
branding         - Logo, colors, etc
whatsapp_number - School WhatsApp number
contact_email   - School email
timezone        - School timezone
language        - Preferred language
academic_year   - Current academic year
grades_offered  - Which grades available
fees            - Fee structure
```

**Used When:**
- Multi-school setup
- Per-school customization
- School-specific branding
- Different fee structures
- Multiple WhatsApp numbers

---

### 13. **wp_edubot_visitors**
**Purpose:** Track website/chatbot visitors  
**Type:** Transactional/Tracking  
**Scope:** Per-visitor record  
**Growth:** Very high (100s-1000s per day)  

**Typical Columns:**
```
id              - Visitor ID
visitor_cookie  - Unique visitor identifier
ip_address      - Visitor IP
device_type     - Mobile/Desktop/Tablet
browser         - Browser name/version
os              - Operating system
location        - Geographic location (city, country)
first_visit     - When first visited
last_visit      - Most recent visit
visit_count     - How many times visited
referrer        - Where they came from
utm_source      - Campaign source
utm_medium      - Campaign medium
utm_campaign    - Campaign name
enquiry_id      - Did they create enquiry?
```

**Used When:**
- Visitor tracking
- UTM parameter capture
- Traffic source analysis
- Visitor behavior analysis
- Remarketing lists
- Attribution modeling

**Example Visitor:**
```
ID: VIS-12345
IP: 203.45.67.89
Device: Mobile (iPhone)
Location: Mumbai, India
First Visit: Oct 15, 2025
Last Visit: Nov 6, 2025
Visits: 7
Source: google_search
Campaign: back-to-school-2025
Enquiry Created: Yes (ENQ-9876)
```

---

### 14. **wp_edubot_visitor_analytics**
**Purpose:** Aggregated visitor behavior and analytics  
**Type:** Analytics  
**Scope:** Aggregated/Summary data  
**Growth:** Controlled (daily/weekly summaries)  

**Typical Columns:**
```
id              - Record ID
date            - Analysis date
visitor_count   - Unique visitors
page_views      - Total page views
avg_session_duration - Average time on site
bounce_rate     - % who left without action
conversion_rate - % who created enquiry
top_referrer    - Main traffic source
top_campaign    - Main campaign
top_device      - Most common device
top_location    - Most common location
traffic_trend   - Up/down trend
```

**Used When:**
- Daily/weekly analytics dashboards
- Traffic trend analysis
- Performance monitoring
- Campaign effectiveness
- Device/location insights
- Executive reporting

**Example Daily Analytics:**
```
Date: Nov 6, 2025
Unique Visitors: 423
Page Views: 1,247
Avg Session: 4 min 32 sec
Bounce Rate: 32%
Conversion Rate: 18%
Top Source: Google Organic (45%)
Top Campaign: back-to-school (28%)
```

---

## 📊 Data Flow Diagram

```
VISITOR TRACKING
┌─────────────────────────────────────────────┐
│ wp_edubot_visitors                          │
│ (Track each unique visitor)                 │
└──────────────┬──────────────────────────────┘
               │
               ├─ Logs to → wp_edubot_visitor_analytics (summary)
               │
               └─ Creates → ENQUIRY
                            │
                            ▼
┌──────────────────────────────────────────────────────────────┐
│ ENQUIRY CREATION & PROCESSING                                │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│ Enquiry Data                                                 │
│ ├─ wp_edubot_applications (if applying)                     │
│ ├─ wp_edubot_conversions (if converts)                      │
│ └─ linked to wp_edubot_visitors (source)                    │
│                                                               │
└──────────────┬───────────────────────────────────────────────┘
               │
               ├─ Tracked via → wp_edubot_attribution_journeys
               │               (complete customer journey)
               │
               ├─ Session records → wp_edubot_attribution_sessions
               │                   (individual chat sessions)
               │
               ├─ Individual interactions → wp_edubot_attribution_touchpoints
               │                           (each touchpoint)
               │
               └─ Events logged → wp_edubot_logs (general logging)

API INTEGRATIONS
┌──────────────────────────────────────────┐
│ wp_edubot_api_integrations               │
│ (Store API credentials & settings)       │
└──────────────┬───────────────────────────┘
               │
               ├─ All API calls logged → wp_edubot_api_logs
               │
               ├─ WhatsApp calls MCB
               │   └─ Logged in → wp_edubot_mcb_sync_log
               │
               ├─ Google Lead Forms
               ├─ Facebook Lead Ads
               └─ Other integrations

CONFIGURATION
┌──────────────────────────────────────────┐
│ wp_edubot_mcb_settings                   │
│ (MCB integration settings)               │
│ + wp_edubot_school_configs               │
│ (Per-school settings)                    │
│ + wp_edubot_report_schedules             │
│ (Automated report config)                │
└──────────────────────────────────────────┘
```

---

## 🔑 Key Relationships

```
wp_edubot_visitors (1)
    ↓ creates
wp_edubot_attribution_journeys (1)
    ├─ has many → wp_edubot_attribution_sessions
    ├─ has many → wp_edubot_attribution_touchpoints
    └─ creates → wp_edubot_conversions, wp_edubot_applications

wp_edubot_mcb_settings (1 config)
    ├─ syncs via → wp_edubot_mcb_sync_log (many logs)
    └─ contains → lead_source_mapping (29 sources)

wp_edubot_api_integrations (many)
    └─ logs via → wp_edubot_api_logs (many logs)

wp_edubot_school_configs (1+ per school)
    └─ used by various tables for school-specific data
```

---

## 💾 Data Retention Strategy

| Table | Retention | Reason |
|-------|-----------|--------|
| `wp_edubot_visitors` | 6-12 months | Business intelligence |
| `wp_edubot_attribution_journeys` | Permanent | Core business data |
| `wp_edubot_conversions` | Permanent | Revenue tracking |
| `wp_edubot_api_logs` | 3-6 months | Debugging, compliance |
| `wp_edubot_logs` | 1-3 months | Troubleshooting |
| `wp_edubot_mcb_sync_log` | 6-12 months | Audit trail |
| `wp_edubot_school_configs` | Permanent | Settings |
| `wp_edubot_mcb_settings` | Permanent | Settings |
| `wp_edubot_report_schedules` | Permanent | Settings |

---

## 🎯 Quick Reference: When to Use Each Table

**Need to track visitor?** → `wp_edubot_visitors`  
**Need to know conversion path?** → `wp_edubot_attribution_journeys` + `wp_edubot_attribution_touchpoints`  
**Need to track session duration?** → `wp_edubot_attribution_sessions`  
**Need to audit API calls?** → `wp_edubot_api_logs` or `wp_edubot_mcb_sync_log`  
**Need to check if synced to MCB?** → `wp_edubot_mcb_sync_log`  
**Need to troubleshoot application error?** → `wp_edubot_logs`  
**Need school-specific settings?** → `wp_edubot_school_configs`  
**Need to set up reports?** → `wp_edubot_report_schedules`  
**Need to see all applications?** → `wp_edubot_applications`  
**Need conversion metrics?** → `wp_edubot_conversions`  

---

**Status:** ✅ Complete Reference  
**Last Updated:** November 6, 2025  
**Total Tables Documented:** 14
