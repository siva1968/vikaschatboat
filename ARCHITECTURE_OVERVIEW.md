# 🏗️ EduBot Pro v1.3.2 - System Architecture

**Status:** Production Ready  
**Version:** 1.3.2  
**Last Updated:** November 3, 2025

---

## 📋 Table of Contents

1. [High-Level Architecture](#high-level-architecture)
2. [Core Components](#core-components)
3. [Plugin Structure](#plugin-structure)
4. [Class Hierarchy](#class-hierarchy)
5. [Data Flow](#data-flow)
6. [Integration Points](#integration-points)

---

## 🎯 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        WORDPRESS CORE                           │
│  (Hooks, Actions, Filters, Admin Interface, User Management)   │
└────────────────────┬────────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
┌───────▼─────────┐      ┌───────▼─────────┐
│   ADMIN SIDE    │      │   PUBLIC SIDE   │
│                 │      │                 │
│ • Settings      │      │ • Chatbot UI    │
│ • Dashboard     │      │ • Form Display  │
│ • Analytics     │      │ • AJAX Handler  │
│ • Applications  │      │ • Enquiry Mgmt  │
└─────────────────┘      └─────────────────┘
        │                         │
        └────────────┬────────────┘
                     │
        ┌────────────▼────────────────────────────────────┐
        │         EDUBOT PRO CORE ENGINE                 │
        │  ┌──────────────────────────────────────────┐  │
        │  │  EduBot_Core (Main Orchestrator)        │  │
        │  │  • Loader Management                     │  │
        │  │  • Hook Registration                     │  │
        │  │  • Dependency Loading                    │  │
        │  └──────────────────────────────────────────┘  │
        └────────────┬────────────────────────────────────┘
                     │
        ┌────────────┴────────────────────────────────────┐
        │                                                 │
   ┌────▼────────┐  ┌────────────┐  ┌───────────────┐   │
   │ SHORTCODE    │  │ CHATBOT    │  │ DATABASE      │   │
   │ MANAGER      │  │ ENGINE     │  │ MANAGER       │   │
   ├──────────────┤  ├────────────┤  ├───────────────┤   │
   │ • Form Flow  │  │ • Responses│  │ • Enquiries   │   │
   │ • Parsing    │  │ • AI Logic │  │ • Applications
   │ • Sessions   │  │ • State    │  │ • Analytics   │   │
   │ • Validation │  │   Management │ • Migrations   │  │
   └──────────────┘  └────────────┘  └───────────────┘   │
        │                    │                 │          │
        └────────┬───────────┴─────────────────┘          │
                 │                                        │
    ┌────────────▼──────────────────┐                    │
    │  BUSINESS LOGIC LAYER         │                    │
    ├───────────────────────────────┤                    │
    │ • Security Manager             │                    │
    │ • School Config                │                    │
    │ • API Integrations             │                    │
    │ • Notification Manager         │                    │
    │ • Branding Manager             │                    │
    │ • Visitor Analytics            │                    │
    └────────────┬────────────────────┘                    │
                 │                                        │
        ┌────────▼────────────────────┐                   │
        │ EXTERNAL INTEGRATIONS       │                   │
        ├─────────────────────────────┤                   │
        │ • OpenAI API (ChatGPT)      │                   │
        │ • WhatsApp Business API     │                   │
        │ • Email Service             │                   │
        │ • SMS Gateway (Pending)     │                   │
        │ • Analytics Platforms       │                   │
        └─────────────────────────────┘                   │
                                                          │
        ┌─────────────────────────────────────────────┐   │
        │  DATA LAYER                                 │   │
        ├─────────────────────────────────────────────┤   │
        │  WordPress Database (MySQL)                 │   │
        │  • wp_edubot_enquiries                      │   │
        │  • wp_edubot_applications                   │   │
        │  • wp_edubot_analytics                      │   │
        │  • wp_edubot_sessions                       │   │
        │  • wp_edubot_security_logs                  │   │
        │  • wp_edubot_visitor_tracking               │   │
        └─────────────────────────────────────────────┘   │
```

---

## 🔧 Core Components

### 1. **EduBot_Core** (Main Orchestrator)
**Location:** `includes/class-edubot-core.php`

**Responsibilities:**
- Load plugin dependencies
- Register admin and public hooks
- Initialize plugin services
- Set plugin locale for internationalization

**Key Methods:**
```php
public function __construct()      // Initialize plugin
private function load_dependencies() // Load required files
private function set_locale()      // Setup i18n
private function define_admin_hooks() // Admin registration
private function define_public_hooks() // Frontend registration
public function run()              // Execute plugin
```

---

### 2. **EduBot_Loader** (Hook Manager)
**Location:** `includes/class-edubot-loader.php`

**Responsibilities:**
- Register WordPress actions
- Register WordPress filters
- Manage hook execution queue
- Organize hooks by priority

**Key Methods:**
```php
public function add_action()    // Register action hook
public function add_filter()    // Register filter hook
public function run()           // Execute all registered hooks
```

---

### 3. **EduBot_Shortcode** (Form & Flow Manager)
**Location:** `includes/class-edubot-shortcode.php`  
**Lines of Code:** 5,649+ (Primary business logic)

**Responsibilities:**
- Render chatbot widget on frontend
- Handle user input and messages
- Parse personal information
- Parse academic information
- Manage conversation sessions
- Process final submission
- Send confirmation emails

**Key Methods:**
```php
public function init_shortcode()                      // Initialize shortcode
private function render_chatbot_html()                // Render UI
private function handle_chatbot_response()            // AJAX handler
private function generate_response()                  // Process message
private function handle_admission_flow_safe()         // Admission flow
private function parse_personal_info()                // Extract personal data
private function parse_academic_info()                // Extract academic data
private function process_final_submission()           // Save to database
private function send_parent_confirmation_email()    // Email notification
```

---

### 4. **EduBot_Chatbot_Engine** (AI & Response Logic)
**Location:** `includes/class-chatbot-engine.php`

**Responsibilities:**
- Manage conversation state
- Generate contextual responses
- Handle flow transitions
- Build application summaries
- Submit applications

**Key Methods:**
```php
public function process_message()                 // Main processor
private function handle_conversation_flow()       // State machine
private function handle_greeting()                // First interaction
private function handle_admission_info_collection() // Admission flow
private function submit_application()             // Save enquiry
```

---

### 5. **EduBot_Database_Manager** (Data Access)
**Location:** `includes/class-database-manager.php`

**Responsibilities:**
- Save enquiry data
- Query applications
- Update notification status
- Manage database transactions
- Handle data migrations

**Key Methods:**
```php
public function insert_enquiry()               // Save new enquiry
public function get_enquiries()                // Fetch enquiries
public function update_notification_status()   // Mark email/WhatsApp sent
public function save_to_applications_table()   // Save application
```

---

### 6. **EduBot_Security_Manager** (Security)
**Location:** `includes/class-security-manager.php`

**Responsibilities:**
- Nonce verification
- Input sanitization
- Output escaping
- CSRF protection
- Rate limiting

**Key Methods:**
```php
public static function verify_nonce()   // Check nonce
public static function sanitize_input() // Clean user input
public static function escape_output()  // Escape for display
```

---

### 7. **EduBot_School_Config** (Settings)
**Location:** `includes/class-school-config.php`

**Responsibilities:**
- Store school information
- Manage configuration
- Handle academic settings
- Board and grade definitions

**Key Methods:**
```php
public static function getInstance()    // Singleton instance
public function get_config()           // Get all settings
public function update_config()        // Update settings
```

---

### 8. **EduBot_Admin** (Admin Interface)
**Location:** `admin/class-edubot-admin.php`

**Responsibilities:**
- Render admin pages
- Handle settings updates
- Display analytics
- Manage applications
- Save API keys

**Key Methods:**
```php
public function enqueue_styles()          // Admin CSS
public function enqueue_scripts()         // Admin JS
public function add_admin_menu()          // Admin menu
public function display_admin_page()      // Main page
public function save_settings()           // AJAX save
```

---

### 9. **EduBot_Public** (Frontend Rendering)
**Location:** `public/class-edubot-public.php`

**Responsibilities:**
- Load frontend CSS/JS
- Register shortcodes
- Render chatbot widget
- Handle AJAX requests

**Key Methods:**
```php
public function enqueue_styles()          // Frontend CSS
public function enqueue_scripts()         // Frontend JS
public function register_shortcodes()     // Register [edubot_chatbot]
public function render_chatbot()          // Render widget HTML
```

---

### 10. **Additional Managers**

#### EduBot_Notification_Manager
- WhatsApp notifications
- Email sending
- SMS management
- Template handling

#### EduBot_Branding_Manager
- Color management
- Logo configuration
- Custom CSS injection
- Styling options

#### EduBot_Visitor_Analytics
- Track visitor interactions
- Count chatbot views
- Measure engagement
- Generate reports

---

## 📁 Plugin Structure

```
edubot-pro/
├── edubot-pro.php                    # Main plugin file
├── uninstall.php                     # Plugin uninstall
├── readme.txt                        # WordPress.org readme
├── phpunit.xml                       # Unit test config
│
├── includes/                         # Core business logic
│   ├── class-edubot-core.php        # Main orchestrator
│   ├── class-edubot-loader.php      # Hook manager
│   ├── class-edubot-i18n.php        # Internationalization
│   ├── class-edubot-activator.php   # Activation handler
│   ├── class-edubot-deactivator.php # Deactivation handler
│   ├── class-edubot-constants.php   # Plugin constants
│   ├── class-edubot-shortcode.php   # Shortcode & form (5649 lines)
│   ├── class-chatbot-engine.php     # AI & response logic
│   ├── class-database-manager.php   # Data access layer
│   ├── class-security-manager.php   # Security functions
│   ├── class-school-config.php      # School settings
│   ├── class-notification-manager.php # Notifications (Email/WhatsApp/SMS)
│   ├── class-branding-manager.php   # Branding & styling
│   ├── class-visitor-analytics.php  # Analytics tracking
│   ├── class-edubot-health-check.php # System diagnostics
│   ├── class-edubot-autoloader.php  # PSR-4 autoloader
│   └── class-api-integrations.php   # External APIs
│
├── admin/                            # Admin functionality
│   ├── class-edubot-admin.php       # Admin interface
│   ├── css/
│   │   └── edubot-admin.css         # Admin styles
│   ├── js/
│   │   └── edubot-admin.js          # Admin scripts
│   └── views/                       # Admin pages
│       ├── dashboard.php            # Main dashboard
│       ├── school-settings.php      # School config
│       ├── api-integrations.php     # API settings
│       ├── analytics.php            # Analytics page
│       ├── applications-list.php    # Applications
│       └── settings.php             # General settings
│
├── public/                          # Frontend functionality
│   ├── class-edubot-public.php     # Public interface
│   ├── js/
│   │   └── edubot-public.js        # Frontend script
│   ├── css/
│   │   └── edubot-public.css       # Frontend styles
│   └── partials/
│       └── chatbot-widget.php      # Chatbot HTML
│
├── assets/                          # Static resources
│   ├── js/
│   │   ├── frontend.js             # Frontend utilities
│   │   └── chatbot.js              # Chatbot logic
│   ├── css/
│   │   ├── frontend.css            # Frontend styles
│   │   └── chatbot.css             # Chatbot styles
│   └── images/
│       └── school-logo.png         # Placeholder
│
├── languages/                       # Translations
│   └── edubot-pro.pot              # Translation template
│
└── docs/                            # Documentation
    ├── INSTALLATION_GUIDE.md       # Setup guide
    ├── USER_MANUAL.md              # Usage guide
    ├── API_REFERENCE.md            # API docs
    └── CHANGELOG.md                # Version history
```

---

## 🔗 Class Hierarchy & Dependencies

```
EduBot_Core
├─ depends on → EduBot_Loader
├─ depends on → EduBot_Admin
├─ depends on → EduBot_Public
├─ depends on → EduBot_i18n
├─ depends on → EduBot_Shortcode
└─ depends on → EduBot_Chatbot_Engine

EduBot_Shortcode
├─ uses → EduBot_Database_Manager
├─ uses → EduBot_Security_Manager
├─ uses → EduBot_School_Config
├─ uses → Notification_Manager
├─ uses → EduBot_Branding_Manager
├─ uses → EduBot_Chatbot_Engine
└─ uses → EduBot_Visitor_Analytics

EduBot_Chatbot_Engine
├─ uses → EduBot_School_Config
├─ uses → EduBot_Security_Manager
└─ uses → Notification_Manager

EduBot_Admin
├─ uses → EduBot_Security_Manager
├─ uses → EduBot_Database_Manager
├─ uses → EduBot_School_Config
└─ uses → EduBot_Visitor_Analytics

EduBot_Public
├─ uses → EduBot_Shortcode
├─ uses → EduBot_Branding_Manager
└─ uses → EduBot_Visitor_Analytics

EduBot_Database_Manager
└─ depends on → WordPress WPDB Class

Notification_Manager
├─ uses → EduBot_School_Config
├─ depends on → WhatsApp Business API
└─ depends on → WordPress wp_mail()
```

---

## 🔄 Data Flow Architecture

```
USER INPUT
    ↓
FRONTEND (edubot-public.js)
    ├─ Captures message from chat input
    ├─ Sends AJAX POST to wp-admin/admin-ajax.php
    └─ Updates session ID if provided
    ↓
WORDPRESS AJAX HANDLER (wp_ajax_edubot_chatbot_response)
    ↓
EduBot_Shortcode::handle_chatbot_response()
    ├─ Verify nonce
    ├─ Sanitize input
    ├─ Log request
    └─ Call generate_response()
    ↓
EduBot_Shortcode::generate_response()
    ├─ Parse personal info (name, email, phone)
    ├─ Parse academic info (grade, board)
    ├─ Check session state
    ├─ Route to appropriate handler:
    │   ├─ handle_admission_flow_safe() → Personal info flow
    │   ├─ handle_admission_flow_safe() → Academic info flow
    │   ├─ process_final_submission() → Finalization
    │   └─ generate_regular_response() → General queries
    └─ Return response
    ↓
EduBot_Chatbot_Engine::process_message() [if called]
    ├─ Get school config
    ├─ Manage conversation state
    ├─ Generate context-aware response
    └─ Return options for user
    ↓
DATABASE OPERATIONS [if applicable]
    ├─ EduBot_Database_Manager::insert_enquiry()
    ├─ EduBot_Database_Manager::save_to_applications_table()
    └─ Update wp_edubot_enquiries table
    ↓
NOTIFICATIONS [if applicable]
    ├─ send_parent_confirmation_email()
    ├─ send_school_enquiry_notification()
    ├─ Notification_Manager::send_whatsapp()
    └─ Notification_Manager::send_sms()
    ↓
RESPONSE FORMATTING
    ├─ Format JSON response
    ├─ Include message text
    ├─ Include session data
    └─ Include action buttons/options
    ↓
RETURN TO FRONTEND (wp_send_json_success)
    ↓
FRONTEND RENDERING
    ├─ Display bot message
    ├─ Update session ID
    ├─ Show action buttons
    ├─ Update UI state
    └─ Log analytics

STORAGE
    ├─ Database: wp_edubot_enquiries
    ├─ Sessions: WordPress transients
    ├─ Logs: WP Debug log
    └─ Analytics: wp_edubot_analytics
```

---

## 🔌 Integration Points

### 1. **OpenAI API** (ChatGPT)
- **Purpose:** AI-powered responses for general queries
- **Configuration:** `AI API Settings` in Admin
- **Methods:** `EduBot_Chatbot_Engine::handle_ai_response()`

### 2. **WhatsApp Business API**
- **Purpose:** Send admission confirmations and notifications
- **Configuration:** `WhatsApp Settings` in Admin
- **Methods:** `Notification_Manager::send_whatsapp()`

### 3. **Email Service**
- **Purpose:** Send confirmation emails to parents and school
- **Configuration:** Uses WordPress `wp_mail()`
- **Methods:** 
  - `send_parent_confirmation_email()`
  - `send_school_enquiry_notification()`

### 4. **SMS Gateway** (Planned)
- **Purpose:** Send SMS notifications
- **Configuration:** `SMS Settings` in Admin
- **Status:** Framework in place

### 5. **Analytics** (Google Analytics, etc.)
- **Purpose:** Track visitor interactions
- **Configuration:** `Visitor_Analytics` class
- **Methods:** `EduBot_Visitor_Analytics::track_event()`

---

## 🛡️ Security Architecture

```
INPUT LAYER
    ├─ Nonce verification (CSRF protection)
    ├─ Capability checks (User roles)
    └─ IP whitelist (Optional)
    ↓
SANITIZATION LAYER
    ├─ sanitize_text_field()
    ├─ sanitize_email()
    ├─ sanitize_url()
    └─ wp_kses_post()
    ↓
VALIDATION LAYER
    ├─ Email validation
    ├─ Phone validation
    ├─ Data type checking
    └─ Required field checking
    ↓
PROCESSING LAYER
    ├─ SQL prepared statements
    ├─ Parameterized queries
    └─ Escape all user data
    ↓
OUTPUT LAYER
    ├─ esc_html()
    ├─ esc_attr()
    ├─ esc_url()
    └─ wp_json_encode()
    ↓
LOGGING LAYER
    └─ Security events logged
```

---

## 📊 Database Schema

### wp_edubot_enquiries
```sql
CREATE TABLE wp_edubot_enquiries (
  id INT PRIMARY KEY AUTO_INCREMENT,
  enquiry_number VARCHAR(50) UNIQUE,
  student_name VARCHAR(255),
  date_of_birth DATE,
  grade VARCHAR(50),
  board VARCHAR(50),
  academic_year VARCHAR(50),
  parent_name VARCHAR(255),
  email VARCHAR(255),
  phone VARCHAR(20),
  address TEXT,
  gender VARCHAR(50),
  ip_address VARCHAR(45),
  user_agent TEXT,
  utm_data LONGTEXT,
  gclid VARCHAR(100),
  fbclid VARCHAR(100),
  click_id_data LONGTEXT,
  source VARCHAR(50),
  whatsapp_sent TINYINT(1),
  email_sent TINYINT(1),
  sms_sent TINYINT(1),
  created_at DATETIME,
  status VARCHAR(50)
);
```

### wp_edubot_applications
```sql
CREATE TABLE wp_edubot_applications (
  id INT PRIMARY KEY AUTO_INCREMENT,
  enquiry_id INT,
  enquiry_number VARCHAR(50),
  student_name VARCHAR(255),
  parent_email VARCHAR(255),
  phone VARCHAR(20),
  grade VARCHAR(50),
  board VARCHAR(50),
  status VARCHAR(50),
  created_at DATETIME,
  FOREIGN KEY (enquiry_id) REFERENCES wp_edubot_enquiries(id)
);
```

---

## 🎯 Summary

The EduBot Pro architecture is built on a **modular, layered design**:

1. **Core Layer** - EduBot_Core orchestrates plugin initialization
2. **Business Logic Layer** - Shortcode, Chatbot Engine handle workflows
3. **Data Layer** - Database Manager handles persistence
4. **Security Layer** - Security Manager enforces protection
5. **Integration Layer** - External APIs managed cleanly
6. **UI Layer** - Admin and Public interfaces separate concerns

This design ensures:
- ✅ **Maintainability** - Clear separation of concerns
- ✅ **Scalability** - Easy to add new components
- ✅ **Security** - Input validation and output escaping
- ✅ **Testability** - Components are independently testable
- ✅ **Reusability** - Shared utilities across components

