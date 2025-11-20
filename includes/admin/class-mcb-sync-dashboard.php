<?php
/**
 * MyClassBoard Sync Dashboard
 * 
 * Real-time monitoring and management of MyClassBoard synchronization
 * 
 * @package EduBot_Pro
 * @subpackage Admin
 * @version 1.0.0
 */

class EduBot_MCB_Sync_Dashboard {

    const MENU_SLUG = 'edubot-mcb-dashboard';

    /**
     * Initialize
     */
    public function __construct() {
        // Use priority 11 to ensure parent menu exists (EduBot Pro creates at priority 10)
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 11 );
        
        // Enqueue scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        // AJAX handlers
        add_action( 'wp_ajax_edubot_mcb_dashboard_stats', array( $this, 'ajax_get_stats' ) );
        add_action( 'wp_ajax_edubot_mcb_dashboard_logs', array( $this, 'ajax_get_logs' ) );
        add_action( 'wp_ajax_edubot_mcb_manual_sync', array( $this, 'ajax_manual_sync' ) );
        add_action( 'wp_ajax_edubot_mcb_retry_sync', array( $this, 'ajax_retry_sync' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edubot-pro',
            'MCB Sync Dashboard',
            '📊 Sync Dashboard',
            'manage_options',
            self::MENU_SLUG,
            array( $this, 'render_page' )
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        ?>
        <div class="wrap">
            <h1>MyClassBoard Sync Dashboard</h1>
            <p class="description">Monitor and manage MyClassBoard synchronization in real-time</p>
            <?php self::render_dashboard(); ?>
        </div>
        <?php
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts( $hook ) {
        if ( strpos( $hook, self::MENU_SLUG ) === false ) {
            return;
        }

        wp_enqueue_style( 'edubot-admin' );
        wp_enqueue_script( 'jquery' );

        wp_localize_script( 'jquery', 'EduBotMCB', array(
            'nonce' => wp_create_nonce( 'edubot_mcb_nonce' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    /**
     * AJAX: Get dashboard statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer( 'edubot_mcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $integration = new EduBot_MyClassBoard_Integration();
        $stats = $integration->get_sync_stats();

        wp_send_json_success( $stats );
    }

    /**
     * AJAX: Get sync logs
     */
    public function ajax_get_logs() {
        check_ajax_referer( 'edubot_mcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $limit = intval( $_POST['limit'] ?? 20 );
        $offset = intval( $_POST['offset'] ?? 0 );

        $integration = new EduBot_MyClassBoard_Integration();
        $logs = $integration->get_recent_sync_logs( $limit );

        wp_send_json_success( array(
            'logs' => $logs,
            'count' => count( $logs ),
        ) );
    }

    /**
     * AJAX: Manually sync enquiry
     */
    public function ajax_manual_sync() {
        check_ajax_referer( 'edubot_mcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $enquiry_id = intval( $_POST['enquiry_id'] ?? 0 );

        if ( ! $enquiry_id ) {
            wp_send_json_error( 'Invalid enquiry ID' );
        }

        $integration = new EduBot_MyClassBoard_Integration();
        $result = $integration->sync_enquiry_to_mcb( $enquiry_id, array() );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * AJAX: Retry failed sync
     */
    public function ajax_retry_sync() {
        check_ajax_referer( 'edubot_mcb_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $enquiry_id = intval( $_POST['enquiry_id'] ?? 0 );

        if ( ! $enquiry_id ) {
            wp_send_json_error( 'Invalid enquiry ID' );
        }

        global $wpdb;
        
        $enquiry = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}edubot_enquiries WHERE id = %d",
                $enquiry_id
            ),
            ARRAY_A
        );

        if ( ! $enquiry ) {
            wp_send_json_error( 'Enquiry not found' );
        }

        $integration = new EduBot_MyClassBoard_Integration();
        $result = $integration->sync_enquiry_to_mcb( $enquiry_id, $enquiry );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Render dashboard HTML
     */
    public static function render_dashboard() {
        ?>
        <div id="edubot-mcb-dashboard" class="edubot-mcb-dashboard">
            <!-- Statistics -->
            <div class="mcb-stats-section">
                <h2>Synchronization Statistics</h2>
                <div class="mcb-stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <div class="stat-value" id="mcb-stat-total">—</div>
                            <div class="stat-label">Total Syncs</div>
                        </div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-value" id="mcb-stat-successful">—</div>
                            <div class="stat-label">Successful</div>
                        </div>
                    </div>
                    <div class="stat-card error">
                        <div class="stat-icon">❌</div>
                        <div class="stat-content">
                            <div class="stat-value" id="mcb-stat-failed">—</div>
                            <div class="stat-label">Failed</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📈</div>
                        <div class="stat-content">
                            <div class="stat-value" id="mcb-stat-rate">—%</div>
                            <div class="stat-label">Success Rate</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🔄</div>
                        <div class="stat-content">
                            <div class="stat-value" id="mcb-stat-today">—</div>
                            <div class="stat-label">Today</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mcb-actions-section">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <button class="button button-primary" id="mcb-btn-refresh">
                        🔄 Refresh Stats
                    </button>
                    <button class="button" id="mcb-btn-export">
                        📥 Export Logs
                    </button>
                    <button class="button" id="mcb-btn-settings">
                        ⚙️ Settings
                    </button>
                </div>
            </div>

            <!-- Recent Syncs -->
            <div class="mcb-logs-section">
                <h2>Recent Synchronizations</h2>
                <div class="mcb-filters">
                    <select id="mcb-filter-status" class="mcb-filter">
                        <option value="">All Status</option>
                        <option value="success">✅ Successful</option>
                        <option value="error">❌ Failed</option>
                    </select>
                    <input type="text" id="mcb-filter-search" class="mcb-filter" placeholder="Search enquiry...">
                </div>
                <table class="mcb-logs-table widefat striped">
                    <thead>
                        <tr>
                            <th>Enquiry #</th>
                            <th>Student</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Message</th>
                            <th>Date/Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mcb-logs-tbody">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mcb-pagination">
                    <button class="button" id="mcb-btn-load-more">Load More</button>
                </div>
            </div>
        </div>

        <style>
            .edubot-mcb-dashboard {
                background: #fff;
                padding: 20px;
            }
            
            .mcb-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            
            .stat-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                gap: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
            
            .stat-card.success {
                background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            }
            
            .stat-card.error {
                background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            }
            
            .stat-icon {
                font-size: 32px;
                opacity: 0.9;
            }
            
            .stat-value {
                font-size: 24px;
                font-weight: bold;
            }
            
            .stat-label {
                font-size: 12px;
                opacity: 0.9;
            }
            
            .mcb-actions-section {
                margin: 30px 0;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 8px;
            }
            
            .action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
            
            .action-buttons .button {
                padding: 10px 20px;
                font-size: 14px;
            }
            
            .mcb-logs-section {
                margin-top: 30px;
            }
            
            .mcb-filters {
                display: flex;
                gap: 10px;
                margin: 15px 0;
            }
            
            .mcb-filter {
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
            }
            
            .mcb-logs-table {
                margin-top: 15px;
                background: white;
            }
            
            .mcb-logs-table thead {
                background: #f5f5f5;
            }
            
            .mcb-logs-table tbody tr {
                transition: background 0.2s;
            }
            
            .mcb-logs-table tbody tr:hover {
                background: #f9f9f9;
            }
            
            .mcb-logs-table tr.sync-success {
                background: #f0f8f4;
            }
            
            .mcb-logs-table tr.sync-error {
                background: #f8f3f0;
            }
            
            .status-badge {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
            }
            
            .status-badge.success {
                background: #d4edda;
                color: #155724;
            }
            
            .status-badge.error {
                background: #f8d7da;
                color: #721c24;
            }
            
            .action-cell {
                display: flex;
                gap: 5px;
            }
            
            .action-link {
                padding: 4px 8px;
                font-size: 12px;
                text-decoration: none;
                cursor: pointer;
            }
            
            .mcb-pagination {
                display: flex;
                justify-content: center;
                margin-top: 20px;
            }
            
            .loading-spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #667eea;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            const nonce = '<?php echo wp_create_nonce( "edubot_mcb_nonce" ); ?>';
            
            // Load statistics
            function loadStats() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_mcb_dashboard_stats',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#mcb-stat-total').text(response.data.total);
                            $('#mcb-stat-successful').text(response.data.successful);
                            $('#mcb-stat-failed').text(response.data.failed);
                            $('#mcb-stat-today').text(response.data.today);
                            $('#mcb-stat-rate').text(response.data.success_rate);
                        }
                    }
                });
            }
            
            // Load logs
            function loadLogs(limit = 20, offset = 0) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_mcb_dashboard_logs',
                        nonce: nonce,
                        limit: limit,
                        offset: offset
                    },
                    success: function(response) {
                        if (response.success) {
                            renderLogs(response.data.logs);
                        }
                    }
                });
            }
            
            // Render logs table
            function renderLogs(logs) {
                const tbody = $('#mcb-logs-tbody');
                tbody.empty();
                
                if (logs.length === 0) {
                    tbody.html('<tr><td colspan="7" style="text-align: center; padding: 20px;">No logs found</td></tr>');
                    return;
                }
                
                logs.forEach(function(log) {
                    const statusClass = log.success ? 'sync-success' : 'sync-error';
                    const statusBadge = log.success ? '<span class="status-badge success">✅ Synced</span>' : '<span class="status-badge error">❌ Failed</span>';
                    const error = log.error_message ? log.error_message : '—';
                    
                    const row = `
                        <tr class="${statusClass}">
                            <td><code>${escapeHtml(log.enquiry_number)}</code></td>
                            <td>${escapeHtml(log.student_name)}</td>
                            <td>${escapeHtml(log.email)}</td>
                            <td>${statusBadge}</td>
                            <td>${escapeHtml(error)}</td>
                            <td>${escapeHtml(log.created_at)}</td>
                            <td>
                                <div class="action-cell">
                                    ${!log.success ? `<a class="action-link button-link" onclick="retrySync(${log.enquiry_id})">🔄 Retry</a>` : ''}
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            }
            
            // Escape HTML
            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }
            
            // Retry sync
            window.retrySync = function(enquiryId) {
                if (!confirm('Are you sure you want to retry this sync?')) return;
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'edubot_mcb_retry_sync',
                        nonce: nonce,
                        enquiry_id: enquiryId
                    },
                    success: function(response) {
                        alert('Retry initiated. Refreshing...');
                        loadStats();
                        loadLogs();
                    }
                });
            };
            
            // Button handlers
            $('#mcb-btn-refresh').click(function() {
                $(this).prop('disabled', true);
                loadStats();
                loadLogs();
                setTimeout(() => $(this).prop('disabled', false), 500);
            });
            
            $('#mcb-btn-settings').click(function() {
                window.location.href = '<?php echo admin_url( 'admin.php?page=edubot-mcb-settings' ); ?>';
            });
            
            $('#mcb-btn-load-more').click(function() {
                loadLogs(20, parseInt($('#mcb-logs-tbody tr').length));
            });
            
            // Initial load
            loadStats();
            loadLogs();
            
            // Auto-refresh every 30 seconds
            setInterval(function() {
                loadStats();
            }, 30000);
        });
        </script>
        <?php
    }
}
