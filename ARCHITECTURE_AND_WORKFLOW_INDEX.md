# 📚 EduBot Pro v1.3.2 - Complete Architecture & Workflow Documentation

**Version:** 1.3.2  
**Created:** November 3, 2025  
**Status:** ✅ Complete  

---

## 🎯 Documentation Overview

This comprehensive documentation package provides complete visibility into the EduBot Pro chatbot system architecture, workflows, data flows, and integrations. Designed for architects, developers, and system administrators.

---

## 📖 Document Index

### 1. **ARCHITECTURE_OVERVIEW.md** ⭐ START HERE
**Purpose:** System-level architecture and component design  
**Contains:**
- High-level architecture diagram
- Core component descriptions (10 major components)
- Plugin directory structure
- Class hierarchy and dependencies
- Data flow overview
- Security architecture
- Database schema overview

**Key Sections:**
- `High-Level Architecture` - Visual representation of system layers
- `Core Components` - Detailed documentation of each major class
- `Plugin Structure` - Complete file organization
- `Class Hierarchy` - Dependency mapping
- `Database Schema` - Table structure overview

**Read if you want to:** Understand overall system design, component responsibilities, and architecture patterns

---

### 2. **WORKFLOW_DIAGRAM.md** 🔄 PROCESS FLOWS
**Purpose:** Step-by-step admission enquiry workflow visualization  
**Contains:**
- Complete enquiry workflow (40+ steps)
- Personal information collection flow
- Academic information collection flow
- Final submission & confirmation flow
- Alternative flows (edit, cancel, return)
- State machine diagram
- Session data structure
- Validation rules
- Enquiry number generation
- Error handling

**Key Sections:**
- `Complete Enquiry Workflow` - Full user journey
- `Personal Information Collection` - Regex parsing, validation
- `Academic Information Collection` - Grade/board selection
- `Final Submission & Confirmation` - DB save, notifications
- `State Machine Diagram` - State transitions
- `Session Data Structure` - JSON schema

**Read if you want to:** Understand the complete enquiry process, state transitions, and validation logic

---

### 3. **DATA_FLOW_AND_INTEGRATIONS.md** 🌊 DATA MOVEMENT
**Purpose:** Data flows, component communication, external integrations  
**Contains:**
- Complete data flow from user input to database
- Component-to-component data exchange
- Database schema and relationships
- External integration details (OpenAI, WhatsApp, Email)
- Message flow sequence diagrams
- Error handling and logging
- Data consistency and integrity
- Conflict resolution strategies

**Key Sections:**
- `Complete Data Flow` - Input → Processing → Output → Storage
- `Component Data Exchange` - How components communicate
- `Database Schema & Relations` - ER diagram and queries
- `External Integrations` - OpenAI, WhatsApp, Email details
- `Message Flow Sequence` - AJAX request sequences
- `Error Handling & Logging` - Exception handling

**Read if you want to:** Understand data movement, component interactions, and external service integration

---

## 🏗️ Architecture Quick Reference

### System Layers

```
┌─────────────────────────────────────────┐
│  PRESENTATION LAYER (Frontend)          │
│  • Chatbot UI (HTML/CSS/JavaScript)     │
│  • AJAX communication                   │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  APPLICATION LAYER (WordPress AJAX)     │
│  • Request routing                      │
│  • Nonce verification                   │
│  • Authorization checks                 │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  BUSINESS LOGIC LAYER (EduBot Core)    │
│  • Shortcode Handler                    │
│  • Chatbot Engine                       │
│  • Message Parsing                      │
│  • Flow Management                      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  DATA ACCESS LAYER (Managers)           │
│  • Database Manager                     │
│  • Security Manager                     │
│  • Notification Manager                 │
│  • School Config Manager                │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  PERSISTENCE LAYER (Storage)            │
│  • WordPress Database (MySQL)           │
│  • WordPress Transients (Cache)         │
│  • WordPress Options (Config)           │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  INTEGRATION LAYER (External APIs)      │
│  • OpenAI (ChatGPT)                    │
│  • WhatsApp Business                    │
│  • Email Service                        │
│  • SMS Gateway (Planned)                │
└─────────────────────────────────────────┘
```

---

## 🔑 Key Components Overview

| Component | Purpose | Key Responsibility |
|-----------|---------|-------------------|
| **EduBot_Core** | Main Orchestrator | Load dependencies, register hooks, initialize plugin |
| **EduBot_Shortcode** | Form & Flow Manager | Render chatbot, process messages, manage sessions |
| **EduBot_Chatbot_Engine** | AI & Response Logic | Conversation state, contextual responses, submissions |
| **EduBot_Database_Manager** | Data Access | Save enquiries, query applications, update status |
| **EduBot_Security_Manager** | Security | Nonce verification, input sanitization, output escaping |
| **EduBot_School_Config** | Settings | Store/retrieve configuration, academic data |
| **Notification_Manager** | Communications | Send emails, WhatsApp, SMS notifications |
| **EduBot_Visitor_Analytics** | Tracking | Track interactions, generate analytics |

---

## 🔄 Workflow Summary

### 6-Step Enquiry Process

```
STEP 1: Chat Initialization
└─ User sees chatbot, clicks "New Application"

STEP 2: Personal Information
└─ Collect: Name, Email, Phone

STEP 3: Academic Information  
└─ Collect: Grade, Board, Academic Year

STEP 4: Additional Details
└─ Collect: DOB, Gender, Parent Name, Address

STEP 5: Confirmation
└─ Display summary, get user confirmation

STEP 6: Submission & Notifications
└─ Save to DB, send emails, WhatsApp, update status
```

### Total Steps in Detail: 40+
- Session creation
- User input parsing
- Data validation
- Session storage
- Response generation
- Database insertion
- Email sending (2 emails)
- WhatsApp notification
- Applications table update
- Status tracking

---

## 💾 Database Tables

### wp_edubot_enquiries (Primary)
- 23 columns
- Stores all enquiry submissions
- Tracks notification status
- Contains tracking data (UTM, IP, etc)

### wp_edubot_applications
- 9 columns
- Unified admin interface
- Linked to enquiries via foreign key
- Used for admin applications list

### wp_options
- Stores all plugin settings
- School configuration
- API keys and credentials
- Branding colors and logos

### wp_transients
- Session storage (TTL: 24 hours)
- Conversation state
- User inputs across steps

---

## 🔌 External Integrations

| Service | Purpose | Status | Type |
|---------|---------|--------|------|
| **OpenAI API** | AI-powered responses | ✅ Active | REST API |
| **WhatsApp Business** | Parent notifications | ✅ Active | REST API |
| **WordPress wp_mail** | Email notifications | ✅ Active | Built-in |
| **SMS Gateway** | SMS notifications | ⏳ Planned | REST API |
| **Google Analytics** | User analytics | ✅ Framework | Tracking |

---

## 🔐 Security Architecture

### Input Security
- ✅ Nonce verification (CSRF protection)
- ✅ Capability checks (user roles)
- ✅ Sanitization of all inputs
- ✅ Validation of all data

### Output Security
- ✅ Proper escaping (HTML, attributes, URLs)
- ✅ wp_json_encode() for JSON
- ✅ Prepared statements for database
- ✅ No direct SQL queries

### Data Security
- ✅ Stored in WordPress database
- ✅ No plaintext sensitive data
- ✅ API keys in wp_options
- ✅ Session data in transients

---

## 📊 Data Validation Rules

| Field | Validation |
|-------|-----------|
| **Student Name** | 2-100 chars, letters/spaces |
| **Email** | RFC 5322 format |
| **Phone** | 10 digits (India) |
| **DOB** | YYYY-MM-DD format |
| **Grade** | From configured list |
| **Board** | From configured list |

---

## 🎯 State Diagram

```
START
  ↓
GREETING
  ↓
PERSONAL_INFO (Name, Email, Phone)
  ↓
ACADEMIC_INFO (Grade, Board)
  ↓
ADDITIONAL_INFO (DOB, Gender, Parent, Address)
  ↓
CONFIRMATION (Show summary)
  ├─ [EDIT] → Back to incomplete step
  ├─ [CANCEL] → Back to START
  └─ [SUBMIT] → COMPLETED
                    ↓
              Save to DB
              Send notifications
              Show success
                    ↓
              END
```

---

## 📈 Message Flow Overview

```
User Input (Browser)
    ↓
AJAX POST Request
    ↓
WordPress AJAX Handler
    ↓
Verify Nonce
    ↓
EduBot_Shortcode Handler
    ├─ Parse message
    ├─ Validate data
    ├─ Get/create session
    └─ Route to handler
    ↓
Handle Admission Flow / Generate Response
    ├─ Extract personal info
    ├─ Extract academic info
    ├─ Update session
    └─ Generate response
    ↓
[Optional] Process Final Submission
    ├─ Validate all fields
    ├─ Generate enquiry number
    ├─ Insert to database
    ├─ Send notifications
    └─ Update status
    ↓
Return JSON Response
    ↓
Frontend Display (Browser)
```

---

## 🚀 Getting Started with the Documentation

### For System Architects
1. Read: **ARCHITECTURE_OVERVIEW.md**
   - Understand system design
   - Review component responsibilities
   - Study plugin structure

2. Review: **DATA_FLOW_AND_INTEGRATIONS.md**
   - Understand data movement
   - Review integration points
   - Study security architecture

### For Developers
1. Read: **ARCHITECTURE_OVERVIEW.md** (class hierarchy section)
   - Map component dependencies
   - Understand inheritance

2. Read: **WORKFLOW_DIAGRAM.md**
   - Understand step-by-step flow
   - Review validation rules
   - Study state machine

3. Reference: **DATA_FLOW_AND_INTEGRATIONS.md**
   - Track data movement
   - Understand error handling
   - Review logging

### For System Administrators
1. Read: **WORKFLOW_DIAGRAM.md**
   - Understand user journey
   - Review success criteria
   - Study validation rules

2. Skim: **DATA_FLOW_AND_INTEGRATIONS.md**
   - Review external integrations
   - Understand error handling
   - Check logging locations

### For DevOps/Infrastructure Teams
1. Review: **DATA_FLOW_AND_INTEGRATIONS.md**
   - Database requirements
   - Integration endpoints
   - API credentials needed

2. Check: **ARCHITECTURE_OVERVIEW.md**
   - Security architecture
   - Database constraints
   - Performance considerations

---

## 📚 Related Documentation

| Document | Purpose |
|----------|---------|
| INSTALLATION_GUIDE.md | Setup and installation |
| USER_MANUAL.md | End-user guide |
| API_REFERENCE.md | API documentation |
| PLUGIN_READY.md | Feature checklist |
| DEPLOYMENT_CHECKLIST.md | Deployment steps |
| SECURITY_AUDIT_REPORT.md | Security assessment |

---

## 🔍 Quick Lookup Guide

### "How does the chatbot work?"
→ Read: WORKFLOW_DIAGRAM.md → Complete Enquiry Workflow section

### "Where is data stored?"
→ Read: DATA_FLOW_AND_INTEGRATIONS.md → Database Schema section

### "How does WhatsApp integration work?"
→ Read: DATA_FLOW_AND_INTEGRATIONS.md → External Integrations section

### "What components are involved?"
→ Read: ARCHITECTURE_OVERVIEW.md → Core Components section

### "How does data flow through the system?"
→ Read: DATA_FLOW_AND_INTEGRATIONS.md → Complete Data Flow section

### "What are the validation rules?"
→ Read: WORKFLOW_DIAGRAM.md → Validation Rules section

### "How is security handled?"
→ Read: ARCHITECTURE_OVERVIEW.md → Security Architecture section

### "What happens if something fails?"
→ Read: DATA_FLOW_AND_INTEGRATIONS.md → Error Handling & Logging section

---

## 📋 Documentation Checklist

✅ **High-Level Architecture**
- System layers clearly defined
- Component responsibilities documented
- Dependencies mapped
- Integration points identified

✅ **Detailed Workflows**
- Complete enquiry process documented
- State transitions clearly shown
- Validation rules specified
- Error scenarios covered

✅ **Data Architecture**
- Database schema documented
- Data flow visualized
- Component communication shown
- External integrations detailed

✅ **Security Documentation**
- Input validation strategies
- Output escaping patterns
- Security manager responsibilities
- Error handling procedures

✅ **Operational Information**
- Logging locations documented
- Error scenarios covered
- Recovery procedures outlined
- Monitoring recommendations provided

---

## 🎓 Learning Path

### Beginner (Understanding the System)
1. ARCHITECTURE_OVERVIEW.md - High-Level Architecture section
2. WORKFLOW_DIAGRAM.md - Complete Enquiry Workflow section
3. DATA_FLOW_AND_INTEGRATIONS.md - Message Flow Sequence section

### Intermediate (System Implementation)
1. ARCHITECTURE_OVERVIEW.md - Full document
2. WORKFLOW_DIAGRAM.md - State Machine section
3. DATA_FLOW_AND_INTEGRATIONS.md - Database Schema section

### Advanced (System Optimization)
1. ARCHITECTURE_OVERVIEW.md - Security Architecture section
2. DATA_FLOW_AND_INTEGRATIONS.md - Error Handling section
3. DATA_FLOW_AND_INTEGRATIONS.md - Data Consistency section

---

## 🔄 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.3.2 | Nov 3, 2025 | Architecture & workflow documentation |
| 1.3.1 | Oct 16, 2025 | Date format enhancement |
| 1.3.0 | Oct 16, 2025 | Database migration, enquiry fix |

---

## 📞 Support & Questions

For questions about:
- **Architecture**: See ARCHITECTURE_OVERVIEW.md
- **Workflows**: See WORKFLOW_DIAGRAM.md
- **Data Movement**: See DATA_FLOW_AND_INTEGRATIONS.md
- **Setup**: See INSTALLATION_GUIDE.md
- **Features**: See PLUGIN_READY.md

---

## ✨ Document Highlights

### Visual Clarity
- 📊 ASCII diagrams for flows and architecture
- 🔄 State machine representation
- 📐 Database relationships
- 🔀 Sequence diagrams

### Comprehensive Coverage
- 🎯 System-level overview
- 🔧 Component-level detail
- 🌊 Data-level flow
- 🔌 Integration points
- 🔐 Security architecture

### Practical Information
- ✅ Validation rules
- 🗂️ Database schema
- 🚀 Workflow steps
- ⚠️ Error scenarios
- 📝 Logging locations

---

## 🎯 Key Takeaways

1. **Architecture**: 10 core components working in concert
2. **Workflow**: 6-step enquiry process with 40+ detailed steps
3. **Data**: Flows from user input through parsing to database persistence
4. **Security**: Multiple layers of validation, sanitization, and escaping
5. **Integration**: Clean separation of concerns with external APIs
6. **State Management**: Session-based state machine for conversation flow
7. **Notifications**: Multi-channel (Email, WhatsApp, SMS planned)
8. **Scalability**: Modular design allows easy extension

---

**Complete Architecture & Workflow Documentation Package**  
**Status:** ✅ Production Ready  
**Last Updated:** November 3, 2025  

For the latest updates, refer to the main repository at: https://github.com/siva1968/edubot-pro

