/**
 * EduBot Pro - Frontend JavaScript
 * Handles chatbot widget functionality and interactions
 */

(function($) {
    'use strict';

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
            this.config.sessionId = sessionId;
            this.bindElements();
            this.bindEvents();
            this.loadPersistedData();
            this.startHeartbeat();
            
            console.log('EduBot Chat Widget initialized with session:', sessionId);
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
                if (!self.config.isOpen) {
                    $(this).blur();
                }
            });

            // Handle form submissions (if inline forms exist)
            $(document).on('submit', '#edubot-application-form', function(e) {
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
            this.elements.container.show();
            this.config.isOpen = true;
            this.elements.toggle.addClass('chat-open');
            
            // Focus input after animation
            setTimeout(() => {
                this.elements.input.focus();
            }, 300);

            // Show initial options if no conversation yet
            if (this.config.messageHistory.length === 0) {
                this.showWelcomeOptions();
            }

            // Scroll to bottom
            this.scrollToBottom();
            
            // Track analytics
            this.trackEvent('chat_opened');
        },

        // Close chat widget
        closeChat: function() {
            this.elements.container.hide();
            this.config.isOpen = false;
            this.elements.toggle.removeClass('chat-open');
            this.elements.input.blur();
            
            // Track analytics
            this.trackEvent('chat_closed');
        },

        // Send message
        sendMessage: function(messageText) {
            var message = messageText || this.elements.input.val().trim();
            
            if (!message || this.config.isTyping) {
                return;
            }

            // Add user message to chat
            this.addUserMessage(message);
            
            // Clear input
            this.elements.input.val('');
            this.autoResizeInput();
            
            // Hide options
            this.hideOptions();
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to server
            this.sendToServer(message);
        },

        // Send message to server
        sendToServer: function(message, retryCount = 0) {
            var self = this;
            
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_chatbot',
                    message: message,
                    session_id: this.config.sessionId,
                    nonce: edubot_ajax.nonce
                },
                timeout: 30000,
                success: function(response) {
                    self.hideTypingIndicator();
                    
                    if (response.success) {
                        self.handleServerResponse(response.data);
                    } else {
                        self.handleError(response.data || edubot_ajax.strings.error);
                    }
                },
                error: function(xhr, status, error) {
                    self.hideTypingIndicator();
                    
                    // Retry on failure
                    if (retryCount < self.config.maxRetries) {
                        setTimeout(function() {
                            self.sendToServer(message, retryCount + 1);
                        }, self.config.retryDelay * (retryCount + 1));
                    } else {
                        self.handleError(edubot_ajax.strings.error);
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

        // Format message (handle line breaks, emojis, etc.)
        formatMessage: function(message) {
            return message
                .replace(/\n/g, '<br>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>');
        },

        // Show typing indicator
        showTypingIndicator: function() {
            if (this.config.isTyping) return;
            
            this.config.isTyping = true;
            var typingHtml = '<div class="edubot-typing-indicator" id="edubot-typing">' +
                           '<span>' + edubot_ajax.strings.typing + '</span>' +
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
        showWelcomeOptions: function() {
            var welcomeOptions = [
                { text: edubot_ajax.strings.new_application || 'New Application', value: 'new_admission' },
                { text: edubot_ajax.strings.school_info || 'School Information', value: 'school_info' },
                { text: edubot_ajax.strings.contact_info || 'Contact Information', value: 'contact_info' }
            ];
            
            setTimeout(() => {
                this.showOptions(welcomeOptions);
            }, 1000);
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
                    action: 'edubot_chatbot',
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
            var submitBtn = form.find('.edubot-submit-btn');
            
            // Disable submit button
            submitBtn.prop('disabled', true).addClass('edubot-loading');
            
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_form_submit',
                    form_data: Object.fromEntries(formData),
                    nonce: edubot_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        form.html('<div class="edubot-success-message">' +
                                '<h3>🎉 Application Submitted Successfully!</h3>' +
                                '<p>Application Number: <strong>' + response.data.application_number + '</strong></p>' +
                                '<p>' + response.data.message + '</p></div>');
                    } else {
                        alert('Error: ' + response.data);
                        submitBtn.prop('disabled', false).removeClass('edubot-loading');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    submitBtn.prop('disabled', false).removeClass('edubot-loading');
                }
            });
        },

        // Track analytics events
        trackEvent: function(eventType, eventData = {}) {
            // Send analytics data to server
            $.ajax({
                url: edubot_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'edubot_track_event',
                    event_type: eventType,
                    event_data: eventData,
                    session_id: this.config.sessionId,
                    nonce: edubot_ajax.nonce
                }
            });
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
