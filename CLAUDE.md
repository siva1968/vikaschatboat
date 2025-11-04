# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture Overview

This is EduBot Pro, a WordPress plugin that provides AI-powered chatbot functionality for educational institutions. The plugin handles student admission inquiries through conversational workflows.

### Core System Components

- **EduBot_Core** (`includes/class-edubot-core.php`) - Main plugin orchestrator that loads all dependencies and registers hooks
- **EduBot_Workflow_Manager** (`includes/class-edubot-workflow-manager.php`) - Manages multi-step conversation flows for collecting student information
- **EduBot_Session_Manager** (`includes/class-edubot-session-manager.php`) - Handles session persistence using WordPress transients with options fallback
- **EduBot_Chatbot_Engine** (`includes/class-chatbot-engine.php`) - Core message processing and response generation
- **EduBot_Database_Manager** - Manages custom database tables and migrations

### Data Flow Architecture

1. User initiates chat via shortcode on frontend
2. Session Manager creates/retrieves session data
3. Workflow Manager determines current step and processes input
4. Chatbot Engine generates contextual responses
5. Database Manager stores enquiry data and analytics
6. Notification Manager sends WhatsApp/SMS to school staff

### WordPress Integration Pattern

The plugin follows standard WordPress plugin architecture:
- Main plugin file `edubot-pro.php` bootstraps the system
- Classes loaded via `EduBot_Core->load_dependencies()`
- Admin interface uses WordPress admin pages and AJAX
- Frontend uses WordPress shortcodes and AJAX handlers
- Data storage combines WordPress options, transients, and custom tables

## Development Workflow

### Testing
This project uses standalone PHP test files rather than a formal testing framework:

```bash
# Run individual test files
php test_main_plugin_file.php
php test_session_flow.php
php debug_chatbot_flow.php

# Database diagnostics
php edubot_database_diagnostic.php
php check_database_structure.php
```

### Common Debug Files
- `debug_session_flow.php` - Test session management
- `debug_personal_info_parsing.php` - Test input parsing logic  
- `test_multi_flow_system.php` - Test conversation workflows
- `test_notification_status.php` - Test WhatsApp/SMS integration

### Key Configuration Areas
- School settings in admin interface affect conversation flow
- WhatsApp/SMS API credentials required for notifications
- Database tables auto-created on plugin activation
- Session timeout configured in Session Manager (24 hours default)

## Critical Dependencies

The system requires these components to function:
- WordPress transients for session storage
- Custom database tables for enquiries and analytics
- School configuration for institutional branding
- API integrations for WhatsApp/SMS notifications

## Session Management Architecture

Sessions use a dual-storage approach:
1. Primary: WordPress transients (fast, automatic cleanup)
2. Fallback: WordPress options (recovery mechanism)
3. Sessions auto-migrate from options to transients when accessed

This design handles WordPress transient failures gracefully while maintaining performance.