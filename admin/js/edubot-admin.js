/**
 * EduBot Pro - Admin JavaScript
 * Handles admin panel functionality and interactions
 */

jQuery(document).ready(function($) {
    'use strict';

    // Define EdubotAdmin within the jQuery ready scope
    var EdubotAdmin = {
    
        // Initialize all admin functionality
        init: function() {
            this.initTabs();
            this.initColorPickers();
            this.initFileUploads();
            this.initApiTesting();
            this.initDynamicFields();
            this.initCustomFields();
            this.initFormValidation();
            this.initAnalytics();
            this.initNotifications();
            this.initBulkActions();
            this.initAutoSave();
        },

        // Tab navigation
        initTabs: function() {
        $('.edubot-tab-nav a').on('click', function(e) {
            e.preventDefault();
            
            var target = $(this).attr('href');
            var $parent = $(this).closest('.edubot-tabs');
            
            // Update active tab
            $parent.find('.edubot-tab-nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show target content
            $parent.siblings('.edubot-tab-content').removeClass('active');
            $(target).addClass('active');
        });
    },

    // Color picker initialization
    initColorPickers: function() {
        if (typeof $.fn.wpColorPicker !== 'undefined') {
            $('.edubot-color-picker').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    $(this).siblings('.edubot-color-preview').css('background-color', color);
                }
            });
        }
    },

    // File upload handling
    initFileUploads: function() {
        var mediaUploader;
        
        $('.edubot-upload-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $input = $btn.siblings('input[type="hidden"]');
            var $preview = $btn.siblings('.edubot-upload-preview');
            
            // Create media uploader if it doesn't exist
            if (!mediaUploader) {
                mediaUploader = wp.media({
                    title: 'Select Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
            }
            
            // Handle selection
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $input.val(attachment.url);
                $preview.attr('src', attachment.url).show();
            });
            
            mediaUploader.open();
        });

        // School logo upload functionality
        $('.edubot-upload-logo-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $input = $('#edubot_school_logo');
            var $preview = $('.logo-preview');
            var $removeBtn = $('.edubot-remove-logo-btn');
            
            // Create media uploader for logo
            var logoUploader = wp.media({
                title: 'Select School Logo',
                button: {
                    text: 'Use this logo'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // Handle logo selection
            logoUploader.on('select', function() {
                var attachment = logoUploader.state().get('selection').first().toJSON();
                $input.val(attachment.url);
                
                // Update preview
                $preview.html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px; display: block; margin-bottom: 10px;" />');
                
                // Update button text and show remove button
                $btn.text('Change Logo');
                if ($removeBtn.length === 0) {
                    $btn.after('<button type="button" class="button edubot-remove-logo-btn" style="margin-left: 10px;">Remove Logo</button>');
                } else {
                    $removeBtn.show();
                }
            });
            
            logoUploader.open();
        });

        // Remove logo functionality
        $(document).on('click', '.edubot-remove-logo-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $input = $('#edubot_school_logo');
            var $preview = $('.logo-preview');
            var $uploadBtn = $('.edubot-upload-logo-btn');
            
            // Clear values
            $input.val('');
            $preview.empty();
            
            // Update button states
            $uploadBtn.text('Select Logo');
            $btn.hide();
        });
    },

    // API connection testing
    initApiTesting: function() {
        $('.edubot-test-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var service = $btn.data('service');
            var $status = $btn.siblings('.edubot-status-indicator');
            
            // Update button state
            $btn.addClass('testing').text('Testing...');
            $status.removeClass('connected disconnected').addClass('testing')
                   .find('.edubot-status-text').text('Testing');
            
            // Make AJAX request
            $.ajax({
                url: edubot_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_test_api',
                    service: service,
                    nonce: edubot_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $btn.removeClass('testing').addClass('success').text('Connected');
                        $status.removeClass('testing disconnected').addClass('connected')
                               .find('.edubot-status-text').text('Connected');
                        EdubotAdmin.showNotification('success', service + ' API connected successfully!');
                    } else {
                        $btn.removeClass('testing').addClass('error').text('Failed');
                        $status.removeClass('testing connected').addClass('disconnected')
                               .find('.edubot-status-text').text('Disconnected');
                        EdubotAdmin.showNotification('error', 'Failed to connect to ' + service + ': ' + response.data);
                    }
                },
                error: function() {
                    $btn.removeClass('testing').addClass('error').text('Error');
                    $status.removeClass('testing connected').addClass('disconnected')
                           .find('.edubot-status-text').text('Error');
                    EdubotAdmin.showNotification('error', 'Network error while testing ' + service + ' API');
                },
                complete: function() {
                    // Reset button after 3 seconds
                    setTimeout(function() {
                        $btn.removeClass('testing success error').text('Test Connection');
                    }, 3000);
                }
            });
        });
    },

    // Dynamic field management
    initDynamicFields: function() {
        // Add new field
        $('.edubot-add-btn').on('click', function(e) {
            e.preventDefault();
            
            var $container = $(this).siblings('.edubot-dynamic-fields');
            var template = $container.data('template');
            var index = $container.children().length;
            
            // Replace placeholder with actual index
            var newField = template.replace(/\{\{INDEX\}\}/g, index);
            $container.append(newField);
        });
        
        // Remove field
        $(document).on('click', '.edubot-remove-btn', function(e) {
            e.preventDefault();
            $(this).closest('.edubot-field-item').remove();
        });
    },

    // Custom form field builder
    initCustomFields: function() {
        // Add custom field
        $('.edubot-add-custom-field').on('click', function(e) {
            e.preventDefault();
            
            var $container = $(this).siblings('.edubot-custom-fields');
            var index = $container.children().length;
            
            var template = `
                <div class="edubot-custom-field-item">
                    <input type="text" name="custom_fields[${index}][label]" placeholder="Field Label" required />
                    <select name="custom_fields[${index}][type]">
                        <option value="text">Text</option>
                        <option value="email">Email</option>
                        <option value="tel">Phone</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="select">Dropdown</option>
                        <option value="textarea">Textarea</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio</option>
                    </select>
                    <input type="text" name="custom_fields[${index}][options]" placeholder="Options (for select/radio/checkbox)" />
                    <label><input type="checkbox" name="custom_fields[${index}][required]" value="1" /> Required</label>
                    <label><input type="checkbox" name="custom_fields[${index}][collect_lead]" value="1" /> Collect as Lead</label>
                    <button type="button" class="edubot-remove-btn">Remove</button>
                </div>
            `;
            
            $container.append(template);
        });
        
        // Show/hide options field based on field type
        $(document).on('change', '.edubot-custom-field-item select', function() {
            var type = $(this).val();
            var $optionsField = $(this).siblings('input[name*="[options]"]');
            
            if (['select', 'radio', 'checkbox'].includes(type)) {
                $optionsField.show().attr('required', true);
            } else {
                $optionsField.hide().removeAttr('required');
            }
        });
    },

    // Form validation
    initFormValidation: function() {
        $('.edubot-admin-form').on('submit', function(e) {
            var isValid = true;
            var $form = $(this);
            
            // Clear previous errors
            $form.find('.error').removeClass('error');
            $form.find('.error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    $field.after('<span class="error-message">This field is required</span>');
                    isValid = false;
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (value && !emailRegex.test(value)) {
                    $field.addClass('error');
                    $field.after('<span class="error-message">Please enter a valid email address</span>');
                    isValid = false;
                }
            });
            
            // Validate URL fields
            $form.find('input[type="url"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                var urlRegex = /^https?:\/\/.+/;
                
                if (value && !urlRegex.test(value)) {
                    $field.addClass('error');
                    $field.after('<span class="error-message">Please enter a valid URL (starting with http:// or https://)</span>');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                EdubotAdmin.showNotification('error', 'Please fix the errors above before saving');
                
                // Scroll to first error
                var $firstError = $form.find('.error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
            }
        });
    },

    // Analytics dashboard
    initAnalytics: function() {
        this.loadDashboardStats();
        this.initCharts();
        this.initDateRangePicker();
    },

    // Load dashboard statistics
    loadDashboardStats: function() {
        $.ajax({
            url: edubot_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'edubot_get_dashboard_stats',
                nonce: edubot_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    var stats = response.data;
                    
                    // Update dashboard cards
                    $('.edubot-card-value[data-stat="total_applications"]').text(stats.total_applications);
                    $('.edubot-card-value[data-stat="pending_applications"]').text(stats.pending_applications);
                    $('.edubot-card-value[data-stat="total_conversations"]').text(stats.total_conversations);
                    $('.edubot-card-value[data-stat="active_schools"]').text(stats.active_schools);
                    
                    // Update change indicators
                    EdubotAdmin.updateChangeIndicators(stats.changes);
                }
            }
        });
    },

    // Update change indicators
    updateChangeIndicators: function(changes) {
        $.each(changes, function(key, change) {
            var $indicator = $('.edubot-metric-change[data-metric="' + key + '"]');
            var icon = change > 0 ? '↗' : change < 0 ? '↘' : '→';
            var className = change > 0 ? 'positive' : change < 0 ? 'negative' : '';
            
            $indicator.removeClass('positive negative')
                      .addClass(className)
                      .html(icon + ' ' + Math.abs(change) + '%');
        });
    },

    // Initialize charts
    initCharts: function() {
        if (typeof Chart !== 'undefined') {
            this.createApplicationChart();
            this.createConversationChart();
        }
    },

    // Create applications chart
    createApplicationChart: function() {
        var ctx = document.getElementById('applicationsChart');
        if (!ctx) return;
        
        $.ajax({
            url: edubot_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'edubot_get_applications_chart_data',
                nonce: edubot_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'line',
                        data: response.data,
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Applications Over Time'
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            }
        });
    },

    // Create conversations chart
    createConversationChart: function() {
        var ctx = document.getElementById('conversationsChart');
        if (!ctx) return;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edubot_get_conversations_chart_data',
                nonce: edubot_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: response.data,
                        options: {
                            responsive: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Conversation Outcomes'
                                }
                            }
                        }
                    });
                }
            }
        });
    },

    // Date range picker
    initDateRangePicker: function() {
        if (typeof $.fn.daterangepicker !== 'undefined') {
            $('#analytics-date-range').daterangepicker({
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                startDate: moment().subtract(29, 'days'),
                endDate: moment()
            }, function(start, end) {
                EdubotAdmin.updateAnalytics(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            });
        }
    },

    // Update analytics with date range
    updateAnalytics: function(startDate, endDate) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'edubot_get_analytics_data',
                start_date: startDate,
                end_date: endDate,
                nonce: edubot_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update charts and metrics
                    EdubotAdmin.updateCharts(response.data);
                }
            }
        });
    },

    // Notification system
    initNotifications: function() {
        // Auto-hide notifications after 5 seconds
        setTimeout(function() {
            $('.edubot-notification').fadeOut();
        }, 5000);
        
        // Allow manual dismissal
        $(document).on('click', '.edubot-notification .dismiss', function() {
            $(this).closest('.edubot-notification').fadeOut();
        });
    },

    // Show notification
    showNotification: function(type, message) {
        var $notification = $('<div class="edubot-notification ' + type + '">' + message + '</div>');
        $('.edubot-admin-panel').prepend($notification);
        
        setTimeout(function() {
            $notification.fadeOut();
        }, 5000);
    },

    // Bulk actions for applications
    initBulkActions: function() {
        $('#bulk-action-selector').on('change', function() {
            var action = $(this).val();
            if (action) {
                $('#bulk-action-btn').show();
            } else {
                $('#bulk-action-btn').hide();
            }
        });
        
        $('#bulk-action-btn').on('click', function(e) {
            e.preventDefault();
            
            var action = $('#bulk-action-selector').val();
            var selectedIds = [];
            
            $('input[name="application_ids[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                EdubotAdmin.showNotification('warning', 'Please select at least one application');
                return;
            }
            
            if (!confirm('Are you sure you want to ' + action + ' ' + selectedIds.length + ' application(s)?')) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'edubot_bulk_action',
                    bulk_action: action,
                    application_ids: selectedIds,
                    nonce: edubot_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        EdubotAdmin.showNotification('success', response.data.message);
                        location.reload();
                    } else {
                        EdubotAdmin.showNotification('error', response.data);
                    }
                }
            });
        });
        
        // Select all checkbox
        $('#select-all-applications').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('input[name="application_ids[]"]').prop('checked', isChecked);
        });
    },

    // Auto-save functionality
    initAutoSave: function() {
        var autoSaveTimeout;
        var $form = $('.edubot-admin-form');
        
        $form.on('input change', 'input, select, textarea', function() {
            clearTimeout(autoSaveTimeout);
            
            // Show saving indicator
            $('.edubot-autosave-indicator').text('Saving...').show();
            
            autoSaveTimeout = setTimeout(function() {
                EdubotAdmin.performAutoSave($form);
            }, 2000);
        });
    },

    // Perform auto-save
    performAutoSave: function($form) {
        var formData = $form.serialize();
        formData += '&action=edubot_autosave&nonce=' + edubot_admin.nonce;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('.edubot-autosave-indicator').text('Saved').fadeOut(2000);
                } else {
                    $('.edubot-autosave-indicator').text('Save failed').fadeOut(2000);
                }
            },
            error: function() {
                $('.edubot-autosave-indicator').text('Save failed').fadeOut(2000);
            }
        });
    },

    // Export functionality
    exportApplications: function(format) {
        var startDate = $('#export-start-date').val();
        var endDate = $('#export-end-date').val();
        var schoolId = $('#export-school-id').val();
        
        var url = edubot_admin.ajax_url + '?action=edubot_export_applications&format=' + format +
                  '&start_date=' + startDate + '&end_date=' + endDate +
                  '&school_id=' + schoolId + '&nonce=' + edubot_admin.nonce;
        
        window.open(url, '_blank');
    },

    // Live chat simulation
    initChatSimulation: function() {
        $('.edubot-simulate-chat').on('click', function(e) {
            e.preventDefault();
            
            var $modal = $('#chat-simulation-modal');
            var $chatArea = $modal.find('.chat-simulation-area');
            
            // Clear previous chat
            $chatArea.empty();
            
            // Show modal
            $modal.show();
            
            // Start simulation
            EdubotAdmin.simulateConversation($chatArea);
        });
    },

    // Simulate conversation flow
    simulateConversation: function($chatArea) {
        var messages = [
            { type: 'bot', text: 'Hello! I\'m your AI assistant. How can I help you today?' },
            { type: 'user', text: 'I\'m interested in your engineering programs' },
            { type: 'bot', text: 'Great! We offer several engineering programs. What field interests you most?' },
            { type: 'user', text: 'Computer Science' },
            { type: 'bot', text: 'Excellent choice! Our Computer Science program is highly rated. Would you like to start an application?' }
        ];
        
        var index = 0;
        
        function addMessage() {
            if (index < messages.length) {
                var message = messages[index];
                var $message = $('<div class="chat-message ' + message.type + '">' + message.text + '</div>');
                $chatArea.append($message);
                $chatArea.scrollTop($chatArea[0].scrollHeight);
                
                index++;
                setTimeout(addMessage, 1500);
            }
        }
        
        addMessage();
    }
    };

    // Initialize EdubotAdmin
    EdubotAdmin.init();

    // Export for global access if needed
    window.EdubotAdmin = EdubotAdmin;

});
