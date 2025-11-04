# 🔧 EduBot Pro - Technical Specifications

## 📋 **System Architecture Overview**

### **Platform Foundation**
- **Framework**: WordPress Plugin Architecture
- **PHP Version**: 7.4+ (Recommended: 8.0+)
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Server**: Apache/Nginx with mod_rewrite
- **Memory**: Minimum 256MB, Recommended 512MB+

### **Technology Stack**
- **Backend**: PHP 8.0, WordPress APIs, Custom Database Tables
- **Frontend**: JavaScript (ES6+), CSS3, HTML5, AJAX
- **AI Integration**: OpenAI GPT-3.5/4 API
- **Communication APIs**: Meta WhatsApp Business API, SMTP, SMS APIs
- **Security**: WordPress Nonce, Data Encryption (AES-256), Input Sanitization

---

## 🗄️ **Database Schema**

### **Core Tables**

#### **`wp_edubot_enquiries`**
```sql
CREATE TABLE wp_edubot_enquiries (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    enquiry_number VARCHAR(50) NOT NULL UNIQUE,
    student_name VARCHAR(255) NOT NULL,
    parent_name VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    grade VARCHAR(50) NOT NULL,
    board VARCHAR(50) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    submission_date DATETIME NOT NULL,
    email_sent TINYINT(1) DEFAULT 0,
    whatsapp_sent TINYINT(1) DEFAULT 0,
    sms_sent TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending',
    source VARCHAR(100) DEFAULT 'chatbot',
    site_id BIGINT(20) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_enquiry_number (enquiry_number),
    INDEX idx_submission_date (submission_date),
    INDEX idx_status (status),
    INDEX idx_site_id (site_id)
);
```

#### **`wp_edubot_sessions`**
```sql
CREATE TABLE wp_edubot_sessions (
    session_id VARCHAR(255) NOT NULL,
    flow_type VARCHAR(50) NOT NULL,
    step VARCHAR(100) NOT NULL,
    data LONGTEXT NOT NULL,
    started DATETIME NOT NULL,
    updated DATETIME NOT NULL,
    expires DATETIME NOT NULL,
    site_id BIGINT(20) DEFAULT 1,
    PRIMARY KEY (session_id),
    INDEX idx_expires (expires),
    INDEX idx_site_id (site_id)
);
```

#### **`wp_edubot_school_configs`**
```sql
CREATE TABLE wp_edubot_school_configs (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    site_id BIGINT(20) NOT NULL DEFAULT 1,
    config_data LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_site_config (site_id)
);
```

---

## 🔌 **API Integrations**

### **OpenAI Integration**
```php
// Configuration
$api_endpoint = 'https://api.openai.com/v1/chat/completions';
$model_options = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'];

// Request Structure
{
    "model": "gpt-3.5-turbo",
    "messages": [
        {"role": "system", "content": "You are an admission assistant..."},
        {"role": "user", "content": "User input"}
    ],
    "max_tokens": 500,
    "temperature": 0.7
}
```

### **WhatsApp Business API**
```php
// Meta WhatsApp Business API Endpoint
$whatsapp_endpoint = "https://graph.facebook.com/v18.0/{phone_number_id}/messages";

// Template Message Structure
{
    "messaging_product": "whatsapp",
    "to": "919876543210",
    "type": "template",
    "template": {
        "name": "admission_confirmation",
        "language": {"code": "en"},
        "components": [
            {
                "type": "body",
                "parameters": [
                    {"type": "text", "text": "John Doe"},
                    {"type": "text", "text": "ENQ2025001"}
                ]
            }
        ]
    }
}
```

### **Email API Integration**
```php
// SMTP Configuration
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'school@example.com',
    'password' => 'encrypted_password'
];

// Alternative: SendGrid/Mailgun API
$email_api_providers = ['sendgrid', 'mailgun', 'smtp'];
```

---

## 🔐 **Security Implementation**

### **Data Encryption**
```php
// API Key Encryption (AES-256-CBC)
class EduBot_Security_Manager {
    private function encrypt_data($data, $key) {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    private function decrypt_data($encrypted_data, $key) {
        $data = base64_decode($encrypted_data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}
```

### **Input Sanitization**
```php
// WordPress Security Functions
$sanitized_data = [
    'student_name' => sanitize_text_field($_POST['student_name']),
    'email' => sanitize_email($_POST['email']),
    'phone' => preg_replace('/[^0-9+\-\s]/', '', $_POST['phone']),
    'grade' => sanitize_text_field($_POST['grade'])
];

// SQL Injection Prevention
$wpdb->prepare("SELECT * FROM {$table_name} WHERE enquiry_number = %s", $enquiry_number);
```

### **Access Control**
```php
// WordPress Capabilities
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

// Nonce Verification
if (!wp_verify_nonce($_POST['nonce'], 'edubot_admin_action')) {
    wp_die('Security check failed');
}
```

---

## 🎯 **Core Classes & Architecture**

### **Main Plugin Class**
```php
class EduBot_Pro {
    private $version = '1.3.1';
    private $plugin_name = 'edubot-pro';
    
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-edubot-shortcode.php';
        require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-database-manager.php';
        require_once EDUBOT_PRO_PLUGIN_PATH . 'includes/class-api-integrations.php';
        require_once EDUBOT_PRO_PLUGIN_PATH . 'admin/class-edubot-admin.php';
    }
}
```

### **Shortcode Handler**
```php
class EduBot_Shortcode {
    private $session_manager;
    private $database_manager;
    private $api_integrations;
    
    public function handle_chatbot_response() {
        // AJAX handler for chatbot interactions
        $message = sanitize_text_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id']);
        
        $response = $this->generate_response($message, $session_id);
        wp_send_json($response);
    }
    
    private function generate_response($message, $session_id) {
        // AI-powered response generation
        // Session management
        // Flow control logic
        return $formatted_response;
    }
}
```

### **Database Manager**
```php
class EduBot_Database_Manager {
    private $wpdb;
    private $table_prefix;
    
    public function save_enquiry($enquiry_data) {
        $table_name = $this->wpdb->prefix . 'edubot_enquiries';
        
        $result = $this->wpdb->insert(
            $table_name,
            $enquiry_data,
            $this->get_data_formats($enquiry_data)
        );
        
        return $result !== false ? $this->wpdb->insert_id : false;
    }
    
    public function get_analytics_data($filters = []) {
        // Complex analytics queries
        // Data aggregation
        // Performance optimization
        return $analytics_results;
    }
}
```

---

## 📊 **Performance Specifications**

### **Response Times**
- **Chatbot Response**: < 2 seconds (including AI processing)
- **Page Load Time**: < 3 seconds (first load)
- **Database Queries**: < 100ms per query
- **API Calls**: < 5 seconds timeout with retry logic

### **Scalability Metrics**
- **Concurrent Users**: 100+ simultaneous chatbot sessions
- **Database Capacity**: 1M+ enquiry records without performance degradation
- **API Rate Limits**: Respects provider limits with queuing system
- **Memory Usage**: < 64MB per request in typical usage

### **Caching Strategy**
```php
// WordPress Transient Caching
set_transient('edubot_school_config_' . $site_id, $config_data, 12 * HOUR_IN_SECONDS);

// Object Caching for Frequent Queries
wp_cache_set('edubot_analytics_' . $cache_key, $analytics_data, 'edubot', 15 * MINUTE_IN_SECONDS);

// Database Query Optimization
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} WHERE submission_date >= %s ORDER BY id DESC LIMIT %d",
    $start_date, $limit
), ARRAY_A);
```

---

## 🔄 **WordPress Integration**

### **Hooks & Filters**
```php
// Action Hooks
add_action('init', [$this, 'init_chatbot']);
add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
add_action('wp_ajax_edubot_chat', [$this, 'handle_chatbot_response']);
add_action('wp_ajax_nopriv_edubot_chat', [$this, 'handle_chatbot_response']);

// Filter Hooks
add_filter('edubot_response_data', [$this, 'customize_response'], 10, 3);
add_filter('edubot_notification_message', [$this, 'customize_notification'], 10, 2);

// Custom Hooks for Developers
do_action('edubot_enquiry_submitted', $enquiry_id, $enquiry_data);
apply_filters('edubot_ai_prompt', $default_prompt, $context);
```

### **Shortcode Implementation**
```php
// Shortcode Registration
add_shortcode('edubot_chat', [$this, 'render_chatbot_widget']);

// Shortcode Usage
[edubot_chat theme="modern" position="bottom-right" welcome="Welcome to our school!"]
```

### **Settings API Integration**
```php
// WordPress Settings API
register_setting('edubot_settings', 'edubot_openai_api_key');
add_settings_section('edubot_ai_settings', 'AI Configuration', null, 'edubot-settings');
add_settings_field('edubot_openai_key', 'OpenAI API Key', [$this, 'render_api_key_field'], 'edubot-settings', 'edubot_ai_settings');
```

---

## 📱 **Frontend Implementation**

### **JavaScript Architecture**
```javascript
class EduBotWidget {
    constructor(options) {
        this.apiUrl = options.ajaxUrl;
        this.nonce = options.nonce;
        this.sessionId = this.generateSessionId();
        this.init();
    }
    
    async sendMessage(message) {
        const response = await fetch(this.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'edubot_chat',
                message: message,
                session_id: this.sessionId,
                nonce: this.nonce
            })
        });
        
        return response.json();
    }
    
    displayMessage(message, isUser = false) {
        // DOM manipulation for chat interface
        // Animation and UX enhancements
    }
}
```

### **CSS Framework**
```css
/* Responsive Design */
.edubot-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto;
}

@media (max-width: 768px) {
    .edubot-widget {
        bottom: 10px;
        right: 10px;
        left: 10px;
        width: auto;
    }
}

/* Animation Framework */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

---

## 🛠️ **Development & Deployment**

### **Plugin Structure**
```
edubot-pro/
├── edubot-pro.php                 # Main plugin file
├── includes/
│   ├── class-edubot-shortcode.php    # Core chatbot logic
│   ├── class-database-manager.php    # Database operations
│   ├── class-api-integrations.php    # External API handlers
│   └── class-security-manager.php    # Security utilities
├── admin/
│   ├── class-edubot-admin.php        # Admin interface
│   ├── views/                        # Admin page templates
│   └── assets/                       # Admin CSS/JS
├── public/
│   ├── css/                          # Frontend styles
│   ├── js/                           # Frontend scripts
│   └── images/                       # Plugin assets
├── languages/                        # Translation files
└── README.txt                        # WordPress plugin readme
```

### **Installation Process**
```php
register_activation_hook(__FILE__, 'edubot_activate');
register_deactivation_hook(__FILE__, 'edubot_deactivate');

function edubot_activate() {
    // Create database tables
    // Set default options
    // Schedule cron jobs
    flush_rewrite_rules();
}

function edubot_deactivate() {
    // Clean up temporary data
    // Clear scheduled events
    flush_rewrite_rules();
}
```

### **Update Mechanism**
```php
class EduBot_Updater {
    private $version;
    private $plugin_file;
    
    public function check_for_updates() {
        $current_version = get_option('edubot_version', '1.0.0');
        
        if (version_compare($current_version, $this->version, '<')) {
            $this->perform_update($current_version);
        }
    }
    
    private function perform_update($from_version) {
        // Database migrations
        // Option updates
        // File migrations
        update_option('edubot_version', $this->version);
    }
}
```

---

## 🔍 **Monitoring & Debugging**

### **Logging System**
```php
class EduBot_Logger {
    private static function write_log($message, $level = 'info') {
        if (WP_DEBUG_LOG) {
            $timestamp = current_time('Y-m-d H:i:s');
            $log_entry = "[{$timestamp}] EduBot {$level}: {$message}\n";
            
            error_log($log_entry, 3, WP_CONTENT_DIR . '/debug.log');
        }
    }
    
    public static function info($message) {
        self::write_log($message, 'INFO');
    }
    
    public static function error($message) {
        self::write_log($message, 'ERROR');
    }
}
```

### **Health Check System**
```php
public function system_health_check() {
    $health_status = [
        'database' => $this->check_database_connection(),
        'openai_api' => $this->test_openai_connection(),
        'whatsapp_api' => $this->test_whatsapp_connection(),
        'email_service' => $this->test_email_service(),
        'memory_usage' => memory_get_usage(true),
        'php_version' => PHP_VERSION
    ];
    
    return $health_status;
}
```

---

## 📋 **API Documentation**

### **REST Endpoints**
```php
// Custom REST API Endpoints
register_rest_route('edubot/v1', '/enquiries', [
    'methods' => 'GET',
    'callback' => [$this, 'get_enquiries'],
    'permission_callback' => [$this, 'check_permissions']
]);

register_rest_route('edubot/v1', '/analytics', [
    'methods' => 'GET',
    'callback' => [$this, 'get_analytics'],
    'permission_callback' => [$this, 'check_permissions']
]);

// Usage Examples
// GET /wp-json/edubot/v1/enquiries?status=pending&limit=50
// GET /wp-json/edubot/v1/analytics?period=30days&group_by=grade
```

### **Webhook Support**
```php
// Webhook Registration
add_action('edubot_enquiry_submitted', [$this, 'trigger_webhooks']);

public function trigger_webhooks($enquiry_data) {
    $webhook_urls = get_option('edubot_webhooks', []);
    
    foreach ($webhook_urls as $webhook_url) {
        wp_remote_post($webhook_url, [
            'body' => json_encode($enquiry_data),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-EduBot-Signature' => $this->generate_signature($enquiry_data)
            ]
        ]);
    }
}
```

---

## 🎯 **Technical Requirements Summary**

### **Minimum System Requirements**
- **PHP**: 7.4+
- **WordPress**: 5.5+
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Memory**: 256MB
- **Disk Space**: 50MB
- **SSL**: Required for production

### **Recommended Specifications**
- **PHP**: 8.0+
- **WordPress**: Latest version
- **Memory**: 512MB+
- **Server**: SSD storage, CDN integration
- **Caching**: Redis/Memcached for high-traffic sites

### **External Service Requirements**
- **OpenAI API**: Valid API key with sufficient credits
- **WhatsApp Business API**: Meta Business account and phone number verification
- **Email Service**: SMTP credentials or email service API key
- **SSL Certificate**: For secure API communications

---

*EduBot Pro - Built with Enterprise-Grade Technology Standards* 🔧✨
