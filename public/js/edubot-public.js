/**
 * EduBot Pro - Frontend JavaScript
 * Handles chatbot widget functionality and interactions
 */

(function($) {
    'use strict';
    
    // Global error handler for edubot_ajax
    if (typeof edubot_ajax === 'undefined') {
        console.warn('EduBot: edubot_ajax not loaded, creating fallback object');
        window.edubot_ajax = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: '',
            strings: {
                connecting: 'Connecting...',
                typing: 'Bot is typing...',
                error: 'Sorry, something went wrong. Please try again.',
                send: 'Send',
                type_message: 'Type your message...',
                new_application: 'New Application',
                school_info: 'School Information',
                contact_info: 'Contact Information'
            }
        };
    }

    // Version identifier for debugging
    console.log('EduBot: JavaScript version 2.6 - Full Admission Workflow Enabled at', new Date().toISOString());

    // Main chatbot widget class
    window.EduBotChatWidget = {
        // Configuration
        config: {
            sessionId: null,
            isOpen: false,
            isTyping: false,
            messageHistory: [],
            lastActivity: null,
            maxRetries: 3,
            retryDelay: 1000,
            typingDelay: 1000,
            messageQueue: []
        },

        // DOM elements
        elements: {
            widget: null,
            toggle: null,
            container: null,
            messages: null,
            input: null,
            sendBtn: null,
            options: null,
            minimizeBtn: null
        },

        // Initialize the chatbot
        init: function(sessionId) {
            // Validate required objects
            if (typeof edubot_ajax === 'undefined') {
                console.error('EduBot: edubot_ajax object not found. Make sure script is properly localized.');
                return;
            }
            
            if (!edubot_ajax.ajax_url || !edubot_ajax.nonce) {
                console.error('EduBot: Missing AJAX URL or nonce in edubot_ajax object.');
                return;
            }
            
            this.config.sessionId = sessionId;
            this.bindElements();
            this.bindEvents();
            this.loadPersistedData();
            this.checkInitialState();
            this.startHeartbeat();
            
            console.log('EduBot Chat Widget initialized with session:', sessionId);
            console.log('EduBot: AJAX URL:', edubot_ajax.ajax_url);
            console.log('EduBot: Nonce available:', !!edubot_ajax.nonce);
        },

        // Check initial chat state
        checkInitialState: function() {
            // Always start with chat closed to prevent layout issues
            this.config.isOpen = false;
            this.elements.container.removeClass('show');
            this.elements.toggle.removeClass('chat-open');
            console.log('EduBot: Chat initialized in closed state');
            
            // Enable input for when chat is opened
            setTimeout(() => {
                this.elements.input.prop('disabled', false);
                console.log('EduBot: Input enabled');
            }, 100);
        },

        // Bind DOM elements
        bindElements: function() {
            this.elements.widget = $('#edubot-chatbot-widget');
            this.elements.toggle = $('#edubot-chat-toggle');
            this.elements.container = $('#edubot-chat-container');
            this.elements.messages = $('#edubot-chat-messages');
            this.elements.input = $('#edubot-chat-input');
            this.elements.sendBtn = $('#edubot-send-btn');
            this.elements.options = $('#edubot-chat-options');
            this.elements.minimizeBtn = $('#edubot-minimize');
        },

        // Bind event listeners
        bindEvents: function() {
            var self = this;

            // Toggle chat widget
            this.elements.toggle.on('click', function() {
                self.toggleChat();
            });

            // Minimize chat
            this.elements.minimizeBtn.on('click', function() {
                self.closeChat();
            });

            // Send message on button click
            this.elements.sendBtn.on('click', function() {
                self.sendMessage();
            });

            // Send message on Enter key
            this.elements.input.on('keypress', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Auto-resize input
            this.elements.input.on('input', function() {
                self.autoResizeInput();
            });

            // Option button clicks
            $(document).on('click', '.edubot-option-btn', function() {
                var optionValue = $(this).data('value') || $(this).text();
                self.sendMessage(optionValue);
                self.hideOptions();
            });

            // Quick action button clicks
            $(document).on('click', '.quick-action', function() {
                var actionValue = $(this).data('action');
                if (actionValue) {
                    self.sendMessage('', actionValue);
                }
            });

            // EduBot quick action button clicks (for public class)
            $(document).on('click', '.edubot-quick-action', function() {
                var actionValue = $(this).data('action');
                if (actionValue) {
                    console.log('EduBot: Quick action clicked:', actionValue);
                    self.sendMessage('', actionValue);
                }
            });

            // Close chat when clicking outside (on mobile)
            $(document).on('click', function(e) {
                if (self.config.isOpen && 
                    !self.elements.container.is(e.target) && 
                    self.elements.container.has(e.target).length === 0 &&
                    !self.elements.toggle.is(e.target)) {
                    if (window.innerWidth <= 768) {
                        self.closeChat();
                    }
                }
            });

            // Prevent input focus on mobile when chat is closed
            this.elements.input.on('focus', function() {
                // Allow focus if chat is open or opening
                if (!self.config.isOpen && !self.elements.container.is(':visible')) {
                    console.log('EduBot: Preventing focus - chat is closed');
                    $(this).blur();
                } else {
                    console.log('EduBot: Allowing input focus - chat is open');
                }
            });

            // Handle form submissions (if inline forms exist)
            $(document).on('submit', '#edubot-application', function(e) {
                e.preventDefault();
                self.handleFormSubmission($(this));
            });

            // Handle inline chat inputs
            $(document).on('click', '.edubot-inline-send-btn', function() {
                var sessionId = $(this).data('session');
                var input = $('.edubot-inline-input-field[data-session="' + sessionId + '"]');
                var message = input.val().trim();
                
                if (message) {
                    self.sendInlineMessage(message, sessionId);
                    input.val('');
                }
            });

            $(document).on('keypress', '.edubot-inline-input-field', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    var sessionId = $(this).data('session');
                    var message = $(this).val().trim();
                    
                    if (message) {
                        self.sendInlineMessage(message, sessionId);
                        $(this).val('');
                    }
                }
            });
        },

        // Toggle chat open/close
        toggleChat: function() {
            if (this.config.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        },

        // Open chat widget
        openChat: function() {
            this.elements.container.addClass('show');
            this.config.isOpen = true;
            this.elements.toggle.addClass('chat-open');
            
            // Focus input after animation
            setTimeout(() => {
                this.elements.input.focus();
            }, 300);

            // Show initial options if no conversation yet
            if (this.config.messageHistory.length === 0) {
                // Quick actions are handled by buttons in the welcome message HTML
                // No need for additional JavaScript-generated options
                console.log('EduBot: Welcome message with quick actions already displayed');
            }
            
            // Scroll to bottom
            this.scrollToBottom();
            
            // Track analytics
            this.trackEvent('chat_opened');
        },

        // Close chat widget
        closeChat: function() {
            this.elements.container.removeClass('show');
            this.config.isOpen = false;
            this.elements.toggle.removeClass('chat-open');
            this.elements.input.blur();
            
            // Track analytics
            this.trackEvent('chat_closed');
        },

        // Send message
        sendMessage: function(messageText, actionValue) {
            var message = messageText || this.elements.input.val().trim();
            
            if (!message && !actionValue) {
                return;
            }

            if (this.config.isTyping) {
                return;
            }

            // Add user message to chat (only if there's a message to show)
            if (message) {
                this.addUserMessage(message);
            }
            
            // Clear input
            this.elements.input.val('');
            this.autoResizeInput();
            
            // Hide options
            this.hideOptions();
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to server
            this.sendToServer(message, actionValue);
        },

        // Send message to server
        sendToServer: function(message, actionValue, retryCount = 0) {
            var self = this;
            
            var requestData = {
                action: 'edubot_chatbot_response',
                message: message || '',
                session_id: this.config.sessionId,
                nonce: edubot_ajax.nonce
            };
            
            // Add action parameter if provided
            if (actionValue) {
                requestData.action_type = actionValue;
            }
            
            console.log('EduBot: Sending AJAX request:', requestData);
            
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: requestData,
                timeout: 30000,
                success: function(response) {
                    console.log('EduBot: AJAX response:', response);
                    self.hideTypingIndicator();
                    
                    if (response.success) {
                        self.handleServerResponse(response.data);
                    } else {
                        console.error('EduBot: Server returned error:', response.data);
                        var errorText = response.data || 
                                      ((edubot_ajax && edubot_ajax.strings && edubot_ajax.strings.error) ? 
                                       edubot_ajax.strings.error : 'Sorry, something went wrong. Please try again.');
                        self.handleError(errorText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('EduBot: AJAX error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    self.hideTypingIndicator();
                    
                    // Retry on failure
                    if (retryCount < self.config.maxRetries) {
                        console.log('EduBot: Retrying request, attempt:', retryCount + 1);
                        setTimeout(function() {
                            self.sendToServer(message, actionValue, retryCount + 1);
                        }, self.config.retryDelay * (retryCount + 1));
                    } else {
                        console.error('EduBot: Max retries reached, giving up');
                        var errorText = (edubot_ajax && edubot_ajax.strings && edubot_ajax.strings.error) ? 
                                      edubot_ajax.strings.error : 'Sorry, something went wrong. Please try again.';
                        self.handleError(errorText);
                    }
                }
            });
        },

        // Handle server response
        handleServerResponse: function(data) {
            if (data.message) {
                this.addBotMessage(data.message);
            }

            if (data.options && data.options.length > 0) {
                this.showOptions(data.options);
            }

            if (data.application_number) {
                this.showApplicationSuccess(data.application_number);
            }

            // Update session ID for conversation continuity
            if (data.session_id) {
                this.config.sessionId = data.session_id;
                console.log('EduBot: Session ID updated to:', data.session_id);
            }

            // Update session data
            if (data.session_data) {
                this.updateSessionData(data.session_data);
            }

            this.scrollToBottom();
            this.updateLastActivity();
        },

        // Add user message to chat
        addUserMessage: function(message) {
            var messageHtml = this.createMessageHTML(message, 'user');
            this.elements.messages.append(messageHtml);
            this.config.messageHistory.push({
                type: 'user',
                message: message,
                timestamp: new Date()
            });
            this.scrollToBottom();
        },

        // Add bot message to chat
        addBotMessage: function(message) {
            var messageHtml = this.createMessageHTML(message, 'bot');
            this.elements.messages.append(messageHtml);
            this.config.messageHistory.push({
                type: 'bot',
                message: message,
                timestamp: new Date()
            });
            this.scrollToBottom();
        },

        // Create message HTML
        createMessageHTML: function(message, type) {
            var messageClass = type === 'user' ? 'edubot-user-message' : 'edubot-bot-message';
            var formattedMessage = this.formatMessage(message);
            
            return '<div class="' + messageClass + '">' +
                   '<div class="edubot-message-content">' + formattedMessage + '</div>' +
                   '</div>';
        },

        // Format message (handle line breaks, emojis, etc.) with XSS protection
        formatMessage: function(message) {
            // Ensure message is a string
            if (typeof message !== 'string') {
                if (typeof message === 'object' && message !== null) {
                    message = message.message || message.text || JSON.stringify(message);
                } else {
                    message = String(message || '');
                }
            }
            
            // SECURITY: Decode HTML entities that may have been encoded by WordPress
            function decodeHtml(text) {
                var textarea = document.createElement('textarea');
                textarea.innerHTML = text;
                return textarea.value;
            }
            
            // First decode any existing HTML entities from WordPress
            var decoded = decodeHtml(message);
            
            // Format message with markdown and convert URLs to links
            var formatted = decoded
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
            
            // Convert URLs to clickable links
            // Match URLs starting with http://, https://, or www.
            var urlPattern = /(https?:\/\/[^\s<]+)|(www\.[^\s<]+)/gi;
            formatted = formatted.replace(urlPattern, function(url) {
                var href = url;
                // Add protocol if missing (for www. links)
                if (url.indexOf('http') !== 0) {
                    href = 'http://' + url;
                }
                return '<a href="' + href + '" target="_blank" rel="noopener noreferrer" style="color: #0073aa; text-decoration: underline;">' + url + '</a>';
            });
            
            return formatted;
        },

        // Show typing indicator
        showTypingIndicator: function() {
            if (this.config.isTyping) return;
            
            this.config.isTyping = true;
            var typingText = (edubot_ajax && edubot_ajax.strings && edubot_ajax.strings.typing) ? 
                           edubot_ajax.strings.typing : 'Bot is typing...';
            var typingHtml = '<div class="edubot-typing-indicator" id="edubot-typing">' +
                           '<span>' + typingText + '</span>' +
                           '<div class="edubot-typing-dots">' +
                           '<span></span><span></span><span></span>' +
                           '</div></div>';
            
            this.elements.messages.append(typingHtml);
            this.scrollToBottom();
        },

        // Hide typing indicator
        hideTypingIndicator: function() {
            this.config.isTyping = false;
            $('#edubot-typing').remove();
        },

        // Show quick reply options
        showOptions: function(options) {
            this.hideOptions();
            
            var optionsHtml = '';
            options.forEach(function(option) {
                optionsHtml += '<button class="edubot-option-btn" data-value="' + 
                              option.value + '">' + option.text + '</button>';
            });
            
            this.elements.options.html(optionsHtml).show();
        },

        // Hide options
        hideOptions: function() {
            this.elements.options.hide().empty();
        },

        // Show welcome options
        // Deprecated: Welcome options now handled by HTML quick action buttons
        showWelcomeOptions: function() {
            // This method has been disabled because welcome options are now 
            // handled by quick action buttons in the welcome message HTML.
            // This prevents errors from trying to access undefined localized strings.
            console.log('EduBot: showWelcomeOptions called but disabled - using HTML quick actions instead');
            return false;
        },

        // Show application success message
        showApplicationSuccess: function(applicationNumber) {
            var successHtml = '<div class="edubot-success-message">' +
                            '<div class="edubot-success-icon">🎉</div>' +
                            '<div class="edubot-success-text">' +
                            '<strong>Application Submitted Successfully!</strong><br>' +
                            'Application Number: <strong>' + applicationNumber + '</strong>' +
                            '</div></div>';
            
            this.elements.messages.append(successHtml);
            this.scrollToBottom();
        },

        // Auto-resize input field
        autoResizeInput: function() {
            var input = this.elements.input[0];
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        },

        // Scroll messages to bottom
        scrollToBottom: function() {
            var messages = this.elements.messages[0];
            messages.scrollTop = messages.scrollHeight;
        },

        // Handle errors
        handleError: function(errorMessage) {
            this.addBotMessage(errorMessage);
            
            // Show retry options
            var retryOptions = [
                { text: 'Try Again', value: 'retry' },
                { text: 'Contact Support', value: 'contact_info' }
            ];
            
            this.showOptions(retryOptions);
        },

        // Update session data
        updateSessionData: function(sessionData) {
            // Store in localStorage for persistence
            localStorage.setItem('edubot_session_' + this.config.sessionId, JSON.stringify(sessionData));
        },

        // Load persisted data
        loadPersistedData: function() {
            var savedData = localStorage.getItem('edubot_session_' + this.config.sessionId);
            if (savedData) {
                try {
                    var sessionData = JSON.parse(savedData);
                    // Restore conversation history if needed
                    if (sessionData.conversation_log && sessionData.conversation_log.length > 0) {
                        this.restoreConversation(sessionData.conversation_log);
                    }
                } catch (e) {
                    console.warn('Failed to load persisted chat data:', e);
                }
            }
        },

        // Restore conversation from history
        restoreConversation: function(conversationLog) {
            var self = this;
            conversationLog.forEach(function(entry) {
                if (entry.type === 'user') {
                    self.addUserMessage(entry.message);
                } else if (entry.type === 'bot') {
                    self.addBotMessage(entry.message);
                }
            });
        },

        // Send inline message (for embedded chats)
        sendInlineMessage: function(message, sessionId) {
            var messagesContainer = $('#edubot-inline-messages-' + sessionId);
            
            // Add user message
            var userMessageHtml = '<div class="edubot-user-message">' +
                                '<div class="edubot-message-content">' + message + '</div></div>';
            messagesContainer.append(userMessageHtml);
            
            // Scroll to bottom
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
            
            // Send to server (using the same endpoint)
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_chatbot_response',
                    message: message,
                    session_id: sessionId,
                    nonce: edubot_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.message) {
                        var botMessageHtml = '<div class="edubot-bot-message">' +
                                           '<div class="edubot-message-content">' + 
                                           response.data.message + '</div></div>';
                        messagesContainer.append(botMessageHtml);
                        messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
                    }
                }
            });
        },

        // Handle form submission
        handleFormSubmission: function(form) {
            var formData = new FormData(form[0]);
            var submitBtn = form.find('.submit-button');
            
            // Capture UTM parameters from URL
            var urlParams = new URLSearchParams(window.location.search);
            var utm_params = {};
            
            // List of UTM/tracking parameters to capture
            var param_list = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 
                             'gclid', 'fbclid', 'msclkid', 'ttclid', 'twclid', '_kenshoo_clickid', 
                             'irclickid', 'li_fat_id', 'sc_click_id', 'yclid'];
            
            // Extract each parameter from URL
            param_list.forEach(function(param) {
                if (urlParams.has(param)) {
                    utm_params[param] = urlParams.get(param);
                }
            });
            
            console.log('EduBot: Captured UTM parameters:', utm_params);
            
            // Disable submit button
            submitBtn.prop('disabled', true).addClass('loading');
            
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_submit_application',
                    edubot_nonce: edubot_ajax.nonce,
                    utm_params: utm_params,
                    student_name: formData.get('student_name'),
                    date_of_birth: formData.get('date_of_birth'),
                    grade: formData.get('grade'),
                    gender: formData.get('gender'),
                    parent_name: formData.get('parent_name'),
                    email: formData.get('email'),
                    phone: formData.get('phone'),
                    address: formData.get('address'),
                    educational_board: formData.get('educational_board'),
                    academic_year: formData.get('academic_year'),
                    special_requirements: formData.get('special_requirements'),
                    marketing_consent: formData.get('marketing_consent') ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        form.html('<div class="success-message" style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 6px; color: #155724;">' +
                                '<h3 style="margin-top: 0;">🎉 Application Submitted Successfully!</h3>' +
                                '<p><strong>Application Number:</strong> ' + response.data.application_number + '</p>' +
                                '<p>' + response.data.message + '</p></div>');
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error occurred'));
                        submitBtn.prop('disabled', false).removeClass('loading');
                    }
                },
                error: function(xhr, status, error) {
                    alert('An error occurred: ' + error + '. Please try again.');
                    console.error('Form submission error:', xhr, status, error);
                    submitBtn.prop('disabled', false).removeClass('loading');
                }
            });
        },

        // Track analytics events
        trackEvent: function(eventType, eventData = {}) {
            // Tracking disabled - backend handler not implemented
            console.log('EduBot: Event tracked (local only):', eventType, eventData);
        },

        // Update last activity timestamp
        updateLastActivity: function() {
            this.config.lastActivity = new Date();
        },

        // Start heartbeat to keep session alive
        startHeartbeat: function() {
            var self = this;
            setInterval(function() {
                if (self.config.isOpen) {
                    self.trackEvent('heartbeat');
                }
            }, 60000); // Every minute
        },

        // Clean up old sessions
        cleanupOldSessions: function() {
            var keys = Object.keys(localStorage);
            keys.forEach(function(key) {
                if (key.startsWith('edubot_session_')) {
                    try {
                        var data = JSON.parse(localStorage.getItem(key));
                        var lastActivity = new Date(data.last_activity);
                        var now = new Date();
                        
                        // Remove sessions older than 24 hours
                        if (now - lastActivity > 24 * 60 * 60 * 1000) {
                            localStorage.removeItem(key);
                        }
                    } catch (e) {
                        localStorage.removeItem(key);
                    }
                }
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Clean up old sessions
        EduBotChatWidget.cleanupOldSessions();
        
        // Auto-initialize if widget exists and session ID is provided
        if ($('#edubot-chatbot-widget').length > 0) {
            // Session ID will be set via inline script in PHP
            console.log('EduBot Chat Widget DOM ready');
        }
    });

    // Expose globally for manual initialization
    window.EduBot = EduBotChatWidget;

})(jQuery);
