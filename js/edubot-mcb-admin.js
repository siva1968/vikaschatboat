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
