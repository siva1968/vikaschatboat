# 📱 WhatsApp Messages Not Sending - Diagnosis & Fix

## Current Status

WhatsApp messages are not being sent because one of the following is true:

1. ❌ WhatsApp notifications are **DISABLED** in settings
2. ❌ WhatsApp API **NOT CONFIGURED** (missing token/phone ID)
3. ❌ School phone number **NOT SET** (for school notifications)

---

## 🔧 How to Enable WhatsApp

### Step 1: Configure WhatsApp API

1. Go to **WordPress Admin Dashboard**
2. Click **EduBot Pro** in left menu
3. Click **API Integrations**
4. Find the **WhatsApp** tab
5. Fill in:
   - **WhatsApp Provider:** Select "Meta"
   - **Access Token:** Paste your WhatsApp Business API token
   - **Phone Number ID:** Enter your WhatsApp phone number ID
6. Click **"Test Connection"** to verify
7. Click **Save Settings**

### Step 2: Enable Parent Notifications

1. Go to **EduBot Pro** → **School Settings**
2. Find **"WhatsApp Notifications"** section
3. **CHECK** the box: "Send WhatsApp confirmations to parents"
4. Click **Save Settings**

### Step 3: (Optional) Enable School Notifications

1. In **School Settings**, find **"School WhatsApp Notifications"**
2. **CHECK** the box: "Send WhatsApp notifications to school admission team"
3. Make sure **Contact Phone** is set to your school's WhatsApp number (format: 919866133566)
4. Click **Save Settings**

---

## ✅ Verification

After configuring:

1. Visit: `http://localhost/demo/whatsapp_diagnostic.php`
2. Check that all settings show ✅ (enabled and configured)
3. Submit a test enquiry
4. Check your phone for WhatsApp message

---

## 📊 What You'll Receive

### Parent WhatsApp Message:
```
Dear {Parent Name},

Thank you for your enquiry at Epistemo Vikas Leadership School.

Your Enquiry Number: ENQ20256110
Student: Prasad
Grade: Grade 5
Academic Year: 2026-27

We have received your application and will contact you within 24-48 hours with next steps.

Best regards,
Admissions Team
```

### School WhatsApp Message (Optional):
```
🎓 *New Admission Enquiry - Epistemo Vikas Leadership School*

📋 *Enquiry Number:* ENQ20256110
👶 *Student:* Prasad
🎯 *Grade:* Grade 5
📚 *Board:* CBSE
👨‍👩‍👧 *Parent:* [Parent Name]
📱 *Phone:* 9866133566
📧 *Email:* prasadmasian@gmail.com

Please contact the family to proceed with admission.
```

---

## 🐛 If WhatsApp Still Not Working

1. Check debug log: `http://localhost/demo/debug_log_viewer.php`
2. Search for "WhatsApp" entries
3. Look for error messages
4. Common issues:
   - ❌ Invalid token
   - ❌ Wrong phone number ID
   - ❌ API limit exceeded
   - ❌ Phone number not verified

---

## 📝 Current Configuration Check

Visit: `http://localhost/demo/whatsapp_diagnostic.php` to see:
- ✅ Notifications enabled/disabled
- ✅ API credentials configured
- ✅ Phone numbers set
- ✅ Recent log entries

