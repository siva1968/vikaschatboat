# WordPress Plugin Standard Implementation Prompt

Create a WordPress plugin following these comprehensive standards and best practices:

## Plugin Structure Requirements

### File Organization
```
/my-plugin/
├── my-plugin.php (main plugin file)
├── uninstall.php
├── readme.txt
├── /includes/
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-core.php
│   └── class-loader.php
├── /admin/
│   ├── class-admin.php
│   ├── /css/
│   ├── /js/
│   └── /partials/
├── /public/
│   ├── class-public.php
│   ├── /css/
│   ├── /js/
│   └── /partials/
└── /languages/
```

### Plugin Header (Required)
```php
<?php
/**
 * Plugin Name: [Plugin Name]
 * Plugin URI: [Plugin Website]
 * Description: [Brief Description]
 * Version: 1.0.0
 * Author: [Author Name]
 * Author URI: [Author Website]
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: [plugin-textdomain]
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
```

## Security Standards

### Essential Security Measures
1. **Prevent Direct Access**: Always include `if (!defined('ABSPATH')) { exit; }`
2. **Nonce Verification**: Use `wp_nonce_field()` and `wp_verify_nonce()` for forms
3. **Data Sanitization**: Sanitize all input using `sanitize_text_field()`, `sanitize_email()`, etc.
4. **Data Validation**: Validate all data before processing
5. **Escape Output**: Use `esc_html()`, `esc_attr()`, `esc_url()` for output
6. **Capability Checks**: Use `current_user_can()` for permission verification
7. **SQL Injection Prevention**: Use `$wpdb->prepare()` for database queries

### Example Security Implementation
```php
// Form processing with security
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'my_action')) {
    if (current_user_can('manage_options')) {
        $value = sanitize_text_field($_POST['field_name']);
        // Process data
    }
}
```

## WordPress Coding Standards

### PHP Standards
1. Follow WordPress PHP Coding Standards
2. Use proper indentation (tabs, not spaces)
3. Proper spacing around operators and after commas
4. Meaningful variable and function names
5. Comprehensive inline documentation using PHPDoc

### Naming Conventions
- **Functions**: `prefix_function_name()`
- **Classes**: `Prefix_Class_Name`
- **Constants**: `PREFIX_CONSTANT_NAME`
- **Variables**: `$descriptive_variable_name`

## Database Implementation

### Table Creation
```php
function create_plugin_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'my_plugin_table';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

### Database Queries
```php
// Use prepared statements
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}my_table WHERE user_id = %d AND status = %s",
    $user_id,
    $status
));
```

## Hook Implementation

### Action Hooks
```php
// Plugin activation/deactivation
register_activation_hook(__FILE__, 'plugin_activate');
register_deactivation_hook(__FILE__, 'plugin_deactivate');

// WordPress hooks
add_action('init', 'plugin_init');
add_action('wp_enqueue_scripts', 'plugin_enqueue_scripts');
add_action('admin_enqueue_scripts', 'plugin_admin_scripts');
```

### Filter Hooks
```php
add_filter('the_content', 'modify_content');
add_filter('wp_mail', 'customize_wp_mail');
```

## Admin Interface Standards

### Menu Integration
```php
add_action('admin_menu', 'add_plugin_admin_menu');

function add_plugin_admin_menu() {
    add_options_page(
        'Plugin Settings',
        'My Plugin',
        'manage_options',
        'my-plugin-settings',
        'plugin_settings_page'
    );
}
```

### Settings API Implementation
```php
// Register settings
add_action('admin_init', 'plugin_admin_init');

function plugin_admin_init() {
    register_setting('plugin_settings_group', 'plugin_option');
    
    add_settings_section(
        'plugin_main_section',
        'Main Settings',
        'plugin_section_callback',
        'plugin-settings'
    );
    
    add_settings_field(
        'plugin_field',
        'Setting Field',
        'plugin_field_callback',
        'plugin-settings',
        'plugin_main_section'
    );
}
```

## Frontend Implementation

### Enqueue Scripts and Styles
```php
function plugin_enqueue_assets() {
    wp_enqueue_style(
        'plugin-style',
        plugin_dir_url(__FILE__) . 'css/plugin-style.css',
        array(),
        PLUGIN_VERSION
    );
    
    wp_enqueue_script(
        'plugin-script',
        plugin_dir_url(__FILE__) . 'js/plugin-script.js',
        array('jquery'),
        PLUGIN_VERSION,
        true
    );
    
    // Localize script for AJAX
    wp_localize_script('plugin-script', 'plugin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('plugin_nonce')
    ));
}
```

## AJAX Implementation

### Backend Handler
```php
// For logged-in users
add_action('wp_ajax_my_action', 'handle_ajax_request');
// For non-logged-in users
add_action('wp_ajax_nopriv_my_action', 'handle_ajax_request');

function handle_ajax_request() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'plugin_nonce')) {
        wp_die('Security check failed');
    }
    
    // Process request
    $result = process_ajax_data();
    
    wp_send_json_success($result);
}
```

## Internationalization (i18n)

### Text Domain Setup
```php
add_action('plugins_loaded', 'plugin_load_textdomain');

function plugin_load_textdomain() {
    load_plugin_textdomain(
        'plugin-textdomain',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
```

### Translatable Strings
```php
// Use translation functions
__('Text to translate', 'plugin-textdomain');
_e('Echo translated text', 'plugin-textdomain');
_n('Singular', 'Plural', $count, 'plugin-textdomain');
esc_html__('Escaped translation', 'plugin-textdomain');
```

## Error Handling and Logging

### Error Handling
```php
function safe_plugin_operation() {
    try {
        // Plugin operation
        $result = risky_operation();
        return $result;
    } catch (Exception $e) {
        error_log('Plugin Error: ' . $e->getMessage());
        return new WP_Error('plugin_error', __('Operation failed', 'plugin-textdomain'));
    }
}
```

### Debug Logging
```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Plugin Debug: ' . print_r($debug_data, true));
}
```

## Plugin Architecture

### Main Plugin Class
```php
class My_Plugin {
    
    protected $loader;
    protected $plugin_name;
    protected $version;
    
    public function __construct() {
        $this->plugin_name = 'my-plugin';
        $this->version = '1.0.0';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        $this->loader = new Plugin_Loader();
    }
    
    public function run() {
        $this->loader->run();
    }
}
```

## Documentation Requirements

### Code Documentation
- Use PHPDoc blocks for all functions and classes
- Include parameter types and return types
- Document complex logic with inline comments
- Maintain changelog in readme.txt

### readme.txt Format
```
=== Plugin Name ===
Contributors: username
Tags: tag1, tag2
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later

Short description of the plugin.

== Description ==
Detailed description...

== Installation ==
Installation instructions...

== Changelog ==
= 1.0.0 =
* Initial release
```

## Performance Optimization

### Best Practices
1. **Conditional Loading**: Only load scripts/styles when needed
2. **Database Optimization**: Use proper indexing and efficient queries
3. **Caching**: Implement transients for expensive operations
4. **Lazy Loading**: Load heavy resources only when required
5. **Minification**: Minify CSS/JS files for production

### Caching Example
```php
function get_cached_data() {
    $cache_key = 'plugin_data_cache';
    $cached_data = get_transient($cache_key);
    
    if (false === $cached_data) {
        $cached_data = expensive_data_operation();
        set_transient($cache_key, $cached_data, HOUR_IN_SECONDS);
    }
    
    return $cached_data;
}
```

## Testing Requirements

### Unit Testing Setup
- Use PHPUnit for unit testing
- Test all public methods
- Mock WordPress functions when needed
- Achieve minimum 80% code coverage

### Manual Testing Checklist
- [ ] Plugin activation/deactivation
- [ ] Admin interface functionality
- [ ] Frontend display
- [ ] Form submissions
- [ ] AJAX operations
- [ ] Multisite compatibility (if applicable)
- [ ] Performance impact
- [ ] Cross-browser compatibility

## Deployment Standards

### Pre-deployment Checklist
- [ ] Code review completed
- [ ] All tests passing
- [ ] Documentation updated
- [ ] Version numbers incremented
- [ ] Security audit completed
- [ ] Performance testing done
- [ ] WordPress compatibility verified

### Version Control
- Use semantic versioning (MAJOR.MINOR.PATCH)
- Tag releases in version control
- Maintain detailed commit messages
- Use branching strategy (main, develop, feature branches)

This prompt ensures your WordPress plugin follows industry standards, WordPress guidelines, and security best practices while maintaining scalability and maintainability.