/**
 * EduBot MCB Admin - JavaScript
 * Handles manual sync button clicks and status updates
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Handle MCB sync button click
    $(document).on('click', '.mcb-sync-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var enquiryId = $btn.data('enquiry-id');
        var originalText = $btn.text();
        var originalClass = $btn.attr('class');
        
        // Disable button and show loading state
        $btn.prop('disabled', true)
            .addClass('loading')
            .text(edubot_mcb.sync_text)
            .css('pointer-events', 'none');
        
        // Make AJAX request
        $.ajax({
            url: edubot_mcb.ajax_url,
            type: 'POST',
            data: {
                action: 'edubot_mcb_manual_sync',
                enquiry_id: enquiryId,
                nonce: edubot_mcb.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update button state
                    $btn.removeClass(originalClass)
                        .addClass('mcb-sync-btn synced')
                        .text('✓ ' + response.data.message)
                        .css('color', '#28a745');
                    
                    // Update status column if it exists
                    updateStatusColumn(enquiryId, 'synced', response.data.mcb_id);
                    
                    // Show success message
                    showNotification('success', edubot_mcb.sync_success);
                    
                    // Reset button after 3 seconds
                    setTimeout(function() {
                        $btn.prop('disabled', false)
                            .css('pointer-events', 'auto');
                    }, 3000);
                } else {
                    // Handle error
                    showNotification('error', response.data.message);
                    $btn.prop('disabled', false)
                        .removeClass('loading')
                        .text(originalText)
                        .css('pointer-events', 'auto');
                }
            },
            error: function(xhr, status, error) {
                console.error('Sync failed:', error);
                showNotification('error', edubot_mcb.sync_failed);
                
                // Reset button
                $btn.prop('disabled', false)
                    .removeClass('loading')
                    .text(originalText)
                    .css('pointer-events', 'auto');
            }
        });
    });
    
    /**
     * Update status column display
     */
    function updateStatusColumn(enquiryId, status, mcbId) {
        var $row = $('tr[data-enquiry-id="' + enquiryId + '"]');
        if ($row.length) {
            var $statusCell = $row.find('.column-mcb_status');
            if ($statusCell.length) {
                var statusHtml = '<span class="badge badge-success">✓ Synced</span>';
                if (mcbId) {
                    statusHtml += '<br><small>ID: ' + escapeHtml(mcbId) + '</small>';
                }
                $statusCell.html(statusHtml);
            }
        }
    }
    
    /**
     * Show notification toast
     */
    function showNotification(type, message) {
        var bgClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var $notification = $('<div class="alert ' + bgClass + ' alert-dismissible fade show" role="alert">')
            .html(message + '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>')
            .prependTo('body')
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'z-index': '9999',
                'min-width': '300px'
            });
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Handle MCB preview button click
    $(document).on('click', '.mcb-preview-btn', function(e) {
        e.preventDefault();
        
        var enquiryId = $(this).data('enquiry-id');
        
        // Make AJAX request to get MCB preview data
        $.ajax({
            url: edubot_mcb.ajax_url,
            type: 'POST',
            data: {
                action: 'edubot_mcb_preview_data',
                enquiry_id: enquiryId,
                nonce: edubot_mcb.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMCBPreviewModal(response.data);
                } else {
                    showNotification('error', response.data.message || 'Failed to load MCB preview');
                }
            },
            error: function(xhr, status, error) {
                console.error('Preview failed:', error);
                showNotification('error', 'Failed to load MCB preview data');
            }
        });
    });
    
    /**
     * Display MCB preview modal
     */
    function showMCBPreviewModal(data) {
        var html = '<div class="mcb-preview-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;">';
        html += '<div style="background: white; border-radius: 8px; max-width: 1000px; max-height: 90vh; overflow: auto; padding: 30px; box-shadow: 0 5px 40px rgba(0,0,0,0.3);">';
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #0073aa; padding-bottom: 15px;">';
        html += '<h2 style="margin: 0; color: #0073aa;">MCB Data Preview - ' + escapeHtml(data.enquiry_number) + '</h2>';
        html += '<button type="button" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>';
        html += '</div>';
        
        // Student Information Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">👤 Student Information</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        html += tableRow('Student Name', data.mcb_data.StudentName || 'N/A');
        html += tableRow('Parent Name', data.mcb_data.FatherName || 'N/A');
        html += tableRow('Parent Email', data.mcb_data.FatherEmailID || 'N/A');
        html += tableRow('Parent Phone', data.mcb_data.FatherMobile || 'N/A');
        html += tableRow('Date of Birth', data.mcb_data.DOB || 'N/A');
        html += '</table>';
        
        // Academic Information Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">🎓 Academic Information</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        html += tableRow('Class ID', data.mcb_data.ClassID || 'N/A');
        html += tableRow('Academic Year ID', data.mcb_data.AcademicYearID || 'N/A');
        html += '</table>';
        
        // MCB Configuration Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">⚙️ MCB Configuration</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        html += tableRow('Organization ID', data.mcb_data.OrganisationID || 'N/A');
        html += tableRow('Branch ID', data.mcb_data.BranchID || 'N/A');
        html += tableRow('Lead Source ID', data.mcb_data.QueryContactSourceID || 'N/A');
        html += '</table>';
        
        // Marketing Data Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">📊 Marketing Attribution Data</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        html += tableRowWithStatus('utm_source', data.marketing_data.utm_source);
        html += tableRowWithStatus('utm_medium', data.marketing_data.utm_medium);
        html += tableRowWithStatus('utm_campaign', data.marketing_data.utm_campaign);
        html += tableRowWithStatus('gclid (Google)', data.marketing_data.gclid);
        html += tableRowWithStatus('fbclid (Facebook)', data.marketing_data.fbclid);
        html += '</table>';
        
        // Complete Payload Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">📋 Complete MCB Payload (JSON)</h3>';
        html += '<pre style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px;">';
        html += escapeHtml(JSON.stringify(data.mcb_data, null, 2));
        html += '</pre>';
        
        html += '</div></div>';
        
        var $modal = $(html);
        
        // Close modal on button click
        $modal.find('button').on('click', function() {
            $modal.remove();
        });
        
        // Close modal on background click
        $modal.on('click', function(e) {
            if (e.target === this) {
                $modal.remove();
            }
        });
        
        $('body').append($modal);
    }
    
    /**
     * Create a table row
     */
    function tableRow(label, value) {
        return '<tr style="border-bottom: 1px solid #e0e0e0;">' +
               '<td style="padding: 12px; font-weight: bold; color: #0073aa; min-width: 150px;">' + escapeHtml(label) + '</td>' +
               '<td style="padding: 12px; color: #333;">' + escapeHtml(value) + '</td>' +
               '</tr>';
    }
    
    /**
     * Create a table row with status indicator
     */
    function tableRowWithStatus(label, value) {
        var status = value ? '<span style="color: green; font-weight: bold;">✓ Captured</span>' : '<span style="color: #999;">Not captured</span>';
        var displayValue = value || '(empty)';
        return '<tr style="border-bottom: 1px solid #e0e0e0;">' +
               '<td style="padding: 12px; font-weight: bold; color: #0073aa; min-width: 150px;">' + escapeHtml(label) + '</td>' +
               '<td style="padding: 12px; color: #333;">' + escapeHtml(displayValue) + '</td>' +
               '<td style="padding: 12px; text-align: right;">' + status + '</td>' +
               '</tr>';
    }
    
    /**
     * Escape HTML entities
     */
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
