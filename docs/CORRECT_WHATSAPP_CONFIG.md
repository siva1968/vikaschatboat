## 🎯 CORRECT CONFIGURATION FOR EDUBOT PRO WHATSAPP

### 📋 Admin Panel Settings Required:

**Go to: Admin > EduBot Pro > School Settings**

1. ✅ **WhatsApp Notifications:** CHECKED (Enable this checkbox)
2. ✅ **WhatsApp Template Type:** Business API Template  
3. ✅ **WhatsApp Template Name:** admission_confirmation
4. ✅ **WhatsApp Template Language:** en

**WhatsApp Message Template Field:** LEAVE AS IS (this is for free-form only)
```
Admission Enquiry Confirmation
Dear {parent_name},

Thank you for your enquiry at {school_name}. Your enquiry number is {enquiry_number} for Grade {grade}.

We have received your application on {submission_date} and will contact you within 24-48 hours with the next steps.

Best regards,
Admissions Team
Reply STOP to unsubscribe
```

**Go to: Admin > EduBot Pro > API Integrations**

5. ✅ **WhatsApp Provider:** Meta
6. ✅ **WhatsApp Access Token:** EAASeCKYjY2sBPfLljPAnLtWsXwUzCzPZAd92PfUIqaScZAFjpM9fK3UhLzxxt4OhgzLYpRpWZAlmVjZCSpTV19FcJXRZALTtHlbtjCqNfp5BLdLmXZBzW90c4v4REIko62w6QguwNMWXN1qITGK9D1su8YeILdogvDPeJTOIjdBrC2VgnzKKOLWKOAOT2n2wZDZD
7. ✅ **Phone Number ID:** 614525638411206

### 🎯 KEY POINT: 
When you select "Business API Template" the plugin will IGNORE the free-form template text and instead use your approved Meta Business template "admission_confirmation" with the correct {{1}}, {{2}}, {{3}}, {{4}}, {{5}} parameters.

### ✅ Template Parameters (Automatic Mapping):
- {{1}} = Parent Name (Siva)
- {{2}} = Enquiry Number (ENQ20254651)  
- {{3}} = School Name (Epistemo)
- {{4}} = Grade (Grade 5)
- {{5}} = Submission Date (07/09/2025)

### 🚫 COMMON MISTAKE TO AVOID:
Do NOT set "WhatsApp Template Type" to "Free-form Message" - this will try to use the text template instead of your approved Business API template.

### ✅ CORRECT FLOW:
1. Template Type = "Business API Template"
2. Template Name = "admission_confirmation" 
3. Plugin uses Meta Business API with {{1}}-{{5}} parameters
4. Your approved template sends the message
5. Parent receives formatted message ✅

### ❌ WRONG FLOW:
1. Template Type = "Free-form Message"
2. Plugin tries to send the text template as-is
3. May get blocked or rejected by WhatsApp ❌
