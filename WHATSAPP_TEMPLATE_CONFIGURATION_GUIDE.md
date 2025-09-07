# 📋 WhatsApp Business API Template Configuration Guide

This guide will walk you through configuring WhatsApp Business API templates for EduBot Pro to ensure compliance with Meta's messaging requirements.

## 🚨 Important: Why Templates Are Required

**WhatsApp Business API requires pre-approved templates for production messaging.** You cannot send arbitrary text messages to users unless they are in a sandbox/testing environment.

### Production vs Sandbox
- **Production**: Must use approved templates only
- **Sandbox**: Can send text messages for testing (limited to verified numbers)

---

## 📋 Step-by-Step Configuration

### Step 1: Create WhatsApp Business Account

1. **Go to Meta Business Manager**: https://business.facebook.com/
2. **Create/Access Business Account**: Ensure you have a verified business account
3. **Set Up WhatsApp Business API**: Add WhatsApp Business API to your account

### Step 2: Create Message Template

1. **Access WhatsApp Manager**: Go to business.facebook.com → WhatsApp → Message Templates
2. **Create New Template**: Click "Create Template"
3. **Template Configuration**:
   - **Template Name**: `admission_confirmation` (or your preferred name)
   - **Category**: Choose "TRANSACTIONAL" (for admission confirmations)
   - **Language**: Select your primary language (e.g., English)

### Step 3: Design Template Content

#### Recommended Template Structure:
```
Header: Admission Enquiry Confirmation

Body: 
Dear {{1}},

Thank you for your enquiry at {{3}}. Your enquiry number is {{2}} for Grade {{4}}.

We have received your application on {{5}} and will contact you within 24-48 hours with next steps.

Best regards,
Admissions Team

Footer: Reply STOP to unsubscribe
```

#### Template Parameters:
1. **{{1}}** - Student Name
2. **{{2}}** - Enquiry Number  
3. **{{3}}** - School Name
4. **{{4}}** - Grade
5. **{{5}}** - Date

### Step 4: Submit Template for Approval

1. **Review Template**: Ensure content follows WhatsApp guidelines
2. **Submit for Review**: Templates typically take 24-48 hours for approval
3. **Wait for Approval**: You'll receive notification once approved

### Step 5: Configure EduBot Pro Backend

Once your template is approved:

#### 5.1 Go to WordPress Admin
Navigate to: **EduBot Pro → API Integrations**

#### 5.2 WhatsApp Configuration Section
Fill in the following fields:

```
WhatsApp Provider: Meta WhatsApp Business API
Access Token: [Your Permanent Access Token]
Phone Number ID: [Your Phone Number ID]
```

#### 5.3 WhatsApp Business API Template Settings
```
☑️ Use Templates: Enabled (checked)
Template Namespace: your_business_namespace
Template Name: admission_confirmation
Template Language: en (or your template language)
```

---

## 🔧 Finding Your Configuration Values

### Access Token (Permanent)
1. Go to **Meta Business Manager → System Users**
2. Create/Select System User with WhatsApp Business API permissions
3. Generate **Permanent Access Token** (never expires)
4. **Important**: Use permanent token, not temporary ones

### Phone Number ID
1. Go to **WhatsApp Manager → API Setup**
2. Find your **Phone Number ID** (different from actual phone number)
3. Format: Usually a long numeric ID like `123456789012345`

### Template Namespace
1. Go to **WhatsApp Manager → Message Templates**
2. Find your approved template
3. Namespace shown in template details (e.g., `your_business_namespace`)

### Template Name & Language
- **Name**: Exact name you used when creating template
- **Language**: Language code (en, hi, es, etc.)

---

## ⚙️ Backend Configuration Examples

### Example 1: Meta WhatsApp Business API
```
WhatsApp Provider: meta
Access Token: EAABuF8r2abcXYZ... (permanent token)
Phone Number ID: 123456789012345
Use Templates: ✅ Enabled
Template Namespace: my_school_system
Template Name: admission_confirmation  
Template Language: en
```

### Example 2: Twilio WhatsApp
```
WhatsApp Provider: twilio
Access Token: AC1234567890:abcd1234567890 (Account SID:Auth Token)
Phone Number ID: +14155238886
Use Templates: ✅ Enabled (if using Business API)
Template Namespace: (leave empty for Twilio)
Template Name: admission_confirmation
Template Language: en
```

---

## 🧪 Testing Your Configuration

### Before Going Live:

1. **Test in Sandbox**: Disable templates temporarily for testing
2. **Verify Template**: Ensure template is approved in Meta Business Manager
3. **Check Phone Number**: Verify phone number ID is correct
4. **Test with Real Numbers**: Send test messages to verified numbers

### Testing Steps:
1. Go to **EduBot Pro → API Integrations**
2. Configure WhatsApp settings
3. Click **"Test WhatsApp"** button
4. Check test message delivery

---

## 📱 Template Message Flow

### How It Works:
1. **Student Submits Enquiry** → EduBot collects data
2. **System Prepares Template** → Maps data to template parameters
3. **Builds Template Message** → Creates WhatsApp API payload
4. **Sends via Business API** → Delivers formatted template message
5. **Fallback Support** → Uses text if template fails (sandbox only)

### Message Example:
```
📚 Admission Enquiry Confirmation

Dear John Smith,

Thank you for your enquiry at ABC International School. Your enquiry number is ENQ2024001 for Grade 10.

We have received your application on 15/12/2024 and will contact you within 24-48 hours with next steps.

Best regards,
Admissions Team
```

---

## 🚨 Common Issues & Solutions

### Issue 1: "Template Not Found"
**Solution**: 
- Verify template name matches exactly
- Ensure template is approved and active
- Check template namespace is correct

### Issue 2: "Invalid Access Token"
**Solution**:
- Use permanent access token, not temporary
- Ensure system user has correct permissions
- Regenerate token if needed

### Issue 3: "Phone Number Not Verified"
**Solution**:
- Complete WhatsApp Business verification
- Ensure phone number is approved for messaging
- Check phone number ID format

### Issue 4: "Message Rejected"
**Solution**:
- Templates required for production
- Enable template usage in backend
- Verify template parameters match

---

## 🔒 Security Best Practices

### API Key Security:
- ✅ Use permanent access tokens
- ✅ Store tokens securely (EduBot encrypts automatically)
- ✅ Restrict system user permissions
- ❌ Never share tokens publicly
- ❌ Don't use personal access tokens

### Template Security:
- ✅ Follow WhatsApp content policies
- ✅ Use transactional category for admissions
- ✅ Include opt-out instructions
- ❌ Don't send promotional content via templates

---

## 📞 Support & Resources

### Meta Resources:
- **WhatsApp Business API Documentation**: https://developers.facebook.com/docs/whatsapp
- **Business Manager**: https://business.facebook.com/
- **Template Guidelines**: https://developers.facebook.com/docs/whatsapp/message-templates

### EduBot Pro Support:
- Check **System Status** in WordPress admin
- Review **API Integration Logs**
- Test configuration before production use

---

## ✅ Configuration Checklist

Before going live, ensure:

- [ ] Meta Business Account verified
- [ ] WhatsApp Business API access approved  
- [ ] Message template created and approved
- [ ] Permanent access token generated
- [ ] Phone number ID configured correctly
- [ ] Template settings configured in EduBot
- [ ] Test messages working successfully
- [ ] Fallback email/SMS configured as backup

---

**Remember**: WhatsApp Business API is strict about template compliance. Always test thoroughly in sandbox mode before enabling for production use!
