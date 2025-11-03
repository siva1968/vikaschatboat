# 🔄 EduBot Pro - Admission Enquiry Workflow

**Version:** 1.3.2  
**Document:** Detailed Workflow Diagrams and Process Flows  
**Created:** November 3, 2025

---

## 📋 Table of Contents

1. [Complete Enquiry Workflow](#complete-enquiry-workflow)
2. [Personal Information Collection](#personal-information-collection)
3. [Academic Information Collection](#academic-information-collection)
4. [Final Submission & Confirmation](#final-submission--confirmation)
5. [Alternative Flows](#alternative-flows)
6. [State Machine](#state-machine)

---

## 🎯 Complete Enquiry Workflow

```
START: User visits website
    ↓
User sees chatbot widget (bottom right)
    ↓
┌─────────────────────────────────────────┐
│  STEP 1: CHATBOT INITIALIZATION         │
└─────────────────────────────────────────┘
    │
    ├─→ Check if conversation in session
    │   ├─ YES → Resume existing conversation
    │   └─ NO → Show greeting message
    │
    └─→ Display: "Welcome to [School Name]!"
        "We are currently accepting applications for AY 2026-27"
        [New Application] [School Info] [Contact Info]
    ↓
User clicks [New Application]
    ↓
┌─────────────────────────────────────────┐
│  STEP 2: CREATE SESSION                 │
└─────────────────────────────────────────┘
    │
    ├─→ Generate unique session ID: sess_XXXXX
    ├─→ Initialize session data structure
    ├─→ Save session to WordPress transients
    ├─→ Log session creation
    └─→ Return session ID to frontend
    ↓
┌──────────────────────────────────────────────────────────────┐
│  STEP 3: PERSONAL INFORMATION COLLECTION                    │
│  (Handled in handle_admission_flow_safe() method)           │
└──────────────────────────────────────────────────────────────┘
    │
    ├─→ Display: "Please help me with your details:"
    │   "👶 Student Name"
    │   "📱 Mobile Number"
    │   "📧 Email Address"
    │
    └─→ Await user input (CASE 1: Structured format)
        │
        └─→ User enters: "Name: Rahul, Phone: 9876543210, Email: parent@email.com"
            │
            ├─→ parse_personal_info(message) extracts:
            │   ├─ student_name: "Rahul"
            │   ├─ phone: "9876543210"
            │   └─ email: "parent@email.com"
            │
            ├─→ Validate extracted data:
            │   ├─ Email format valid? ✓
            │   ├─ Phone length valid? ✓
            │   └─ Name not empty? ✓
            │
            ├─→ Save to session:
            │   $session_data['data']['student_name'] = "Rahul"
            │   $session_data['data']['phone'] = "9876543210"
            │   $session_data['data']['email'] = "parent@email.com"
            │
            ├─→ Update step counter:
            │   $session_data['step'] = 'academic'
            │
            └─→ Confirm and ask for next info:
                "✅ Personal Information Complete!"
                "👶 Student: Rahul"
                "📧 Email: parent@email.com"
                "📱 Phone: 9876543210"
                
                "Step 2: Academic Information 🎓"
                "Please share:
                • Grade/Class seeking admission for
                • Board Preference (CBSE/CAIE)"
    ↓
┌──────────────────────────────────────────────────────────────┐
│  STEP 4: ACADEMIC INFORMATION COLLECTION                    │
└──────────────────────────────────────────────────────────────┘
    │
    └─→ User enters: "Grade 5, CBSE"
        │
        ├─→ parse_academic_info(message) extracts:
        │   ├─ grade: "Grade 5"
        │   └─ board: "CBSE"
        │
        ├─→ Validate against configured options:
        │   ├─ Grade in ['Pre-KG', 'LKG', ..., 'Grade 12']? ✓
        │   └─ Board in ['CBSE', 'CAIE', 'State']? ✓
        │
        ├─→ Save to session:
        │   $session_data['data']['grade'] = "Grade 5"
        │   $session_data['data']['board'] = "CBSE"
        │   $session_data['data']['academic_year'] = "2026-27"
        │
        ├─→ Update step counter:
        │   $session_data['step'] = 'additional'
        │
        └─→ Ask for additional info:
            "Perfect! Now please provide additional details:"
            "📅 Date of Birth (YYYY-MM-DD)"
            "⚧ Gender (Male/Female/Other)"
            "👨‍👩‍👧‍👦 Parent/Guardian Name"
            "🏠 Full Address"
    ↓
┌──────────────────────────────────────────────────────────────┐
│  STEP 5: ADDITIONAL INFORMATION COLLECTION                  │
└──────────────────────────────────────────────────────────────┘
    │
    └─→ User enters: "DOB: 2015-05-15, Gender: Male, Parent: Rajesh Kumar, 
                      Address: 123 Main Street, City"
        │
        ├─→ parse_additional_info(message) extracts:
        │   ├─ date_of_birth: "2015-05-15"
        │   ├─ gender: "Male"
        │   ├─ parent_name: "Rajesh Kumar"
        │   └─ address: "123 Main Street, City"
        │
        ├─→ Validate data:
        │   ├─ Date format valid? ✓
        │   ├─ Age calculated (10 years old) ✓
        │   └─ All required fields present? ✓
        │
        ├─→ Save to session:
        │   $session_data['data']['date_of_birth'] = "2015-05-15"
        │   $session_data['data']['gender'] = "Male"
        │   $session_data['data']['parent_name'] = "Rajesh Kumar"
        │   $session_data['data']['address'] = "123 Main Street, City"
        │
        ├─→ Update step counter:
        │   $session_data['step'] = 'confirmation'
        │
        └─→ Generate confirmation summary:
            "📋 ADMISSION ENQUIRY SUMMARY"
            "👤 Student Details:"
            "  • Name: Rahul"
            "  • DOB: 2015-05-15 (Age: 10 years)"
            "  • Gender: Male"
            "🎓 Academic Details:"
            "  • Grade: Grade 5"
            "  • Board: CBSE"
            "  • Academic Year: 2026-27"
            "👨‍👩‍👧‍👦 Parent Details:"
            "  • Name: Rajesh Kumar"
            "  • Email: parent@email.com"
            "  • Phone: 9876543210"
            "  • Address: 123 Main Street, City"
            
            [✅ Submit] [✏️ Edit] [❌ Cancel]
    ↓
┌──────────────────────────────────────────────────────────────┐
│  STEP 6: FINAL SUBMISSION & CONFIRMATION                    │
└──────────────────────────────────────────────────────────────┘
    │
    └─→ User clicks [✅ Submit]
        │
        ├─→ process_final_submission(session_id) called
        │
        ├─→ STEP 6.1: Generate Enquiry Number
        │   │
        │   └─→ Generate: ENQ-2025-001234
        │       (Format: ENQ-YYYY-SEQUENTIAL_ID)
        │
        ├─→ STEP 6.2: Save to Database
        │   │
        │   └─→ EduBot_Database_Manager::save_enquiry()
        │       │
        │       ├─→ Prepare INSERT query:
        │       │   INSERT INTO wp_edubot_enquiries (
        │       │     enquiry_number, student_name, date_of_birth, grade, board,
        │       │     academic_year, parent_name, email, phone, address, gender,
        │       │     ip_address, user_agent, source, created_at, status
        │       │   ) VALUES (...)
        │       │
        │       ├─→ Validate all data before insert
        │       ├─→ Execute query with prepared statement
        │       ├─→ Verify insert successful
        │       ├─→ Get new enquiry ID
        │       └─→ Log: "Enquiry saved with ID: X"
        │
        ├─→ STEP 6.3: Send Parent Confirmation Email
        │   │
        │   └─→ send_parent_confirmation_email() called
        │       │
        │       ├─→ Build HTML email template:
        │       │   [Header with school logo and colors]
        │       │   "✅ Enquiry Successfully Submitted!"
        │       │   [Enquiry Reference Box: ENQ-2025-001234]
        │       │   [Enquiry Details Table]
        │       │   [Next Steps Section]
        │       │   [Contact Information]
        │       │   [Footer]
        │       │
        │       ├─→ Send via wp_mail():
        │       │   To: parent@email.com
        │       │   Subject: "Admission Enquiry Confirmation - [School Name]"
        │       │   Content-Type: text/html
        │       │
        │       ├─→ Verify email sent
        │       ├─→ Update DB: email_sent = 1
        │       └─→ Log: "Confirmation email sent to parent@email.com"
        │
        ├─→ STEP 6.4: Send School Notification Email
        │   │
        │   └─→ send_school_enquiry_notification() called
        │       │
        │       ├─→ Get school email from settings
        │       ├─→ Build school notification email:
        │       │   "🔔 New Admission Enquiry"
        │       │   "⚡ Priority: Contact within 24 hours"
        │       │   [Student Information]
        │       │   [Parent Contact Details]
        │       │   [Required Actions]
        │       │
        │       ├─→ Send via wp_mail():
        │       │   To: admissions@school.edu
        │       │   Subject: "New Admission Enquiry - ENQ-2025-001234"
        │       │
        │       └─→ Log: "School notification sent"
        │
        ├─→ STEP 6.5: Send WhatsApp Notification
        │   │
        │   └─→ Notification_Manager::send_whatsapp() called
        │       │
        │       ├─→ Check if WhatsApp enabled in settings
        │       ├─→ Get WhatsApp API key and phone ID
        │       ├─→ Check if phone number valid
        │       │
        │       ├─→ If enabled:
        │       │   ├─→ Get WhatsApp template name from settings
        │       │   ├─→ Prepare message with parameters:
        │       │   │   {
        │       │   │     "student_name": "Rahul",
        │       │   │     "enquiry_number": "ENQ-2025-001234",
        │       │   │     "grade": "Grade 5"
        │       │   │   }
        │       │   ├─→ Call WhatsApp Business API
        │       │   ├─→ Log: "WhatsApp message queued for 9876543210"
        │       │   └─→ Update DB: whatsapp_sent = 1
        │       │
        │       └─→ If disabled:
        │           └─→ Skip WhatsApp sending
        │
        ├─→ STEP 6.6: Save to Applications Table
        │   │
        │   └─→ EduBot_Database_Manager::save_to_applications_table()
        │       │
        │       ├─→ INSERT INTO wp_edubot_applications:
        │       │   enquiry_id, enquiry_number, student_name,
        │       │   parent_email, phone, grade, board, status
        │       │
        │       └─→ Log: "Application saved to applications table"
        │
        ├─→ STEP 6.7: Update Session
        │   │
        │   └─→ Mark session as completed
        │       $session_data['step'] = 'completed'
        │       $session_data['enquiry_number'] = 'ENQ-2025-001234'
        │
        └─→ STEP 6.8: Generate Response
            │
            ├─→ Display success message:
            │   "🎉 SUCCESS!"
            │   "Your enquiry has been submitted successfully!"
            │   "Enquiry Number: ENQ-2025-001234"
            │   
            │   "📧 A confirmation email has been sent to: parent@email.com"
            │   "📞 We will contact you within 24 hours on: 9876543210"
            │   
            │   "✅ What happens next?"
            │   "• We'll review your application"
            │   "• Our team will contact you within 24-48 hours"
            │   "• You'll receive a confirmation email shortly"
            │
            └─→ Display next actions:
                [📍 New Application]
                [🏫 School Information]
                [📞 Contact Information]
    ↓
END: Enquiry submitted and saved
```

---

## 🔄 Personal Information Collection - Detailed

```
USER INPUT ANALYSIS
    ↓
Message: "Name: Rahul, Phone: 9876543210, Email: parent@email.com"
    ↓
parse_personal_info(message)
    ├─→ Call regex patterns to extract:
    │
    ├─ Name Pattern: /name\s*:\s*([a-z\s]+)/i
    │   └─→ Matches: "Rahul"
    │
    ├─ Phone Pattern: /(?:phone|mobile|contact|ph|number|tel)\s*:\s*(\d{10})/i
    │   └─→ Matches: "9876543210"
    │
    └─ Email Pattern: /(?:email|mail|e-mail)\s*:\s*([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})/i
        └─→ Matches: "parent@email.com"
    ↓
VALIDATION
    ├─→ Is email valid format? YES ✓
    ├─→ Is phone 10 digits? YES ✓
    ├─→ Is name not empty? YES ✓
    └─→ All required fields present? YES ✓
    ↓
RESPONSE GENERATION
    ├─→ Check current session step
    ├─→ If step == 'personal_info' or empty:
    │   └─→ Move to 'academic' step
    │
    ├─→ Format confirmation message:
    │   "✅ Personal Information Complete!"
    │   "👶 Student: Rahul"
    │   "📧 Email: parent@email.com"
    │   "📱 Phone: 9876543210"
    │   
    │   "Step 2: Academic Information 🎓"
    │   "Please share:
    │   • Grade/Class seeking admission for
    │   • Board Preference (CBSE/CAIE)"
    │
    └─→ Return response with session update
```

---

## 📚 Academic Information Collection - Detailed

```
USER INPUT ANALYSIS
    ↓
Message: "Grade 5, CBSE"
    ↓
parse_academic_info(message)
    ├─→ Extract Grade:
    │   │
    │   ├─ Check against grade list:
    │   │   ['Pre-KG', 'LKG', 'UKG', 'Grade 1-12', ...]
    │   │
    │   ├─ Match "Grade 5" → FOUND
    │   └─ Store: $academic_info['grade'] = 'Grade 5'
    │
    ├─→ Extract Board:
    │   │
    │   ├─ Check against board list:
    │   │   ['CBSE', 'CAIE', 'State', 'IGCSE', ...]
    │   │
    │   ├─ Match "CBSE" → FOUND
    │   └─ Store: $academic_info['board'] = 'CBSE'
    │
    └─→ Extract Academic Year:
        ├─ Get from current year
        ├─ Default to next year
        └─ Store: $academic_info['academic_year'] = '2026-27'
    ↓
VALIDATION
    ├─→ Is grade in configured list? YES ✓
    ├─→ Is board in configured list? YES ✓
    ├─→ Are both fields present? YES ✓
    └─→ Valid combination? YES ✓
    ↓
SESSION UPDATE
    ├─→ $session['data']['grade'] = 'Grade 5'
    ├─→ $session['data']['board'] = 'CBSE'
    ├─→ $session['data']['academic_year'] = '2026-27'
    └─→ $session['step'] = 'additional'
    ↓
RESPONSE GENERATION
    └─→ "Perfect! Now please provide additional details:"
        "📅 Date of Birth (YYYY-MM-DD format)"
        "⚧ Gender (Male/Female/Other)"
        "👨‍👩‍👧‍👦 Parent/Guardian Name"
        "🏠 Full Address"
```

---

## ✅ Final Submission & Confirmation - Detailed

```
SUBMISSION TRIGGERED
    ↓
User clicks: [✅ Submit]
    ├─→ AJAX POST: action=submit_enquiry
    ├─→ Include session_id
    └─→ Include nonce for CSRF protection
    ↓
PROCESS FINAL SUBMISSION
    │
    ├─→ 1. VALIDATION
    │   ├─ Verify nonce
    │   ├─ Check all required fields present
    │   ├─ Validate email format
    │   ├─ Validate phone format
    │   └─ Return error if validation fails
    │
    ├─→ 2. GENERATE ENQUIRY NUMBER
    │   ├─ Format: ENQ-YYYY-XXXXX
    │   ├─ Example: ENQ-2025-001234
    │   ├─ Store in: $collected_data['enquiry_number']
    │   └─ Use for tracking
    │
    ├─→ 3. SAVE TO DATABASE
    │   ├─ Call: insert_enquiry()
    │   ├─ Prepare data array with all fields
    │   ├─ Execute INSERT with prepared statement
    │   ├─ Capture insert error if any
    │   ├─ Log: "EduBot: Enquiry saved (ID: X, ENQ: XXX)"
    │   └─ Return error if insert fails
    │
    ├─→ 4. SEND PARENT CONFIRMATION EMAIL
    │   ├─ Get parent email from $collected_data
    │   ├─ Build HTML template with:
    │   │   - School branding (colors, logo)
    │   │   - Enquiry number prominently displayed
    │   │   - All submitted details
    │   │   - Next steps information
    │   │   - Contact information
    │   ├─ Send via wp_mail()
    │   ├─ Set: email_sent = 1
    │   ├─ Log: "Confirmation email sent to parent@email.com"
    │   └─ Continue even if email fails
    │
    ├─→ 5. SEND SCHOOL NOTIFICATION EMAIL
    │   ├─ Get school email from settings
    │   ├─ Build school notification email
    │   ├─ Include enquiry details
    │   ├─ Include action items for school team
    │   ├─ Send via wp_mail()
    │   ├─ Log: "School notification sent"
    │   └─ Continue even if email fails
    │
    ├─→ 6. SEND WHATSAPP NOTIFICATION
    │   ├─ Check if WhatsApp enabled
    │   ├─ Get API credentials
    │   ├─ Validate phone number
    │   ├─ Get template name
    │   ├─ Queue message with parameters
    │   ├─ Set: whatsapp_sent = 1
    │   ├─ Log: "WhatsApp message queued"
    │   └─ Continue even if WhatsApp fails
    │
    ├─→ 7. SAVE TO APPLICATIONS TABLE
    │   ├─ INSERT to wp_edubot_applications
    │   ├─ Link to enquiry ID
    │   ├─ Store key information
    │   ├─ Set initial status: 'new'
    │   └─ Log: "Application saved"
    │
    ├─→ 8. UPDATE SESSION
    │   ├─ Set: $session['step'] = 'completed'
    │   ├─ Set: $session['enquiry_number'] = ENQ-XXX
    │   ├─ Mark session as completed
    │   └─ Save to transients
    │
    └─→ 9. GENERATE SUCCESS RESPONSE
        ├─ Display success message
        ├─ Show enquiry number
        ├─ Confirm email sent
        ├─ Provide next steps
        └─ Show action buttons for next actions
    ↓
RETURN TO USER
    ├─→ Show: "🎉 SUCCESS! Enquiry submitted"
    ├─→ Show: "Enquiry Number: ENQ-2025-001234"
    ├─→ Show: "Email confirmation sent to parent@email.com"
    ├─→ Show: "Will contact within 24 hours"
    └─→ Show action buttons
```

---

## 🔀 Alternative Flows

### Flow A: User Returns to Existing Enquiry

```
User sends message in same session
    ↓
Check session ID
    ├─ YES (Session exists) → Resume from last step
    │   ├─ If step='personal_info' → Ask for missing personal info
    │   ├─ If step='academic' → Ask for academic info
    │   ├─ If step='additional' → Ask for additional details
    │   └─ If step='completed' → Show success / offer new enquiry
    │
    └─ NO (New user) → Start fresh new enquiry
```

### Flow B: User Selects "Edit Information"

```
User clicks: [✏️ Edit Information]
    ↓
Return to step with incomplete data
    ├─→ Show current data
    ├─→ Ask which field to edit
    ├─→ User provides new data
    ├─→ Validate new data
    ├─→ Update session
    └─→ Continue to next step
```

### Flow C: User Cancels Enquiry

```
User clicks: [❌ Cancel]
    ↓
Clear session data
    ├─→ Delete session transient
    ├─→ Mark session as cancelled
    ├─→ Log cancellation
    └─→ Return to greeting screen
```

### Flow D: User Exits Mid-Enquiry

```
User leaves website / closes browser
    ↓
Session stored in WordPress transients
    ├─→ Expires after 24 hours (configurable)
    ├─→ Can be resumed if user returns with same session ID
    └─→ No data loss
```

---

## 🎭 State Machine Diagram

```
┌────────────────────────────────────────────────────────────────┐
│                      SESSION STATES                            │
└────────────────────────────────────────────────────────────────┘

                         [START]
                             ↓
                    ┌────────────────┐
                    │  INITIAL       │
                    │  (greeting)    │
                    └────────┬───────┘
                             ↓
                    ┌────────────────┐
                    │  PERSONAL_INFO │◄─┐ (user edits)
                    │                │  │
                    │ Ask for:       │  │
                    │ • Name         │  │
                    │ • Email        │  │
                    │ • Phone        │  │
                    └────────┬───────┘  │
                             │          │
            (User provides) ──┘──────┐   │
                             │      │   │
              ┌──────────────┘      │   │
              │                    │   │
              ↓                    │   │
    ┌──────────────────┐          │   │
    │ ACADEMIC_INFO    │◄─────────┘   │
    │                  │  (user edits)│
    │ Ask for:         │              │
    │ • Grade          │              │
    │ • Board          │              │
    └────────┬─────────┘              │
             ↓                        │
    ┌──────────────────┐              │
    │ ADDITIONAL_INFO  │◄─────────────┘
    │                  │  (user edits)
    │ Ask for:         │
    │ • DOB            │
    │ • Gender         │
    │ • Parent Name    │
    │ • Address        │
    └────────┬─────────┘
             ↓
    ┌──────────────────┐
    │ CONFIRMATION     │
    │                  │
    │ Show Summary     │
    │ [Submit] [Edit]  │
    │ [Cancel]         │
    └────────┬─────────┘
             ↓
    ┌────────────────────────┐
    │ COMPLETED              │
    │                        │
    │ Save to DB             │
    │ Send Emails/WhatsApp   │
    │ Show Success Message   │
    └────────┬───────────────┘
             ↓
          [END]

TRANSITIONS:
• INITIAL → PERSONAL_INFO (User clicks "New Application")
• PERSONAL_INFO → ACADEMIC_INFO (All personal info provided)
• ACADEMIC_INFO → ADDITIONAL_INFO (Grade & board provided)
• ADDITIONAL_INFO → CONFIRMATION (DOB & other details provided)
• CONFIRMATION → COMPLETED (User clicks Submit)
• CONFIRMATION → [EDIT STATE] (User clicks Edit) → Back to incomplete step
• [ANY STATE] → INITIAL (User clicks Cancel)
• COMPLETED → INITIAL (User starts new enquiry)
```

---

## 📊 Session Data Structure

```json
{
  "session_id": "sess_6538e2c3a4f81",
  "flow_type": "admission",
  "started": "2025-11-03 15:30:45",
  "step": "confirmation",
  "data": {
    "student_name": "Rahul Kumar",
    "parent_name": "Rajesh Kumar",
    "email": "parent@email.com",
    "phone": "9876543210",
    "date_of_birth": "2015-05-15",
    "gender": "Male",
    "grade": "Grade 5",
    "board": "CBSE",
    "academic_year": "2026-27",
    "address": "123 Main Street, City",
    "ip_address": "203.0.113.45",
    "user_agent": "Mozilla/5.0...",
    "enquiry_number": ""
  },
  "metadata": {
    "created_at": "2025-11-03 15:30:45",
    "last_updated": "2025-11-03 15:45:20",
    "message_count": 6,
    "ip_address": "203.0.113.45",
    "referer": "https://epistemo.in/",
    "utm_source": "google",
    "utm_medium": "organic"
  }
}
```

---

## 🔍 Validation Rules

| Field | Validation Rule | Example |
|-------|-----------------|---------|
| **Student Name** | 2-100 chars, letters/spaces only | "Rahul Kumar" |
| **Parent Name** | 2-100 chars, letters/spaces only | "Rajesh Kumar" |
| **Email** | Valid RFC 5322 format | "parent@email.com" |
| **Phone** | 10 digits (India) | "9876543210" |
| **DOB** | YYYY-MM-DD format, valid date | "2015-05-15" |
| **Gender** | Male/Female/Other | "Male" |
| **Grade** | From configured list | "Grade 5" |
| **Board** | From configured list | "CBSE" |
| **Address** | 5-255 chars | "123 Main Street" |
| **Academic Year** | YYYY-YY format | "2026-27" |

---

## 📈 Enquiry Number Generation

```php
Format: ENQ-YYYY-XXXXX

Example: ENQ-2025-001234

Breakdown:
├─ ENQ = Prefix (constant)
├─ 2025 = Current year (YYYY)
└─ 001234 = Sequential ID (zero-padded, 6 digits)

Logic:
1. Get current year: 2025
2. Get next available sequence for year: 1234
3. Zero-pad to 6 digits: 001234
4. Concatenate: ENQ-2025-001234
5. Store in: wp_edubot_enquiries.enquiry_number
6. Make unique: Add UNIQUE constraint on enquiry_number
```

---

## 🎯 Success Criteria

✅ **Enquiry Submission Successful if:**
- All required fields validated
- Data inserted to `wp_edubot_enquiries`
- Entry created in `wp_edubot_applications`
- Parent email sent successfully
- School notification sent successfully
- Enquiry number assigned
- Session marked completed
- User sees success message with enquiry number

❌ **Enquiry Submission Failed if:**
- Validation fails
- Database insert fails
- Enquiry number generation fails
- Critical notification fails

---

## 🔐 Error Handling

```
TRY
  ├─ Validate input
  ├─ Parse information
  ├─ Save to database
  ├─ Send notifications
  └─ Generate response

CATCH
  ├─ Log error with stack trace
  ├─ Log current session state
  ├─ Log all collected data
  ├─ Display user-friendly message
  └─ Suggest recovery action

FINALLY
  ├─ Update session
  ├─ Close database connection
  └─ Clean up resources
```

