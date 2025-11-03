# 🌊 EduBot Pro - Data Flow & Integration Architecture

**Version:** 1.3.2  
**Document:** Data Flow Diagrams and Integration Points  
**Created:** November 3, 2025

---

## 📋 Table of Contents

1. [Complete Data Flow](#complete-data-flow)
2. [Component Data Exchange](#component-data-exchange)
3. [Database Schema & Relations](#database-schema--relations)
4. [External Integrations](#external-integrations)
5. [Message Flow Sequence](#message-flow-sequence)
6. [Error Handling & Logging](#error-handling--logging)

---

## 🌊 Complete Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER INTERACTION LAYER                       │
├─────────────────────────────────────────────────────────────────┤
│  Frontend Browser                                               │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │ Chatbot Widget (edubot-public.js)                        │  │
│  │                                                           │  │
│  │  User Input → Parse → Sanitize → AJAX POST              │  │
│  │                                                           │  │
│  │  POST /wp-admin/admin-ajax.php                          │  │
│  │  {                                                       │  │
│  │    action: 'edubot_chatbot_response',                   │  │
│  │    message: 'Grade 5, CBSE',                            │  │
│  │    session_id: 'sess_6538e2c3a4f81',                   │  │
│  │    nonce: 'abc123...'                                   │  │
│  │  }                                                       │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────┬───────────────────────────────────────────────┘
                  │
                  │ HTTP POST
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│                   WORDPRESS CORE LAYER                          │
├─────────────────────────────────────────────────────────────────┤
│  AJAX Request Handler                                           │
│  ├─ Check action: 'edubot_chatbot_response'                    │
│  ├─ Verify nonce: wp_verify_nonce()                            │
│  ├─ Check authorization: current_user_can()                    │
│  └─ Route to: EduBot_Shortcode::handle_chatbot_response()     │
└─────────────────┬───────────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│              EDUBOT_SHORTCODE - REQUEST HANDLER                │
├─────────────────────────────────────────────────────────────────┤
│  handle_chatbot_response()                                      │
│  ├─ Extract: $message, $session_id                             │
│  ├─ Verify nonce: wp_verify_nonce()                            │
│  ├─ Sanitize message: sanitize_text_field()                    │
│  ├─ Log request                                                 │
│  └─ Call: generate_response()                                   │
└─────────────────┬───────────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│           MAIN RESPONSE GENERATOR                               │
├─────────────────────────────────────────────────────────────────┤
│  generate_response($message, $action_type, $session_id)        │
│                                                                  │
│  ├─ STEP 1: Parse Information                                  │
│  │  ├─ parse_personal_info($message)                           │
│  │  │  ├─ Extract: name, email, phone                          │
│  │  │  └─ Validate: email format, phone length                │
│  │  │                                                           │
│  │  ├─ parse_academic_info($message)                           │
│  │  │  ├─ Extract: grade, board                                │
│  │  │  └─ Validate against config                              │
│  │  │                                                           │
│  │  └─ parse_additional_info($message)                         │
│  │     ├─ Extract: DOB, gender, parent name, address           │
│  │     └─ Validate: date format, required fields               │
│  │                                                              │
│  ├─ STEP 2: Get/Create Session                                 │
│  │  ├─ get_conversation_session($session_id)                   │
│  │  │  └─ Query: get_transient('edubot_session_' . $id)       │
│  │  │                                                           │
│  │  ├─ If NOT exists:                                          │
│  │  │  ├─ init_conversation_session()                          │
│  │  │  ├─ Set: session_id, flow_type, started_at              │
│  │  │  └─ Save to transients                                   │
│  │  │                                                           │
│  │  └─ Return: $session_data (array)                           │
│  │                                                              │
│  ├─ STEP 3: Determine Current Step                             │
│  │  ├─ Check: $session_data['step']                            │
│  │  ├─ Possible: personal_info, academic, additional, confirm  │
│  │  └─ Default: greeting                                        │
│  │                                                              │
│  └─ STEP 4: Route to Appropriate Handler                       │
│     ├─ If personal_info found:                                 │
│     │  └─ handle_admission_flow_safe($message, 'admission')   │
│     │                                                           │
│     ├─ If academic_info found:                                 │
│     │  └─ handle_admission_flow_safe($message, 'academic')    │
│     │                                                           │
│     ├─ If all data complete:                                   │
│     │  └─ process_final_submission($session_id)                │
│     │                                                           │
│     └─ If general query:                                        │
│        └─ generate_regular_response($message)                  │
└─────────────────┬───────────────────────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────────────────────┐
│           ADMISSION FLOW HANDLER                                │
├─────────────────────────────────────────────────────────────────┤
│  handle_admission_flow_safe($message, $flow_type, $session_id) │
│                                                                  │
│  ├─ CASE 1: 'admission' (Personal Info Stage)                  │
│  │  ├─ Save to session: name, email, phone                     │
│  │  ├─ Update: $session['step'] = 'academic'                   │
│  │  ├─ Format confirmation message                              │
│  │  └─ Return response with next prompt                        │
│  │                                                              │
│  ├─ CASE 2: 'academic_info' (Academic Stage)                   │
│  │  ├─ Save to session: grade, board, academic_year            │
│  │  ├─ Update: $session['step'] = 'additional'                 │
│  │  ├─ Generate confirmation message                            │
│  │  └─ Return response with next prompt                        │
│  │                                                              │
│  ├─ CASE 3: Additional Details (DOB, Gender, Parent, Address)  │
│  │  ├─ Save to session: all additional data                    │
│  │  ├─ Update: $session['step'] = 'confirmation'               │
│  │  ├─ Generate summary                                         │
│  │  └─ Return response with submit options                     │
│  │                                                              │
│  └─ Save updated session                                        │
│     ├─ set_transient('edubot_session_' . $id, $data)          │
│     └─ expire: 24 hours                                        │
└─────────────────┬───────────────────────────────────────────────┘
                  │
                  ↓
       ┌──────────────────┬───────────────────┐
       │                  │                   │
       ↓                  ↓                   ↓
┌────────────────┐  ┌────────────────┐  ┌──────────────────┐
│ REGULAR FLOW   │  │ SUBMISSION     │  │ UPDATE SESSION   │
│                │  │                │  │                  │
│ send response  │  │ process_final_ │  │ save_transient   │
│ w/o DB save    │  │ submission()   │  │                  │
└────────────────┘  └────────┬───────┘  └──────────────────┘
                             │
                             ↓
         ┌───────────────────────────────────────┐
         │  FINAL SUBMISSION HANDLER             │
         ├───────────────────────────────────────┤
         │  process_final_submission()           │
         │                                        │
         │  ├─ VALIDATION STEP                   │
         │  │  ├─ Verify nonce                   │
         │  │  ├─ Check all fields required      │
         │  │  ├─ Validate email format          │
         │  │  └─ Validate phone format          │
         │  │                                    │
         │  ├─ GENERATE ENQUIRY NUMBER           │
         │  │  ├─ Format: ENQ-YYYY-XXXXX        │
         │  │  └─ Store: in $collected_data      │
         │  │                                    │
         │  ├─ DATABASE SAVE                     │
         │  │  └─ EduBot_Database_Manager::     │
         │  │      insert_enquiry($data)        │
         │  │                                    │
         │  ├─ SEND NOTIFICATIONS                │
         │  │  ├─ send_parent_confirmation_()  │
         │  │  ├─ send_school_enquiry_()       │
         │  │  └─ send_whatsapp_confirmation()  │
         │  │                                    │
         │  ├─ UPDATE APPLICATIONS TABLE         │
         │  │  └─ save_to_applications_table()  │
         │  │                                    │
         │  ├─ MARK SESSION COMPLETE            │
         │  │  └─ $session['step'] = 'complete' │
         │  │                                    │
         │  └─ RETURN SUCCESS RESPONSE           │
         │     └─ with enquiry number            │
         └────────────────┬──────────────────────┘
                          │
                          ↓
┌───────────────────────────────────────────────────────────────┐
│              DATABASE PERSISTENCE LAYER                        │
├───────────────────────────────────────────────────────────────┤
│  EduBot_Database_Manager                                      │
│                                                               │
│  ├─ insert_enquiry($data)                                   │
│  │  ├─ Prepare INSERT query                                 │
│  │  ├─ Validate: $data, field types, constraints            │
│  │  ├─ Execute: $wpdb->query()                             │
│  │  ├─ Check: mysql_error(), affected_rows()               │
│  │  └─ Return: Insert success/failure                       │
│  │                                                           │
│  ├─ save_to_applications_table($data)                      │
│  │  ├─ INSERT into wp_edubot_applications                   │
│  │  └─ Link: enquiry_id to applications                     │
│  │                                                           │
│  └─ update_notification_status($enquiry_id, $type, $status)│
│     ├─ UPDATE wp_edubot_enquiries                           │
│     ├─ Set: email_sent=1 or whatsapp_sent=1                 │
│     └─ Where: id = $enquiry_id                              │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ↓
┌───────────────────────────────────────────────────────────────┐
│                  DATABASE STORAGE                              │
├───────────────────────────────────────────────────────────────┤
│  WordPress MySQL Database                                     │
│                                                               │
│  ├─ wp_edubot_enquiries                                      │
│  │  ├─ Table: Stores enquiry submissions                    │
│  │  ├─ Columns: 23 (name, email, phone, grade, board, etc)  │
│  │  └─ Records: All enquiries from chatbot                  │
│  │                                                           │
│  ├─ wp_edubot_applications                                   │
│  │  ├─ Table: Applications summary/admin interface          │
│  │  ├─ Columns: 9 (enquiry_id, student_name, status, etc)  │
│  │  └─ Records: Linked to enquiries                         │
│  │                                                           │
│  ├─ wp_options (WordPress)                                   │
│  │  ├─ Stores: edubot_pro_settings                          │
│  │  ├─ Stores: school configuration                         │
│  │  └─ Stores: API keys, WhatsApp settings                  │
│  │                                                           │
│  └─ wp_transients (WordPress)                                │
│     ├─ Stores: Session data                                 │
│     ├─ Key: edubot_session_[ID]                             │
│     └─ TTL: 24 hours                                        │
└───────────────────────────────────────────────────────────────┘
```

---

## 🔄 Component Data Exchange

```
┌──────────────────────────────────────────────────────────────────┐
│                    COMPONENT INTERACTION MAP                     │
└──────────────────────────────────────────────────────────────────┘

SHORTCODE COMPONENT
├─ INPUT: User message from AJAX
├─ PROCESS: Parse, validate, route
├─ EXCHANGE WITH:
│  ├─ DATABASE_MANAGER → Get/save session data
│  ├─ CHATBOT_ENGINE → Get response
│  ├─ SECURITY_MANAGER → Verify nonce
│  ├─ SCHOOL_CONFIG → Get settings
│  └─ NOTIFICATION_MANAGER → Send emails/WhatsApp
└─ OUTPUT: JSON response to frontend

CHATBOT_ENGINE COMPONENT
├─ INPUT: Message + session data
├─ PROCESS: State machine, generate response
├─ EXCHANGE WITH:
│  ├─ SCHOOL_CONFIG → Get school info
│  └─ API_INTEGRATIONS → Call OpenAI
└─ OUTPUT: Response text + options

DATABASE_MANAGER COMPONENT
├─ INPUT: Data to save
├─ PROCESS: Prepare query, execute
├─ EXCHANGE WITH:
│  ├─ WORDPRESS → Execute queries via $wpdb
│  └─ SECURITY_MANAGER → Sanitize data
└─ OUTPUT: Success/error status

NOTIFICATION_MANAGER COMPONENT
├─ INPUT: Email/phone + message
├─ PROCESS: Send via external services
├─ EXCHANGE WITH:
│  ├─ WORDPRESS → wp_mail()
│  ├─ WHATSAPP_API → Send WhatsApp message
│  └─ SCHOOL_CONFIG → Get templates
└─ OUTPUT: Success/error status

SECURITY_MANAGER COMPONENT
├─ INPUT: User data
├─ PROCESS: Sanitize, validate, verify
├─ EXCHANGE WITH:
│  └─ WORDPRESS → Nonce verification
└─ OUTPUT: Cleaned data

SCHOOL_CONFIG COMPONENT
├─ INPUT: Setting key
├─ PROCESS: Retrieve from database
├─ EXCHANGE WITH:
│  └─ WORDPRESS → get_option()
└─ OUTPUT: Configuration value
```

---

## 💾 Database Schema & Relations

```
┌─────────────────────────────────────────────────────────────────┐
│              DATABASE ENTITY RELATIONSHIP DIAGRAM               │
└─────────────────────────────────────────────────────────────────┘

┌──────────────────────────────┐
│   wp_edubot_enquiries        │
├──────────────────────────────┤
│ id (PK)                      │
│ enquiry_number (UNIQUE)      │◄────┐
│ student_name                 │      │
│ parent_name                  │      │ 1:1
│ email                        │      │
│ phone                        │      │
│ date_of_birth                │      │
│ gender                       │      │
│ grade                        │      │
│ board                        │      │
│ academic_year                │      │
│ address                      │      │
│ ip_address                   │      │
│ user_agent                   │      │
│ utm_data                     │      │
│ gclid, fbclid, click_id_data │      │
│ source                       │      │
│ whatsapp_sent (0/1)          │      │
│ email_sent (0/1)             │      │
│ sms_sent (0/1)               │      │
│ created_at (DATETIME)        │      │
│ status                       │      │
└──────────────────────────────┘      │
                                      │
┌──────────────────────────────┐      │
│ wp_edubot_applications       │      │
├──────────────────────────────┤      │
│ id (PK)                      │      │
│ enquiry_id (FK)──────────────┼──────┘
│ enquiry_number               │
│ student_name                 │
│ parent_email                 │
│ phone                        │
│ grade                        │
│ board                        │
│ status                       │
│ created_at                   │
└──────────────────────────────┘

┌──────────────────────────────┐
│ wp_options (WordPress)       │
├──────────────────────────────┤
│ option_id (PK)               │
│ option_name                  │
│  ├─ edubot_pro_settings      │
│  ├─ edubot_school_email      │
│  ├─ edubot_school_phone      │
│  ├─ edubot_whatsapp_enabled  │
│  ├─ edubot_whatsapp_api_key  │
│  ├─ edubot_primary_color     │
│  └─ [20+ more settings]      │
│ option_value                 │
│ autoload                     │
└──────────────────────────────┘

┌──────────────────────────────┐
│ wp_transients (WordPress)    │
├──────────────────────────────┤
│ transient_id (PK)            │
│ transient_name               │
│  └─ edubot_session_[ID]      │
│ transient_value (JSON)       │
│  ├─ session_id               │
│  ├─ flow_type                │
│  ├─ step                     │
│  ├─ data (array)             │
│  └─ metadata                 │
│ transient_expires (TTL)      │
│  └─ +24 hours from creation  │
└──────────────────────────────┘

QUERIES FLOW:
1. User submits enquiry
   └─→ INSERT into wp_edubot_enquiries
   └─→ Get LAST_INSERT_ID() → enquiry_id
   
2. Check session exists
   └─→ SELECT from wp_transients
   └─→ WHERE transient_name = 'edubot_session_[ID]'
   
3. Get settings
   └─→ SELECT from wp_options
   └─→ WHERE option_name = 'edubot_whatsapp_api_key'
   
4. Save application record
   └─→ INSERT into wp_edubot_applications
   └─→ Reference: enquiry_id from wp_edubot_enquiries
   
5. Update notification status
   └─→ UPDATE wp_edubot_enquiries
   └─→ SET email_sent = 1
   └─→ WHERE id = enquiry_id
```

---

## 🔌 External Integrations

```
┌──────────────────────────────────────────────────────────────────┐
│                    EXTERNAL INTEGRATIONS                         │
└──────────────────────────────────────────────────────────────────┘

1. OPENAI API (ChatGPT)
   ├─ PURPOSE: AI-powered response generation
   ├─ ENDPOINT: https://api.openai.com/v1/chat/completions
   ├─ METHOD: POST (HTTPS)
   ├─ AUTH: Bearer {API_KEY}
   ├─ REQUEST:
   │  └─ {
   │      "model": "gpt-3.5-turbo",
   │      "messages": [
   │        {"role": "system", "content": "You are an admission counselor"},
   │        {"role": "user", "content": "Tell me about your school"}
   │      ]
   │    }
   ├─ RESPONSE:
   │  └─ {
   │      "choices": [{
   │        "message": {"content": "Our school is..."}
   │      }]
   │    }
   ├─ HANDLED BY: EduBot_Chatbot_Engine::handle_ai_response()
   ├─ TIMEOUT: 30 seconds
   ├─ CACHE: None (real-time)
   └─ ERROR HANDLING: Fallback to pre-written response

2. WHATSAPP BUSINESS API
   ├─ PURPOSE: Send notifications to parents
   ├─ ENDPOINT: https://graph.instagram.com/v18.0/{PHONE_ID}/messages
   ├─ METHOD: POST (HTTPS)
   ├─ AUTH: Bearer {ACCESS_TOKEN}
   ├─ REQUEST:
   │  └─ {
   │      "messaging_product": "whatsapp",
   │      "to": "919876543210",
   │      "type": "template",
   │      "template": {
   │        "name": "admission_confirmation",
   │        "language": {"code": "en"},
   │        "parameters": {
   │          "body": {
   │            "parameters": [
   │              {"type": "text", "text": "Rahul"},
   │              {"type": "text", "text": "ENQ-2025-001234"}
   │            ]
   │          }
   │        }
   │      }
   │    }
   ├─ RESPONSE:
   │  └─ {
   │      "messages": [{
   │        "id": "wamid.xxx",
   │        "message_status": "accepted"
   │      }]
   │    }
   ├─ HANDLED BY: Notification_Manager::send_whatsapp()
   ├─ TIMEOUT: 30 seconds
   ├─ RETRY: 3 attempts on failure
   └─ ERROR HANDLING: Log error, continue

3. EMAIL SERVICE (WordPress wp_mail)
   ├─ PURPOSE: Send confirmation emails
   ├─ HANDLER: WordPress wp_mail() function
   ├─ SMTP SERVER: Configured in WordPress
   ├─ REQUEST:
   │  └─ {
   │      "to": "parent@email.com",
   │      "subject": "Admission Enquiry Confirmation",
   │      "message": "[HTML email content]",
   │      "headers": ["Content-Type: text/html"]
   │    }
   ├─ HANDLED BY: send_parent_confirmation_email()
   ├─ TIMEOUT: 10 seconds
   ├─ RETRY: No retry (handled by mail server)
   └─ ERROR HANDLING: Log error, continue

4. SMS GATEWAY (Planned)
   ├─ STATUS: Framework in place, not activated
   ├─ PURPOSE: Send SMS notifications
   ├─ HANDLER: Notification_Manager::send_sms()
   ├─ CONFIGURATION: Admin settings page
   └─ FUTURE: To be integrated with SMS provider

5. ANALYTICS (Visitor Tracking)
   ├─ PURPOSE: Track user interactions
   ├─ HANDLER: EduBot_Visitor_Analytics
   ├─ STORAGE: wp_edubot_analytics table
   ├─ TRACKED EVENTS:
   │  ├─ Chatbot loaded
   │  ├─ Message sent
   │  ├─ Enquiry submitted
   │  └─ Form interactions
   └─ QUERY: Use for admin dashboard reports

DATA FLOW FOR EACH INTEGRATION:

OpenAI:
  Chatbot receives query
  ├─ Check if query matches predefined responses
  ├─ If no match → Call OpenAI API
  ├─ Get AI response
  └─ Return to user

WhatsApp:
  Enquiry submitted
  ├─ Check if WhatsApp enabled
  ├─ Get parent phone + template name
  ├─ Call WhatsApp API
  ├─ Get confirmation/error
  ├─ Update DB: whatsapp_sent status
  └─ Log result

Email:
  Enquiry submitted
  ├─ Build HTML template
  ├─ Get parent + school email addresses
  ├─ Call wp_mail() for each
  ├─ Get success/failure
  ├─ Update DB: email_sent status
  └─ Log result

ERROR SCENARIOS:

If OpenAI fails:
  └─ Use pre-written default response

If WhatsApp fails:
  └─ Set whatsapp_sent = 0, Continue (retry later)

If Email fails:
  └─ Set email_sent = 0, Continue (admin notified)

If Database fails:
  └─ Rollback, Show error to user, Don't send notifications
```

---

## 📡 Message Flow Sequence

```
SEQUENCE: Complete Enquiry Submission

User                Browser               WordPress           EduBot              Database
│                    │                        │                  │                    │
├─ Types message ─→ │                        │                  │                    │
│                    │ AJAX POST             │                  │                    │
│                    ├──────────────────────→│                  │                    │
│                    │ action=chatbot_       │                  │                    │
│                    │ response              │                  │                    │
│                    │ message=Grade 5,CBSE  │                  │                    │
│                    │ session_id=sess_xxx   │                  │                    │
│                    │ nonce=abc123          │                  │                    │
│                    │                       │ Verify CSRF      │                    │
│                    │                       ├─ Check nonce ────────────────────────→
│                    │                       │                  │                    │
│                    │                       │ handle_chatbot   │                    │
│                    │                       │ _response()      │                    │
│                    │                       ├──────────────────┤                    │
│                    │                       │ get_session()    │                    │
│                    │                       │                  ├───────────────────→
│                    │                       │                  │ SELECT from        │
│                    │                       │                  │ transients         │
│                    │                       │ ←─────────────────┤                    │
│                    │                       │ parse_academic() │                    │
│                    │                       ├──────────────────┤                    │
│                    │                       │                  │ extract: grade,    │
│                    │                       │                  │ board              │
│                    │                       │ generate_        │                    │
│                    │                       │ response()       │                    │
│                    │                       ├──────────────────┤                    │
│                    │                       │ update_session() │                    │
│                    │                       ├──────────────────┤ UPDATE session    │
│                    │                       │                  ├───────────────────→
│                    │                       │                  │ set_transient()    │
│                    │                       │                  │ ←────────────────────
│                    │                       │ ← response text  │                    │
│                    │ ← JSON response       │                  │                    │
│                    │ {success: true,       │                  │                    │
│                    │  message: "Perfect!..←────────────────────────────────────────
│                    │  session: sess_xxx}   │                  │                    │
│                    │                       │                  │                    │
│ ← Display bot msg ←│                       │                  │                    │
│                    │                       │                  │                    │
│ Show prompt: "..." │                       │                  │                    │

SEQUENCE: Final Submission

User                Browser               WordPress           EduBot              Database
│                    │                        │                  │                    │
├ Clicks "Submit" ──→│                        │                  │                    │
│                    │ AJAX POST             │                  │                    │
│                    ├──────────────────────→│                  │                    │
│                    │ action=submit_inquiry │                  │                    │
│                    │ session_id=sess_xxx   │                  │                    │
│                    │ nonce=abc123          │                  │                    │
│                    │                       │ process_final_   │                    │
│                    │                       │ submission()     │                    │
│                    │                       ├──────────────────┤                    │
│                    │                       │ validate_data()  │                    │
│                    │                       ├──────────────────┤ INSERT into        │
│                    │                       │ insert_enquiry() ├───────────────────→
│                    │                       │                  │ wp_edubot_         │
│                    │                       │                  │ enquiries          │
│                    │                       │ ← enquiry_id     │ ←────────────────────
│                    │                       │ get_settings()   │                    │
│                    │                       │                  ├───────────────────→
│                    │                       │                  │ SELECT from        │
│                    │                       │ send_emails()    │ wp_options         │
│                    │                       ├──────────────────┤ ←────────────────────
│                    │                       │ send_whatsapp()  │                    │
│                    │                       ├──────────────────┤                    │
│  [External APIs Called]                    │                  │                    │
│                    │                       │ update_status()  │                    │
│                    │                       │                  ├───────────────────→
│                    │                       │                  │ UPDATE notifications
│                    │                       │ ← success        │ ←────────────────────
│                    │ ← JSON response       │                  │                    │
│                    │ {success: true,       │                  │                    │
│                    │  enquiry_number:      │                  │                    │
│                    │  "ENQ-2025-001234"}   │                  │                    │
│                    │                       │                  │                    │
│ ← Display success ←│                       │                  │                    │
│ Show ENQ number   │                        │                  │                    │
```

---

## 🔐 Error Handling & Logging

```
ERROR HANDLING FLOW

┌─────────────────────────────────────────────────────────────┐
│ TRY BLOCK: Execute operation                                │
├─────────────────────────────────────────────────────────────┤
│ Process user input                                          │
│  ├─ Validate data                                           │
│  ├─ Save to database                                        │
│  ├─ Send notifications                                      │
│  └─ Return response                                         │
└────────────────────┬────────────────────────────────────────┘
                     │ Exception / Error?
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ CATCH BLOCK: Handle error                                   │
├─────────────────────────────────────────────────────────────┤
│ 1. LOG ERROR                                                │
│    ├─ Error message                                         │
│    ├─ Error code                                            │
│    ├─ Stack trace                                           │
│    ├─ Session state                                         │
│    └─ User data (sanitized)                                │
│                                                             │
│ 2. DETERMINE SEVERITY                                       │
│    ├─ CRITICAL: Database error, security breach            │
│    │   └─ Notify admin, show generic error to user         │
│    ├─ WARNING: API timeout, email fail                     │
│    │   └─ Log error, continue processing                   │
│    └─ INFO: Validation failure, missing data               │
│        └─ Show user-friendly message                       │
│                                                             │
│ 3. RECOVERY ACTION                                          │
│    ├─ Rollback transaction if needed                       │
│    ├─ Update session with error state                      │
│    ├─ Save error context for debugging                     │
│    └─ Return error response to user                        │
│                                                             │
│ 4. NOTIFY STAKEHOLDERS                                      │
│    ├─ User: "Sorry, something went wrong"                  │
│    ├─ Admin: Detailed error email                          │
│    └─ Logs: Complete error details                         │
└─────────────────────────────────────────────────────────────┘

LOGGING LOCATIONS

WordPress Debug Log:
├─ Path: wp-content/debug.log
├─ Level: error_log() outputs
├─ Format: "[TIME] [TYPE] Message"
├─ Examples:
│  ├─ "EduBot: Enquiry saved (ID: 123, ENQ: ENQ-2025-0001)"
│  ├─ "EduBot: ERROR - Database insert failed"
│  ├─ "EduBot: WhatsApp message queued"
│  └─ "EduBot: Exception during email sending"
└─ Retention: 30 days (configurable)

Database:
├─ Table: wp_edubot_enquiries
├─ Field: status (new, contacted, pending, completed)
├─ Tracking:
│  ├─ email_sent (0=failed, 1=sent)
│  ├─ whatsapp_sent (0=failed, 1=sent)
│  └─ sms_sent (0=failed, 1=sent)
└─ Use: Admin can see which messages failed

Admin Notifications:
├─ Critical errors → Admin email
├─ Failed submissions → Admin dashboard
├─ API failures → Admin notification
└─ Regular reports → Weekly email

User Feedback:
├─ Success: "✅ Enquiry submitted successfully!"
├─ Validation error: "Please enter valid email"
├─ Network error: "Connection error, please retry"
└─ Server error: "Sorry, please try again later"
```

---

## 📊 Data Consistency & Integrity

```
VALIDATION LAYERS

Input → Sanitize → Validate → Process → Verify → Store

LAYER 1: INPUT VALIDATION
├─ Check message not empty
├─ Check message length < 2000 chars
├─ Check session_id format
└─ Verify nonce present

LAYER 2: SANITIZATION
├─ sanitize_text_field() - text inputs
├─ sanitize_email() - email addresses
├─ sanitize_url() - URLs
└─ wp_kses_post() - HTML content

LAYER 3: BUSINESS LOGIC VALIDATION
├─ Name: 2-100 chars, letters/spaces
├─ Email: Valid RFC 5322 format
├─ Phone: 10 digits (India)
├─ Grade: From configured list
├─ Board: From configured list
├─ DOB: Valid YYYY-MM-DD date
└─ Age: Must be school-age

LAYER 4: DATABASE CONSTRAINTS
├─ Primary Key: Unique ID
├─ Unique: enquiry_number
├─ Not Null: Required fields
├─ Data Type: Correct field types
├─ Foreign Key: Link to applications
└─ Check: Date ranges, valid values

LAYER 5: TRANSACTIONAL INTEGRITY
├─ All or Nothing: Inquiry + Application + Notifications
├─ If error during insert → Rollback
├─ If error during send → Keep inquiry, retry notification
├─ If error during update → Log, notify admin
└─ Eventual consistency: All pieces reconciled

CONFLICT RESOLUTION

Scenario 1: Duplicate Enquiry Number
├─ Prevention: UNIQUE constraint on DB
├─ Detection: Query before insert
└─ Resolution: Retry with new number

Scenario 2: Session Expired
├─ Detection: Transient not found
├─ Recovery: Create new session
└─ Notification: "Your session expired, please start over"

Scenario 3: Partial Submission
├─ Detection: Missing required field
├─ Recovery: Request missing field again
└─ Storage: Keep entered data in session

Scenario 4: Duplicate Submission
├─ Detection: Same session_id resubmits
├─ Prevention: Mark session as completed
└─ Resolution: Show previous enquiry number
```

This comprehensive architecture documentation provides a complete view of how EduBot Pro's data flows through the system, from user input through external integrations to database persistence.

