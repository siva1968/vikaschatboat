<?php
/**
 * Applications List View
 */
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="edubot-applications">
        <?php if (isset($applications) && !empty($applications)): ?>
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action">
                        <option value="">Bulk Actions</option>
                        <option value="approve">Approve</option>
                        <option value="reject">Reject</option>
                        <option value="pending">Mark as Pending</option>
                    </select>
                    <button type="button" class="button action">Apply</button>
                </div>
                <div class="alignright">
                    <button type="button" class="button button-primary" id="export-applications">Export CSV</button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all" />
                        </td>
                        <th class="manage-column">Application #</th>
                        <th class="manage-column">Student Name</th>
                        <th class="manage-column">Parent Name</th>
                        <th class="manage-column">Grade</th>
                        <th class="manage-column">Board</th>
                        <th class="manage-column">Academic Year</th>
                        <th class="manage-column">Email</th>
                        <th class="manage-column">Phone</th>
                        <th class="manage-column">Date</th>
                        <th class="manage-column">Status</th>
                        <th class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="application[]" value="<?php echo esc_attr($app['id']); ?>" />
                            </td>
                            <td><?php echo esc_html($app['application_number']); ?></td>
                            <td><strong><?php echo esc_html($app['student_name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo esc_html($app['parent_name'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($app['grade'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $board = $app['educational_board'] ?? 'N/A';
                                if ($board && $board !== 'N/A') {
                                    // Get board full name if available
                                    $school_config = new EduBot_School_Config();
                                    $board_info = $school_config->get_board_info($board);
                                    echo esc_html($board_info ? $board_info['name'] : $board);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $academic_year = $app['academic_year'] ?? 'N/A';
                                if ($academic_year && $academic_year !== 'N/A') {
                                    // Get academic year info if available
                                    $school_config = new EduBot_School_Config();
                                    $year_info = $school_config->get_academic_year_info($academic_year);
                                    echo '<span class="academic-year-display">' . esc_html($year_info['label']) . '</span>';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($app['email'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($app['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html(date('M j, Y g:i A', strtotime($app['created_at']))); ?></td>
                            <td>
                                <span class="status status-<?php echo esc_attr($app['status']); ?>">
                                    <?php echo esc_html(ucfirst($app['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <a href="#" class="button button-small view-application" data-id="<?php echo esc_attr($app['id']); ?>">View</a>
                                <select class="status-changer" data-id="<?php echo esc_attr($app['id']); ?>">
                                    <option value="pending" <?php selected($app['status'], 'pending'); ?>>Pending</option>
                                    <option value="approved" <?php selected($app['status'], 'approved'); ?>>Approved</option>
                                    <option value="rejected" <?php selected($app['status'], 'rejected'); ?>>Rejected</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo count($applications); ?> items</span>
                </div>
            </div>
        <?php else: ?>
            <div class="edubot-card">
                <h2>No Applications Yet</h2>
                <p>No applications have been received yet. Once visitors start using your chatbot, their applications will appear here.</p>
                
                <div class="getting-started">
                    <h3>To start receiving applications:</h3>
                    <ol>
                        <li>Make sure your chatbot is properly configured</li>
                        <li>Add the chatbot to your website with: <code>[edubot_chatbot]</code></li>
                        <li>Test the chatbot to ensure it's working correctly</li>
                    </ol>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Application Detail Modal -->
<div id="application-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Application Details</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="application-details">
                <!-- Application details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<style>
.edubot-applications {
    margin-top: 20px;
}
.edubot-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    border-radius: 4px;
}
.edubot-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}
.status {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 0.85em;
    font-weight: bold;
}
.status-pending {
    background: #fff3cd;
    color: #856404;
}
.status-approved {
    background: #d4edda;
    color: #155724;
}
.status-rejected {
    background: #f8d7da;
    color: #721c24;
}
.status-changer {
    font-size: 0.8em;
    margin-left: 5px;
}
.getting-started {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 4px;
    margin-top: 20px;
}
.getting-started h3 {
    margin-top: 0;
}

/* Modal Styles */
#application-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}
.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border-radius: 4px;
    width: 80%;
    max-width: 800px;
    max-height: 90%;
    overflow-y: auto;
}
.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    margin: 0;
}
.close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover {
    color: #000;
}
.modal-body {
    padding: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Status changer functionality
    $('.status-changer').on('change', function() {
        var applicationId = $(this).data('id');
        var newStatus = $(this).val();
        
        // AJAX call to update status would go here
        console.log('Updating application ' + applicationId + ' to status: ' + newStatus);
    });

    // View application modal
    $('.view-application').on('click', function(e) {
        e.preventDefault();
        var applicationId = $(this).data('id');
        
        // AJAX call to get application details would go here
        $('#application-details').html('<p>Loading application details...</p>');
        $('#application-modal').show();
    });

    // Close modal
    $('.close').on('click', function() {
        $('#application-modal').hide();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target.id === 'application-modal') {
            $('#application-modal').hide();
        }
    });

    // Export functionality
    $('#export-applications').on('click', function() {
        // Export functionality would go here
        alert('Export functionality will be implemented in the next update.');
    });
});
</script>
