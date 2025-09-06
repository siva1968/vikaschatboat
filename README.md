# EduBot Pro - AI Chatbot for Educational Institutions

A comprehensive WordPress plugin that provides AI-powered chatbot functionality specifically designed for educational institutions with multi-school support, white-label branding, and advanced application management.

## Features

### 🤖 AI-Powered Chatbot
- OpenAI GPT integration for natural conversations
- Contextual responses based on school information
- Lead qualification and application guidance
- Multi-language support ready

### 🏫 Multi-School Support
- Manage multiple educational institutions
- School-specific configurations and branding
- Customizable admission processes per school
- Individual analytics and reporting

### 🎨 White-Label Branding
- Custom colors, logos, and messaging
- School-specific chatbot personalities
- Branded application forms
- Professional appearance

### 📱 Multi-Channel Notifications
- WhatsApp Business API integration
- Email notifications (SMTP, SendGrid, Mailgun)
- SMS integration (Twilio, AWS SNS)
- Follow-up automation

### 📊 Advanced Analytics
- Conversation tracking and analytics
- Application conversion rates
- Lead source analysis
- Performance metrics dashboard

### 🔒 Security & Compliance
- API key encryption (AES-256-CBC)
- Rate limiting and spam protection
- Session management
- GDPR compliance features

## Installation

1. **Upload the Plugin**
   - Download the plugin files
   - Upload to `/wp-content/plugins/edubot-pro/`
   - Or install via WordPress admin panel

2. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "EduBot Pro" and click "Activate"

3. **Initial Configuration**
   - Navigate to EduBot Pro → Settings
   - Configure your OpenAI API key
   - Add your first school configuration

## Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher
- **OpenAI API Key**: Required for AI functionality

## Quick Start Guide

### 1. API Configuration
```
EduBot Pro → API Settings
- Add your OpenAI API key
- Configure notification services (optional)
- Test connections
```

### 2. School Setup
```
EduBot Pro → Schools
- Add new school
- Configure branding (colors, logo, messaging)
- Set up custom form fields
- Define admission requirements
```

### 3. Embed Chatbot
```
Use shortcode: [edubot_chat school_id="1"]
Or use the widget in Appearance → Widgets
Or enable site-wide floating chat
```

## Shortcodes

### Main Chatbot
```php
[edubot_chat school_id="1" style="widget"]
[edubot_chat school_id="1" style="inline"]
[edubot_chat school_id="1" style="fullscreen"]
```

### Application Form
```php
[edubot_application_form school_id="1"]
```

### Analytics Dashboard
```php
[edubot_analytics school_id="1" period="30days"]
```

## Configuration Options

### OpenAI Settings
- **API Key**: Your OpenAI API key
- **Model**: GPT model to use (gpt-3.5-turbo, gpt-4)
- **Temperature**: Response creativity (0.0 - 1.0)
- **Max Tokens**: Maximum response length

### WhatsApp Integration
- **Provider**: Choose from multiple WhatsApp Business providers
- **API Key**: Provider-specific API key
- **Phone Number**: Your WhatsApp Business number
- **Template Messages**: Pre-approved message templates

### Email Settings
- **Provider**: SMTP, SendGrid, or Mailgun
- **API Keys/Credentials**: Provider-specific settings
- **From Address**: Sender email address
- **Templates**: Customizable email templates

### SMS Configuration
- **Provider**: Twilio, AWS SNS, or other SMS providers
- **API Credentials**: Provider-specific settings
- **Sender ID**: Your SMS sender identification

## Database Structure

### Schools Table
```sql
wp_edubot_schools
- id, name, domain, branding_settings
- contact_info, admission_requirements
- custom_fields, status, created_at
```

### Applications Table
```sql
wp_edubot_applications
- id, school_id, student_name, email, phone
- course_interest, status, application_data
- source, submitted_at, updated_at
```

### Analytics Table
```sql
wp_edubot_analytics
- id, school_id, session_id, event_type
- event_data, user_agent, ip_address
- timestamp
```

### Chat Sessions Table
```sql
wp_edubot_chat_sessions
- id, school_id, session_id, user_id
- conversation_data, status, started_at
- last_activity, ended_at
```

## API Endpoints

### Public API
```
POST /wp-json/edubot/v1/chat
POST /wp-json/edubot/v1/application
GET  /wp-json/edubot/v1/schools
```

### Admin API
```
GET  /wp-json/edubot/v1/analytics
POST /wp-json/edubot/v1/export
GET  /wp-json/edubot/v1/applications
```

## Hooks and Filters

### Actions
```php
// Before chatbot response
do_action('edubot_before_response', $message, $school_id);

// After application submission
do_action('edubot_application_submitted', $application_data);

// After notification sent
do_action('edubot_notification_sent', $type, $recipient, $data);
```

### Filters
```php
// Modify chatbot response
$response = apply_filters('edubot_chatbot_response', $response, $context);

// Customize form fields
$fields = apply_filters('edubot_application_fields', $fields, $school_id);

// Modify notification content
$content = apply_filters('edubot_notification_content', $content, $type);
```

## Customization

### Custom CSS
```css
/* Override chatbot styles */
.edubot-chat-widget {
    /* Your custom styles */
}

/* Custom message bubbles */
.edubot-message.bot {
    /* Bot message styles */
}

.edubot-message.user {
    /* User message styles */
}
```

### Custom JavaScript
```javascript
// Extend chatbot functionality
EdubotPublic.on('message_received', function(message) {
    // Custom handling
});

// Custom form validation
EdubotPublic.on('form_submit', function(formData) {
    // Custom validation
});
```

## Troubleshooting

### Common Issues

1. **Chatbot not responding**
   - Check OpenAI API key configuration
   - Verify internet connection
   - Check browser console for errors

2. **Notifications not sending**
   - Verify API credentials for notification services
   - Check notification settings
   - Review logs in admin panel

3. **Styling issues**
   - Check for CSS conflicts
   - Ensure proper theme compatibility
   - Clear caching if using cache plugins

### Debug Mode
Enable debug mode in `wp-config.php`:
```php
define('EDUBOT_DEBUG', true);
```

This will enable detailed logging in the admin panel.

## Performance Optimization

### Caching
- Compatible with major caching plugins
- Built-in session caching
- Database query optimization

### CDN Support
- All assets can be served via CDN
- Static file optimization
- Image compression support

## Security Features

### Data Protection
- API key encryption
- SQL injection prevention
- XSS protection
- CSRF protection via nonces

### Rate Limiting
- Configurable rate limits per IP
- Bot detection and blocking
- Spam prevention

### Privacy Compliance
- GDPR compliance features
- Data export/deletion tools
- Privacy policy integration
- Cookie consent management

## Support & Documentation

### Getting Help
- **Documentation**: [https://edubotpro.com/docs](https://edubotpro.com/docs)
- **Support Tickets**: [https://edubotpro.com/support](https://edubotpro.com/support)
- **Community Forum**: [https://edubotpro.com/forum](https://edubotpro.com/forum)

### Updates
- Automatic update notifications
- Backward compatibility maintained
- Migration tools provided for major updates

## Changelog

### Version 1.0.0
- Initial release
- OpenAI GPT integration
- Multi-school support
- WhatsApp, Email, SMS notifications
- Analytics dashboard
- White-label branding
- Security features

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by the EduBot Pro Team with contributions from the WordPress and AI communities.

---

**Ready to transform your educational institution's student engagement?**

Install EduBot Pro today and start converting visitors into students with the power of AI!
