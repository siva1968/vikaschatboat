/**
 * EduBot Pro Frontend JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize chatbot
    if ($('#edubot-chatbot-widget').length) {
        initChatbot();
    }
    
    // Initialize application form
    if ($('#edubot-application').length) {
        initApplicationForm();
    }
    
    /**
     * Initialize Chatbot Functionality
     */
    function initChatbot() {
        const $chatbot = $('#edubot-chatbot-widget');
        const $chatButton = $('#edubot-chat-toggle');
        const $chatWindow = $('#edubot-chat-container');
        const $closeChat = $('#edubot-minimize');
        const $chatInput = $('#edubot-chat-input');
        const $sendButton = $('#edubot-send-btn');
        const $messages = $('#edubot-chat-messages');
        const $typingIndicator = $chatbot.find('.typing-indicator');
        
        let isOpen = false;
        let sessionId = ''; // Store session ID for conversation continuity
        
        // Toggle chat window
        $chatButton.on('click', function() {
            if (!isOpen) {
                $chatWindow.addClass('show');
                $chatbot.addClass('chat-open');
                $chatInput.focus();
                isOpen = true;
            }
        });
        
        // Close chat window
        $closeChat.on('click', function() {
            $chatWindow.removeClass('show');
            $chatbot.removeClass('chat-open');
            isOpen = false;
        });
        
        // Send message on Enter key
        $chatInput.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Send message on button click
        $sendButton.on('click', sendMessage);
        
        // Handle quick actions
        $messages.on('click', '.edubot-quick-action', function() {
            const action = $(this).data('action');
            sendMessage('', action);
        });
        
        /**
         * Send message to chatbot
         */
        function sendMessage(message = '', action = '') {
            message = message || $chatInput.val().trim();
            
            if (!message && !action) return;
            
            // Add user message to chat (if it's a text message)
            if (message) {
                addMessage(message, 'user');
                $chatInput.val('');
            }
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send AJAX request
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_chatbot_response',
                    message: message,
                    action: action,
                    session_id: sessionId, // Include session ID for continuity
                    nonce: edubot_ajax.nonce
                },
                success: function(response) {
                    hideTypingIndicator();
                    
                    if (response.success) {
                        // Store session ID from response for future requests
                        if (response.data.session_id) {
                            sessionId = response.data.session_id;
                        }
                        addMessage(response.data.message, 'bot');
                    } else {
                        addMessage(edubot_ajax.messages.error, 'bot');
                    }
                },
                error: function() {
                    hideTypingIndicator();
                    addMessage(edubot_ajax.messages.error, 'bot');
                }
            });
        }
        
        /**
         * Add message to chat
         */
        function addMessage(text, sender) {
            const isBot = sender === 'bot';
            const avatar = isBot ? '🤖' : '👤';
            const messageClass = isBot ? 'bot-message' : 'user-message';
            
            const messageHtml = `
                <div class="message ${messageClass}">
                    <div class="message-avatar">${avatar}</div>
                    <div class="message-content">
                        <p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
            `;
            
            $messages.append(messageHtml);
            scrollToBottom();
        }
        
        /**
         * Show typing indicator
         */
        function showTypingIndicator() {
            $typingIndicator.show();
            scrollToBottom();
        }
        
        /**
         * Hide typing indicator
         */
        function hideTypingIndicator() {
            $typingIndicator.hide();
        }
        
        /**
         * Scroll to bottom of messages
         */
        function scrollToBottom() {
            $messages.scrollTop($messages[0].scrollHeight);
        }
    }
    
    /**
     * Initialize Application Form
     */
    function initApplicationForm() {
        const $form = $('#edubot-application');
        const $submitButton = $form.find('.submit-button');
        const $buttonText = $submitButton.find('.button-text');
        const $loadingSpinner = $submitButton.find('.loading-spinner');
        
        $form.on('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return;
            }
            
            // Show loading state
            $submitButton.prop('disabled', true);
            $buttonText.hide();
            $loadingSpinner.show();
            
            // Remove any existing messages
            $('.form-message').remove();
            
            // Submit form
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=edubot_submit_application',
                success: function(response) {
                    if (response.success) {
                        showFormMessage('success', response.data.message);
                        $form[0].reset();
                        
                        // Scroll to success message
                        $('html, body').animate({
                            scrollTop: $('.form-message').offset().top - 100
                        }, 500);
                    } else {
                        showFormMessage('error', response.data.message || edubot_ajax.messages.error);
                    }
                },
                error: function() {
                    showFormMessage('error', edubot_ajax.messages.error);
                },
                complete: function() {
                    // Reset button state
                    $submitButton.prop('disabled', false);
                    $buttonText.show();
                    $loadingSpinner.hide();
                }
            });
        });
        
        /**
         * Validate form fields
         */
        function validateForm() {
            let isValid = true;
            const requiredFields = $form.find('[required]');
            
            requiredFields.each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }
            });
            
            // Validate email format
            const $emailField = $form.find('#email');
            const email = $emailField.val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                $emailField.addClass('error');
                isValid = false;
            }
            
            // Check terms agreement
            const $termsCheckbox = $form.find('#agree_terms');
            if (!$termsCheckbox.is(':checked')) {
                $termsCheckbox.closest('.form-group').addClass('error');
                isValid = false;
            } else {
                $termsCheckbox.closest('.form-group').removeClass('error');
            }
            
            if (!isValid) {
                showFormMessage('error', 'Please fill in all required fields correctly.');
            }
            
            return isValid;
        }
        
        /**
         * Show form message
         */
        function showFormMessage(type, message) {
            const messageHtml = `
                <div class="form-message ${type}">
                    ${message}
                </div>
            `;
            
            $form.prepend(messageHtml);
        }
        
        // Remove error styling on input
        $form.on('input change', 'input, select, textarea', function() {
            $(this).removeClass('error');
            $(this).closest('.form-group').removeClass('error');
        });
    }
    
    /**
     * Utility Functions
     */
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notificationHtml = `
            <div class="edubot-notification ${type}">
                ${message}
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        $('body').append(notificationHtml);
        
        const $notification = $('.edubot-notification').last();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Manual close
        $notification.on('click', '.notification-close', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Add notification styles if not already present
     */
    if (!$('#edubot-notification-styles').length) {
        $('head').append(`
            <style id="edubot-notification-styles">
                .edubot-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    max-width: 300px;
                    padding: 15px;
                    border-radius: 6px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    z-index: 999999;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 10px;
                }
                .edubot-notification.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                .edubot-notification.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                .edubot-notification.info {
                    background: #cce7ff;
                    color: #004085;
                    border: 1px solid #b3d7ff;
                }
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 18px;
                    cursor: pointer;
                    color: inherit;
                    padding: 0;
                    line-height: 1;
                }
            </style>
        `);
    }
    
    // Global utility functions
    window.edubot = {
        showNotification: showNotification,
        escapeHtml: escapeHtml
    };
});
