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
                    <select name="bulk_action" id="bulk-action-selector">
                        <option value="">Bulk Actions</option>
                        <option value="approve">Approve</option>
                        <option value="reject">Reject</option>
                        <option value="pending">Mark as Pending</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="button" class="button action" id="bulk-action-btn" style="display:none;">Apply</button>
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
                        <th class="manage-column">EnquiryCode</th>
                        <th class="manage-column">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" name="application_ids[]" value="<?php echo esc_attr($app['id']); ?>" />
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
                                    $school_config = EduBot_School_Config::getInstance();
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
                                    $school_config = EduBot_School_Config::getInstance();
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
                                <?php if (!empty($app['mcb_query_code'])): ?>
                                    <span class="mcb-enquiry-code" style="background: #fff3cd; padding: 4px 8px; border-radius: 3px; font-weight: bold; font-family: monospace; font-size: 12px;">
                                        <?php echo esc_html($app['mcb_query_code']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Build action links array
                                $action_links = array(
                                    'view' => '<a href="#" class="button button-small view-application" data-id="' . esc_attr($app['id']) . '">View</a>',
                                    'delete' => '<a href="#" class="button button-small button-link-delete delete-application" data-id="' . esc_attr($app['id']) . '" onclick="return confirm(\'Are you sure you want to delete this application?\');">Delete</a>'
                                );
                                
                                // Apply filter to allow MCB and other features to add buttons
                                $action_links = apply_filters('edubot_applications_row_actions', $action_links, $app);
                                
                                // Output action links
                                echo implode(' ', $action_links);
                                ?>
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
.button-link-delete {
    color: #a00 !important;
    border-color: #a00 !important;
}
.button-link-delete:hover {
    background: #a00 !important;
    color: #fff !important;
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

/* Application Details Styles */
.application-details {
    font-family: Arial, sans-serif;
}
.detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #eee;
}
.detail-header h3 {
    margin: 0;
    color: #333;
}
.status-badge {
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}
.detail-section {
    margin-bottom: 20px;
}
.detail-section h4 {
    margin: 0 0 10px 0;
    color: #555;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
.detail-table {
    width: 100%;
    border-collapse: collapse;
}
.detail-table td {
    padding: 6px 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
}
.detail-table td:first-child {
    width: 140px;
    font-weight: 500;
    color: #666;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Bulk action selector
    $('#bulk-action-selector').on('change', function() {
        var action = $(this).val();
        if (action) {
            $('#bulk-action-btn').show();
        } else {
            $('#bulk-action-btn').hide();
        }
    });

    // Bulk action button
    $('#bulk-action-btn').on('click', function(e) {
        e.preventDefault();
        
        var action = $('#bulk-action-selector').val();
        var selectedIds = [];
        
        $('input[name="application_ids[]"]:checked').each(function() {
            selectedIds.push($(this).val());
        });
        
        if (selectedIds.length === 0) {
            alert('Please select at least one application');
            return;
        }
        
        var actionText = action === 'delete' ? 'delete' : (action === 'approve' ? 'approve' : (action === 'reject' ? 'reject' : 'update'));
        
        if (!confirm('Are you sure you want to ' + actionText + ' ' + selectedIds.length + ' application(s)?')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edubot_bulk_action',
                bulk_action: action,
                application_ids: selectedIds,
                nonce: '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    });

    // Select all checkbox
    $('#cb-select-all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('input[name="application_ids[]"]').prop('checked', isChecked);
    });

    // Individual delete buttons
    $('.delete-application').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this application?')) {
            return false;
        }
        
        var applicationId = $(this).data('id');
        var $row = $(this).closest('tr');
        
        console.log('Delete clicked for ID:', applicationId);
        console.log('AJAX URL:', ajaxurl);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edubot_delete_application',
                application_id: applicationId,
                nonce: '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>'
            },
            success: function(response) {
                console.log('AJAX Success:', response);
                if (response.success) {
                    $row.fadeOut(function() {
                        $(this).remove();
                    });
                    alert('✅ Application deleted successfully!');
                } else {
                    console.error('Delete failed:', response.data);
                    alert('❌ Error: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error, xhr.responseText);
                alert('❌ Network error. Please try again.\n\nStatus: ' + status);
            }
        });
        
        return false;
    });

    // View application modal
    $('.view-application').on('click', function(e) {
        e.preventDefault();
        var applicationId = $(this).data('id');
        
        $('#application-details').html('<p style="text-align: center; padding: 20px;"><span class="spinner is-active"></span> Loading application details...</p>');
        $('#application-modal').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edubot_view_application',
                application_id: applicationId,
                nonce: '<?php echo wp_create_nonce('edubot_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#application-details').html(response.data.html);
                } else {
                    $('#application-details').html('<p style="color: red;">Error: ' + response.data + '</p>');
                }
            },
            error: function() {
                $('#application-details').html('<p style="color: red;">Network error. Please try again.</p>');
            }
        });
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
    
    // ============================================================================
    // MCB PREVIEW FUNCTIONALITY - INLINED FOR GUARANTEED LOADING
    // ============================================================================
    
    console.log('🔵 === MCB Admin JS (INLINED VERSION) Initialized ===');
    console.log('✅ jQuery loaded:', typeof $ === 'function');
    console.log('📍 Found MCB preview buttons:', $('.mcb-preview-btn').length);
    
    // Ensure edubot_mcb object exists with proper values
    if (typeof edubot_mcb === 'undefined') {
        console.warn('⚠️ edubot_mcb object not found, creating with proper values');
        window.edubot_mcb = {
            ajax_url: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
            nonce: <?php echo json_encode(wp_create_nonce('edubot_mcb_sync')); ?>,
            sync_text: 'Syncing to MCB...',
            sync_success: 'Successfully synced to MCB!',
            sync_failed: 'Failed to sync. Check error logs.',
            sync_already: 'Already synced to MCB'
        };
    }
    
    console.log('📍 Using AJAX URL:', edubot_mcb.ajax_url);
    console.log('✅ Nonce available:', !!edubot_mcb.nonce);
    
    // Handle MCB preview button click
    $(document).on('click', '.mcb-preview-btn', function(e) {
        e.preventDefault();
        console.log('🔵 MCB PREVIEW BUTTON CLICKED');
        
        var $btn = $(this);
        
        // Use direct attribute access to get the ID (it's a string like 'enq_40')
        var enquiryId = $btn.attr('data-enquiry-id');
        console.log('📍 Enquiry ID from attribute:', enquiryId);
        console.log('📊 Button element:', {
            'tag': $btn.prop('tagName'),
            'class': $btn.attr('class'),
            'data-enquiry-id': enquiryId,
            'html': $btn.prop('outerHTML').substring(0, 200)
        });
        
        // Check if ID is valid
        console.log('📊 ID type:', typeof enquiryId, '| Length:', enquiryId ? enquiryId.length : 0);
        
        // Don't parse to integer - the ID could be string or integer
        // Just validate that it's not empty
        if (!enquiryId) {
            console.error('❌ No enquiry ID found or invalid:', enquiryId);
            alert('Error: No enquiry ID found on button');
            return;
        }
        
        console.log('✅ Valid enquiry ID:', enquiryId, '(type:', typeof enquiryId + ')');
        console.log('📤 Sending AJAX with data:', {
            action: 'edubot_mcb_preview_data',
            enquiry_id: enquiryId,
            nonce: 'PRESENT: ' + (!!edubot_mcb.nonce)
        });
        
        $.ajax({
            url: edubot_mcb.ajax_url,
            type: 'POST',
            data: {
                action: 'edubot_mcb_preview_data',
                enquiry_id: enquiryId,
                nonce: edubot_mcb.nonce || ''
            },
            success: function(response) {
                console.log('✅ AJAX Success:', response);
                console.log('📊 Response details:', {
                    'success': response.success,
                    'has_data': !!response.data,
                    'message': response.data?.message || 'N/A'
                });
                if (response.success && response.data) {
                    console.log('✅ Success! Showing modal...');
                    showMCBPreviewModal(response.data);
                } else {
                    console.error('❌ Response error:', response);
                    const errorMsg = response.data?.message || 'Unknown error';
                    console.error('Error Message:', errorMsg);
                    alert('Failed to load preview: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', error, xhr.responseText);
                alert('Failed to load MCB preview');
            }
        });
    });
    
    // Display MCB preview modal
    function showMCBPreviewModal(data) {
        console.log('📋 Showing MCB Preview Modal');
        console.log('   Data:', data);
        
        var html = '<div class="mcb-preview-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;">';
        html += '<div style="background: white; border-radius: 8px; max-width: 1200px; max-height: 95vh; overflow: auto; padding: 40px; box-shadow: 0 5px 40px rgba(0,0,0,0.3); font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;">';
        
        // Header
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 3px solid #0073aa; padding-bottom: 20px;">';
        html += '<h2 style="margin: 0; color: #0073aa; font-size: 24px;">📋 MCB Data Preview</h2>';
        html += '<button type="button" style="background: none; border: none; font-size: 28px; cursor: pointer; color: #999; padding: 0; width: 30px; height: 30px;">&times;</button>';
        html += '</div>';
        
        // Enquiry Header
        html += '<div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #0073aa;">';
        html += '<p style="margin: 5px 0; font-size: 14px;"><strong>Enquiry #:</strong> ' + escapeHtml(data.enquiry_number || 'N/A') + '</p>';
        html += '</div>';
        
        // ====== SECTION 1: MCB Configuration (Settings) ======
        if (data.mcb_settings) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">⚙️ MCB Configuration (Settings)</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fafafa;">';
            html += createTableRow('Organization ID', data.mcb_settings.organization_id);
            html += createTableRow('Branch ID', data.mcb_settings.branch_id);
            html += createTableRow('Sync Enabled', data.mcb_settings.sync_enabled ? '✅ YES' : '❌ NO');
            html += '</table>';
        }
        
        // ====== SECTION 2: Source Data (What Was Collected) ======
        if (data.enquiry_source_data) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">📥 Data Collected from Enquiry (Source)</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fafafa;">';
            html += createTableRowWithStatus('Student Name', data.enquiry_source_data.student_name);
            html += createTableRowWithStatus('Parent Name', data.enquiry_source_data.parent_name);
            html += createTableRowWithStatus('Email', data.enquiry_source_data.email);
            html += createTableRowWithStatus('Phone', data.enquiry_source_data.phone);
            html += createTableRowWithStatus('Date of Birth', data.enquiry_source_data.date_of_birth);
            html += createTableRowWithStatus('Grade', data.enquiry_source_data.grade);
            html += createTableRowWithStatus('Board', data.enquiry_source_data.board);
            html += createTableRowWithStatus('Academic Year', data.enquiry_source_data.academic_year);
            html += createTableRowWithStatus('Source', data.enquiry_source_data.source);
            html += '</table>';
        }
        
        // ====== SECTION 3: MCB Payload - Student Information ======
        if (data.mcb_data) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">👤 Student Information (What MCB Will Receive)</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fafafa;">';
            html += createTableRowWithStatus('Student Name', data.mcb_data.StudentName);
            html += createTableRowWithStatus('Father Name', data.mcb_data.FatherName);
            html += createTableRowWithStatus('Father Email', data.mcb_data.FatherEmailID);
            html += createTableRowWithStatus('Father Mobile', data.mcb_data.FatherMobile);
            html += createTableRowWithStatus('Mother Name', data.mcb_data.MotherName);
            html += createTableRowWithStatus('Mother Mobile', data.mcb_data.MotherMobile);
            html += createTableRowWithStatus('Date of Birth', data.mcb_data.DOB);
            html += createTableRowWithStatus('Address', data.mcb_data.Address1);
            html += '</table>';
        }
        
        // ====== SECTION 4: Academic Information ======
        if (data.mcb_data) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">🎓 Academic Information</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fafafa;">';
            html += createTableRowWithStatus('Class ID', data.mcb_data.ClassID);
            html += createTableRowWithStatus('Academic Year ID', data.mcb_data.AcademicYearID);
            html += '</table>';
        }
        
        // ====== SECTION 5: MCB API Configuration ======
        if (data.mcb_data) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">📋 MCB API Configuration</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fafafa;">';
            html += createTableRowWithStatus('Organization ID (MCB)', data.mcb_data.OrganisationID);
            html += createTableRowWithStatus('Branch ID (MCB)', data.mcb_data.BranchID);
            html += createTableRowWithStatus('Lead Source ID', data.mcb_data.QueryContactSourceID);
            html += '</table>';
        }
        
        // ====== SECTION 6: Marketing Attribution ======
        if (data.marketing_data) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">📊 Marketing Attribution Data</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fafafa;">';
            html += createTableRowWithStatus('utm_source', data.marketing_data.utm_source);
            html += createTableRowWithStatus('utm_medium', data.marketing_data.utm_medium);
            html += createTableRowWithStatus('utm_campaign', data.marketing_data.utm_campaign);
            html += createTableRowWithStatus('gclid (Google)', data.marketing_data.gclid);
            html += createTableRowWithStatus('fbclid (Facebook)', data.marketing_data.fbclid);
            html += '</table>';
        }
        
        // ====== SECTION 7: Remarks ======
        if (data.mcb_data && data.mcb_data.Remarks) {
            html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">📝 Remarks</h3>';
            html += '<div style="background: #fafafa; padding: 15px; border-left: 3px solid #0073aa; border-radius: 3px; margin-bottom: 20px; word-break: break-word;">';
            html += escapeHtml(data.mcb_data.Remarks);
            html += '</div>';
        }
        
        // ====== SECTION 8: Complete JSON Payload ======
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 25px; margin-bottom: 15px; font-size: 16px;">📦 Complete JSON Payload (for API)</h3>';
        html += '<pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; line-height: 1.4; margin-bottom: 20px;">';
        html += escapeHtml(JSON.stringify(data.mcb_data, null, 2));
        html += '</pre>';
        
        html += '</div></div>';
        
        var $modal = $(html);
        
        // Close on button click
        $modal.find('button').on('click', function() {
            $modal.remove();
        });
        
        // Close on background click
        $modal.on('click', function(e) {
            if (e.target === this || $(e.target).closest('.mcb-preview-modal').length) {
                if ($(e.target).is('.mcb-preview-modal')) {
                    $modal.remove();
                }
            }
        });
        
        $('body').append($modal);
    }
    
    // Helper function: Create table row with HTML safe values
    function createTableRow(label, value) {
        var displayValue = (value === null || value === undefined || value === '') ? '<span style="color: #999; font-style: italic;">Not set</span>' : escapeHtml(String(value));
        return '<tr style="border-bottom: 1px solid #e0e0e0;">' +
               '<td style="padding: 12px; font-weight: bold; color: #0073aa; min-width: 160px; background: #f0f7ff;">' + label + '</td>' +
               '<td style="padding: 12px; color: #333;">' + displayValue + '</td>' +
               '</tr>';
    }
    
    // Helper function: Create table row with status indicator
    function createTableRowWithStatus(label, value) {
        var hasValue = (value && value !== 'NA' && value !== '' && value !== null && value !== undefined);
        var status = hasValue ? '<span style="color: green; font-weight: bold; margin-right: 8px;">✅</span>' : '<span style="color: #dc3545; font-weight: bold; margin-right: 8px;">⚠️</span>';
        var displayValue = (value && value !== 'NA') ? escapeHtml(String(value)) : '<span style="color: #999; font-style: italic;">N/A</span>';
        
        return '<tr style="border-bottom: 1px solid #e0e0e0;">' +
               '<td style="padding: 12px; font-weight: bold; color: #0073aa; min-width: 160px; background: #f0f7ff;">' + label + '</td>' +
               '<td style="padding: 12px; color: #333;">' + status + displayValue + '</td>' +
               '</tr>';
    }
    
    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return $('<div/>').text(text).html();
    }
});

</script>
