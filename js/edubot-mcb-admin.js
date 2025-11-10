/**
 * EduBot MCB Admin - JavaScript
 * Handles manual sync button clicks and status updates
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // DEBUG: Check if everything is loaded
    console.log('🔵 === EduBot MCB Admin JS Initialized ===');
    console.log('✅ jQuery loaded:', typeof $ === 'function');
    console.log('✅ Document ready fired');
    console.log('📍 Page URL:', window.location.href);
    console.log('📍 edubot_mcb object:', typeof edubot_mcb !== 'undefined' ? 'EXISTS' : 'MISSING!');
    if (typeof edubot_mcb !== 'undefined') {
        console.log('  - ajax_url:', edubot_mcb.ajax_url);
        console.log('  - nonce exists:', !!edubot_mcb.nonce);
    }
    console.log('🔍 Found MCB preview buttons:', $('.mcb-preview-btn').length);
    $('.mcb-preview-btn').each(function(i) {
        console.log('  Button ' + i + ':', {
            'data-enquiry-id': $(this).data('enquiry-id'),
            'html': $(this).html()
        });
    });
    
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
        console.log('🔵 MCB PREVIEW BUTTON CLICKED - Event firing correctly');
        e.preventDefault();
        
        var enquiryId = $(this).data('enquiry-id');
        console.log('📍 Enquiry ID extracted:', enquiryId);
        console.log('⚙️ AJAX URL:', edubot_mcb.ajax_url);
        console.log('🔐 Nonce exists:', !!edubot_mcb.nonce);
        
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
                console.log('✅ AJAX Success Response:', response);
                if (response.success) {
                    console.log('✅ MCB Preview Data:', response.data);
                    showMCBPreviewModal(response.data);
                } else {
                    console.error('❌ Response not successful:', response);
                    showNotification('error', response.data.message || 'Failed to load MCB preview');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Preview AJAX failed:', error, 'Status:', status);
                console.error('❌ XHR Response:', xhr.responseText);
                showNotification('error', 'Failed to load MCB preview data');
            }
        });
    });
    
    /**
     * Display MCB preview modal
     */
    function showMCBPreviewModal(data) {
        // Debug: Log the full response
        console.log('=== MCB Preview Modal Data ===');
        console.log('Full data object:', data);
        console.log('MCB data object:', data.mcb_data);
        console.log('Enquiry source data:', data.enquiry_source_data);
        console.log('MCB settings:', data.mcb_settings);
        
        var html = '<div class="mcb-preview-modal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 10000;">';
        html += '<div style="background: white; border-radius: 8px; max-width: 1000px; max-height: 90vh; overflow: auto; padding: 30px; box-shadow: 0 5px 40px rgba(0,0,0,0.3);">';
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #0073aa; padding-bottom: 15px;">';
        html += '<h2 style="margin: 0; color: #0073aa;">MCB Data Preview - ' + escapeHtml(data.enquiry_number) + '</h2>';
        html += '<button type="button" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>';
        html += '</div>';
        
        // Source Data Section (Diagnostic)
        if (data.enquiry_source_data) {
            html += '<h3 style="color: #666; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-top: 20px; font-size: 0.9em;">📥 Data from Enquiry (Source)</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.9em; background: #f9f9f9;">';
            html += tableRow('Student Name', data.enquiry_source_data.student_name);
            html += tableRow('Parent Name', data.enquiry_source_data.parent_name);
            html += tableRow('Email', data.enquiry_source_data.email);
            html += tableRow('Phone', data.enquiry_source_data.phone);
            html += tableRow('DOB', data.enquiry_source_data.date_of_birth);
            html += tableRow('Grade', data.enquiry_source_data.grade);
            html += tableRow('Board', data.enquiry_source_data.board);
            html += tableRow('Academic Year', data.enquiry_source_data.academic_year);
            html += tableRow('Source', data.enquiry_source_data.source);
            html += '</table>';
        }
        
        // MCB Settings Section (Diagnostic)
        if (data.mcb_settings) {
            html += '<h3 style="color: #666; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-top: 20px; font-size: 0.9em;">⚙️ MCB Configuration (Settings)</h3>';
            html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.9em; background: #f9f9f9;">';
            html += tableRow('Organization ID', data.mcb_settings.organization_id);
            html += tableRow('Branch ID', data.mcb_settings.branch_id);
            html += tableRow('Sync Enabled', data.mcb_settings.sync_enabled ? 'YES ✓' : 'NO ✗');
            html += '</table>';
        }
        
        // Student Information Section - RENAMED from Source to MCB Data
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">👤 Student Information (What MCB Will Receive)</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        
        // Map MCB fields with status indicators
        var mcbFieldsMapped = [
            { label: 'Student Name', value: data.mcb_data.StudentName, field: 'StudentName' },
            { label: 'Parent Name (Father)', value: data.mcb_data.FatherName, field: 'FatherName' },
            { label: 'Parent Email', value: data.mcb_data.FatherEmailID, field: 'FatherEmailID' },
            { label: 'Parent Phone', value: data.mcb_data.FatherMobile, field: 'FatherMobile' },
            { label: 'Mother Name', value: data.mcb_data.MotherName, field: 'MotherName' },
            { label: 'Mother Phone', value: data.mcb_data.MotherMobile, field: 'MotherMobile' },
            { label: 'Date of Birth', value: data.mcb_data.DOB, field: 'DOB' },
            { label: 'Address', value: data.mcb_data.Address1, field: 'Address1' },
            { label: 'Remarks', value: data.mcb_data.Remarks, field: 'Remarks' }
        ];
        
        mcbFieldsMapped.forEach(function(field) {
            var status = (field.value && field.value !== 'NA' && field.value !== '') ? '✅' : '⚠️';
            var displayValue = field.value || 'N/A';
            html += '<tr style="border-bottom: 1px solid #e0e0e0;">' +
                    '<td style="padding: 12px; font-weight: bold; color: #0073aa; min-width: 180px;">' + field.label + ' (' + field.field + ')</td>' +
                    '<td style="padding: 12px; color: #333;">' + status + ' ' + escapeHtml(displayValue) + '</td>' +
                    '</tr>';
        });
        
        html += '</table>';
        
        // Academic Information Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">🎓 Academic Information</h3>';
        html += '<table style="width: 100%; border-collapse: collapse; margin: 15px 0;">';
        html += tableRow('Class ID', data.mcb_data.ClassID || 'N/A');
        html += tableRow('Academic Year ID', data.mcb_data.AcademicYearID || 'N/A');
        html += '</table>';
        
        // MCB Configuration Section
        html += '<h3 style="color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 20px;">📋 MCB API Payload</h3>';
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
