<?php
/**
 * MyClassBoard Integration Setup and Initialization
 * 
 * Handles plugin initialization, database setup, and integration registration
 * 
 * @package EduBot_Pro
 * @subpackage Integrations
 * @version 1.0.0
 */

class EduBot_MCB_Integration_Setup {

    /**
     * Tables created flag (prevent multiple creation attempts)
     */
    private static $tables_created = false;

    /**
     * Initialize integration
     */
    public static function init() {
        // Load classes FIRST (required for all other operations)
        self::load_classes();

        // Create database tables IMMEDIATELY on init (highest priority)
        // This ensures tables exist before any code tries to use them
        self::create_tables();

        // Instantiate admin classes immediately (they register their own hooks in constructors)
        if ( is_admin() ) {
            new EduBot_MCB_Settings_Page();
            new EduBot_MCB_Sync_Dashboard();
        }

        // Setup frontend hooks
        add_action( 'init', array( __CLASS__, 'setup_frontend' ), 999 );
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'setup_dashboard_widget' ) );

        // Ensure tables exist on every wp_loaded (safety net)
        add_action( 'wp_loaded', array( __CLASS__, 'create_tables' ), 1 );

        // Enquiry creation hook
        add_action( 'edubot_enquiry_created', array( __CLASS__, 'on_enquiry_created' ), 10, 2 );

        // Add admin notice if tables failed to create
        add_action( 'admin_notices', array( __CLASS__, 'check_database_status' ) );
    }

    /**
     * Load integration classes
     * 
     * CRITICAL: Must be called before any other setup operations
     */
    private static function load_classes() {
        $includes_path = dirname( dirname( __FILE__ ) );

        // Core integration class (required by all other operations)
        $core_file = $includes_path . '/class-myclassboard-integration.php';
        if ( ! file_exists( $core_file ) ) {
            error_log( 'MCB: Missing core integration class: ' . $core_file );
            return;
        }
        require_once $core_file;

        // Admin classes
        if ( is_admin() ) {
            $settings_file = $includes_path . '/admin/class-mcb-settings-page.php';
            $dashboard_file = $includes_path . '/admin/class-mcb-sync-dashboard.php';
            
            if ( ! file_exists( $settings_file ) ) {
                error_log( 'MCB: Missing MCB settings page: ' . $settings_file );
                return;
            }
            if ( ! file_exists( $dashboard_file ) ) {
                error_log( 'MCB: Missing MCB dashboard: ' . $dashboard_file );
                return;
            }

            require_once $settings_file;
            require_once $dashboard_file;
        }
    }

    /**
     * Register admin scripts
     * 
     * Called only when needed (lazy loaded)
     */
    public static function register_admin_scripts() {
        wp_register_script(
            'edubot-mcb-admin',
            plugins_url( 'assets/js/mcb-admin.js', dirname( __FILE__ ) ),
            array( 'jquery' ),
            '1.0.0',
            true
        );

        wp_register_style(
            'edubot-mcb-admin',
            plugins_url( 'assets/css/mcb-admin.css', dirname( __FILE__ ) ),
            array(),
            '1.0.0'
        );
    }

    /**
     * Setup frontend
     * 
     * CRITICAL: Ensure tables exist before using integration
     */
    public static function setup_frontend() {
        // Ensure tables exist (safety net)
        if ( ! self::$tables_created ) {
            self::create_tables();
        }

        // Initialize integration
        try {
            $integration = new EduBot_MyClassBoard_Integration();

            // Ensure sync log table exists
            $integration->ensure_sync_log_table();
        } catch ( Exception $e ) {
            error_log( 'MCB: Error during setup_frontend: ' . $e->getMessage() );
        }
    }

    /**
     * Setup dashboard widget
     * 
     * Only shown to admins who have manage_options capability
     */
    public static function setup_dashboard_widget() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Ensure class exists before adding widget
        if ( ! class_exists( 'EduBot_MyClassBoard_Integration' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'edubot_mcb_widget',
            'MyClassBoard Sync Status',
            array( __CLASS__, 'render_dashboard_widget' )
        );
    }

    /**
     * Render dashboard widget
     * 
     * Safely handles errors and shows status
     */
    public static function render_dashboard_widget() {
        try {
            if ( ! class_exists( 'EduBot_MyClassBoard_Integration' ) ) {
                echo '<p style="color: #dc3545;">Integration class not loaded. Please check plugin configuration.</p>';
                return;
            }

            $integration = new EduBot_MyClassBoard_Integration();
            $settings = $integration->get_settings();
            $stats = $integration->get_sync_stats();
        } catch ( Exception $e ) {
            echo '<p style="color: #dc3545;">Error loading widget: ' . esc_html( $e->getMessage() ) . '</p>';
            return;
        }
        ?>
        <div class="edubot-mcb-widget">
            <div style="margin-bottom: 15px;">
                <strong>Integration Status:</strong> 
                <?php if ( $settings['enabled'] && $settings['sync_enabled'] ): ?>
                    <span style="color: green;">✅ Active</span>
                <?php else: ?>
                    <span style="color: orange;">⏸ Inactive</span>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                <div style="background: #f5f5f5; padding: 10px; border-radius: 4px;">
                    <div style="font-size: 18px; font-weight: bold;">
                        <?php echo intval( $stats['successful'] ); ?>
                    </div>
                    <div style="font-size: 12px; color: #666;">Successful Syncs</div>
                </div>
                <div style="background: #f5f5f5; padding: 10px; border-radius: 4px;">
                    <div style="font-size: 18px; font-weight: bold; color: #dc3545;">
                        <?php echo intval( $stats['failed'] ); ?>
                    </div>
                    <div style="font-size: 12px; color: #666;">Failed Syncs</div>
                </div>
            </div>

            <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold;">
                    <?php echo floatval( $stats['success_rate'] ); ?>%
                </div>
                <div style="font-size: 12px; color: #666;">Success Rate</div>
            </div>

            <a href="<?php echo admin_url( 'admin.php?page=edubot-mcb-settings' ); ?>" class="button button-primary" style="width: 100%;">
                View Full Dashboard
            </a>
        </div>

        <style>
            .edubot-mcb-widget {
                padding: 10px 0;
            }
        </style>
        <?php
    }

    /**
     * Create database tables
     * 
     * CRITICAL FUNCTION: Creates all MCB tables needed for plugin operation
     * Called early and often to ensure tables always exist
     * 
     * @return bool True if tables created/exist, False if error
     */
    public static function create_tables() {
        global $wpdb;

        // Prevent multiple simultaneous creation attempts
        if ( self::$tables_created ) {
            return true;
        }

        try {
            // Ensure upgrade.php is loaded
            if ( ! function_exists( 'dbDelta' ) ) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            // Create MCB settings table
            $settings_created = self::create_mcb_settings_table();
            
            // Create MCB sync log table
            $sync_log_created = self::create_mcb_sync_log_table();

            if ( ! $settings_created || ! $sync_log_created ) {
                error_log( 'MCB: Database table creation failed' );
                return false;
            }

            // Mark as created to prevent re-creation attempts
            self::$tables_created = true;

            // Log successful creation
            if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
                error_log( 'MCB: All database tables created successfully' );
            }

            return true;
        } catch ( Exception $e ) {
            error_log( 'MCB: Exception during table creation: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Create MCB settings table
     * 
     * Stores MCB configuration for each blog in multisite
     * Single site uses blog_id = 1
     * 
     * @return bool True if table created/exists, False if error
     */
    private static function create_mcb_settings_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'edubot_mcb_settings';

        // Check if table already exists
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table 
        ) ) === $table;

        if ( $table_exists ) {
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            site_id BIGINT(20) NOT NULL,
            config_data LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_site (site_id),
            INDEX idx_updated (updated_at)
        ) $charset_collate;";

        // Execute the creation
        $wpdb->query( $sql );

        // Verify table was created
        $verify = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table 
        ) ) === $table;

        if ( ! $verify ) {
            error_log( 'MCB: Failed to create settings table: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Create MCB sync log table
     * 
     * Stores all sync attempts (successful and failed)
     * Used for monitoring, debugging, and manual retry operations
     * 
     * @return bool True if table created/exists, False if error
     */
    private static function create_mcb_sync_log_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'edubot_mcb_sync_log';

        // Check if table already exists
        $table_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table 
        ) ) === $table;

        if ( $table_exists ) {
            return true;
        }

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            enquiry_id BIGINT(20) NOT NULL,
            request_data LONGTEXT,
            response_data LONGTEXT,
            success TINYINT(1) DEFAULT 0,
            error_message TEXT,
            retry_count INT(11) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_enquiry (enquiry_id),
            INDEX idx_success (success),
            INDEX idx_created (created_at),
            INDEX idx_retry (retry_count)
        ) $charset_collate;";

        // Execute the creation
        $wpdb->query( $sql );

        // Verify table was created
        $verify = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $table 
        ) ) === $table;

        if ( ! $verify ) {
            error_log( 'MCB: Failed to create sync log table: ' . $wpdb->last_error );
            return false;
        }

        return true;
    }

    /**
     * Handle enquiry creation and trigger MCB sync
     * 
     * Called when a new enquiry is created via the EduBot enquiry system
     * 
     * @param int   $enquiry_id Enquiry ID
     * @param array $enquiry    Enquiry data
     */
    public static function on_enquiry_created( $enquiry_id, $enquiry ) {
        // Validate tables exist
        if ( ! self::verify_tables_exist() ) {
            error_log( 'MCB: Cannot sync enquiry ' . $enquiry_id . ' - database tables missing' );
            return;
        }

        try {
            if ( ! class_exists( 'EduBot_MyClassBoard_Integration' ) ) {
                error_log( 'MCB: Integration class not available for enquiry ' . $enquiry_id );
                return;
            }

            $integration = new EduBot_MyClassBoard_Integration();
            $settings = $integration->get_settings();

            if ( ! $settings['enabled'] || ! $settings['sync_enabled'] || ! $settings['auto_sync'] ) {
                return;
            }

            // Async sync to avoid blocking the enquiry creation
            wp_schedule_single_event(
                time() + 5, // 5 second delay
                'edubot_mcb_sync_enquiry',
                array( $enquiry_id, $enquiry )
            );
        } catch ( Exception $e ) {
            error_log( 'MCB: Error handling enquiry creation: ' . $e->getMessage() );
        }
    }

    /**
     * Get integration status
     * 
     * Returns comprehensive status information
     * 
     * @return array Status information
     */
    public static function get_status() {
        try {
            if ( ! class_exists( 'EduBot_MyClassBoard_Integration' ) ) {
                return array(
                    'enabled'       => false,
                    'error'         => 'Integration class not loaded',
                    'tables_exist'  => self::verify_tables_exist(),
                );
            }

            $integration = new EduBot_MyClassBoard_Integration();
            $settings = $integration->get_settings();
            $stats = $integration->get_sync_stats();

            return array(
                'enabled'       => $settings['enabled'],
                'sync_enabled'  => $settings['sync_enabled'],
                'auto_sync'     => $settings['auto_sync'],
                'test_mode'     => $settings['test_mode'],
                'org_id'        => $settings['organization_id'],
                'branch_id'     => $settings['branch_id'],
                'total_syncs'   => $stats['total'],
                'successful'    => $stats['successful'],
                'failed'        => $stats['failed'],
                'success_rate'  => $stats['success_rate'],
                'tables_exist'  => self::verify_tables_exist(),
            );
        } catch ( Exception $e ) {
            return array(
                'error'         => $e->getMessage(),
                'tables_exist'  => self::verify_tables_exist(),
            );
        }
    }

    /**
     * Verify all required tables exist
     * 
     * CRITICAL: Called before operations to ensure database integrity
     * 
     * @return bool True if all tables exist, False otherwise
     */
    private static function verify_tables_exist() {
        global $wpdb;

        $settings_table = $wpdb->prefix . 'edubot_mcb_settings';
        $sync_log_table = $wpdb->prefix . 'edubot_mcb_sync_log';

        $settings_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $settings_table 
        ) ) === $settings_table;

        $sync_log_exists = $wpdb->get_var( $wpdb->prepare( 
            'SHOW TABLES LIKE %s', 
            $sync_log_table 
        ) ) === $sync_log_table;

        return $settings_exists && $sync_log_exists;
    }

    /**
     * Check database status and show admin notice if issues found
     * 
     * CRITICAL: Alerts admins to database problems early
     */
    public static function check_database_status() {
        // Only show to admins on admin pages
        if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
            return;
        }

        // Check if tables exist
        if ( ! self::verify_tables_exist() ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>
                    <strong>MyClassBoard Integration:</strong> 
                    Database tables are missing. 
                    <a href="<?php echo admin_url( 'admin.php?page=edubot-mcb-settings' ); ?>">
                        View Settings
                    </a>
                </p>
            </div>
            <?php
            return;
        }

        // Verify integration class is available
        if ( ! class_exists( 'EduBot_MyClassBoard_Integration' ) ) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong>MyClassBoard Integration:</strong> 
                    Integration class not loaded. Please check plugin configuration.
                </p>
            </div>
            <?php
        }
    }

    /**
     * Get documentation
     * 
     * @return string Markdown documentation
     */
    public static function get_documentation() {
        return <<<EOD
# MyClassBoard Integration for EduBot Pro

## Overview

This integration automatically synchronizes EduBot Pro enquiries to MyClassBoard (MCB) CRM system.

## Features

- **Automatic Sync**: Automatically send new enquiries to MCB
- **Lead Source Mapping**: Map EduBot sources to MCB source IDs
- **Retry Mechanism**: Automatically retry failed syncs
- **Sync Dashboard**: Monitor sync status in real-time
- **Sync Logs**: Track all sync attempts and results
- **Manual Sync**: Manually sync individual enquiries

## Configuration

### 1. Enable Integration
1. Go to WordPress Admin → EduBot → MyClassBoard Settings
2. Check "Enable MCB Integration"
3. Enter your MCB Organization ID (default: 21)
4. Enter your MCB Branch ID (default: 113)

### 2. Configure API Settings
- API Key: Your MCB API key (optional, for advanced features)
- Timeout: Maximum wait time for API response (default: 65 seconds)
- Retry Attempts: Number of retries for failed requests (default: 3)

### 3. Configure Lead Source Mapping
Map your EduBot sources to MCB QueryContactSourceID values:
- Chatbot → 273
- Website → 231
- Facebook → 272
- Google Search → 269
- etc.

### 4. Enable Auto-Sync
- Check "Enable Data Sync"
- Check "Sync New Enquiries" for automatic new enquiry sync
- Check "Auto Sync" for immediate sync (vs batch)

## How It Works

### Data Flow

```
EduBot Enquiry
    ↓
Enquiry Created Event
    ↓
MCB Integration Listener
    ↓
Map Data to MCB Format
    ↓
Send to MCB API
    ↓
Process Response
    ↓
Log Result
    ↓
Update Enquiry Status
```

### Data Mapping

| EduBot Field | MCB Field | Notes |
|-------------|-----------|-------|
| student_name | StudentName | Required |
| parent_name | FatherName | Parent/Guardian |
| email | FatherEmailID | Primary contact |
| phone | FatherMobile | Primary contact |
| grade | Class | Academic level |
| board | — | For internal tracking |
| academic_year | AcademicYearID | School year |
| address | Address1 | Optional |
| source | QueryContactSourceID | Lead source ID |

## Monitoring & Troubleshooting

### View Sync Status
1. Go to WordPress Admin → Dashboard
2. Check "MyClassBoard Sync Status" widget
3. Or go to EduBot → MyClassBoard Settings → Sync Status

### View Sync Logs
1. Go to EduBot → MyClassBoard Settings → Sync Logs
2. Filter by success/error status
3. Search for specific enquiry

### Manual Sync
1. Go to EduBot → Applications
2. Find the enquiry you want to sync
3. Click "Sync to MCB" action

### Retry Failed Sync
1. Go to EduBot → MyClassBoard Settings → Sync Logs
2. Find the failed sync
3. Click "Retry"

### Common Issues

**Integration not enabled**
- Check if "Enable MCB Integration" is checked
- Check if "Enable Data Sync" is checked

**Syncs are failing**
- Check the sync logs for error messages
- Verify Organization ID and Branch ID
- Check network connectivity to MCB API
- Verify MCB API is accessible

**Syncs are slow**
- Increase the timeout value
- Check your internet connection
- Check MCB API status

## API Endpoints

### Main Endpoint
```
POST https://corp.myclassboard.com/api/EnquiryService/SaveEnquiryDetails
```

### Request Format
```json
{
    "OrganisationID": "21",
    "BranchID": "113",
    "AcademicYearID": 17,
    "StudentName": "John Doe",
    "FatherName": "Jane Doe",
    "FatherMobile": "9876543210",
    "FatherEmailID": "john@example.com",
    "Class": "Grade 5",
    "ClassID": 280,
    "QueryContactSourceID": "273",
    "LeadSource": "273"
}
```

## Security

- API keys are stored securely in WordPress database
- All API communications use HTTPS
- Sensitive data is masked in logs
- User authentication required for manual actions

## Support

For issues or questions:
1. Check sync logs for detailed error messages
2. Review EduBot documentation
3. Contact EduBot Pro support team
4. Contact MyClassBoard support

EOD;
    }
}
