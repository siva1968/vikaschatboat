# 🎯 QUICK GUIDE - How to Access MCB Settings

**Status:** ✅ Integration Activated  
**Date:** November 6, 2025

---

## 📍 WHERE TO FIND MCB SETTINGS

### Location in WordPress Admin:

```
Left Sidebar Menu:
│
├── Dashboard
├── Finish Setup
├── Posts
├── Pages
├── Comments
├── EduBot Pro ◄─── CLICK HERE
│   ├── Dashboard
│   ├── School Settings
│   ├── Academic Configuration
│   ├── API Integrations
│   ├── Form Builder
│   ├── Applications
│   ├── Analytics
│   ├── System Status
│   └── ✨ MyClassBoard Settings ◄─ NEW! CLICK HERE
│
├── Plugins
└── Settings
```

---

## ✅ ACTIVATION CHECKLIST

### Before You Can See Settings:

**Step 1: Deactivate EduBot Pro**
- [ ] Go to Plugins page
- [ ] Find "EduBot Pro"
- [ ] Click "Deactivate"

**Step 2: Reactivate EduBot Pro**
- [ ] Go to Plugins page
- [ ] Find "EduBot Pro"
- [ ] Click "Activate"
- [ ] Wait for page to load

**Step 3: Return to Admin**
- [ ] You should see activation message
- [ ] New menu should appear

---

## 🎨 What You'll See

### The Settings Page Has 4 Tabs:

#### Tab 1: ⚙️ Settings
```
Organization ID:        [21        ]
Branch ID:              [113       ]
API Timeout (seconds):  [65        ]
Retry Attempts:         [3         ]

☐ Enable Integration
☑ Auto-Sync on Enquiry Creation

[Save Settings]  [Reset to Defaults]
```

#### Tab 2: 📊 Status
```
Total Syncs:        150
Successful:         147 (98%)
Failed:              3 (2%)
Today's Syncs:       15
Success Rate:        98%
```

#### Tab 3: 🗂️ Mapping
```
Lead Source Mapping:
Chatbot        → 273
Website        → 231
Facebook       → 272
Google Search  → 269
Instagram      → 268
LinkedIn       → 267
WhatsApp       → 273
Referral       → 92
Email          → 286
Walk-In        → 250
Organic        → 280
Display        → 270

[Save Mapping]
```

#### Tab 4: 📋 Logs
```
Enquiry # | Student | Email | Status | Date/Time | Actions
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
123456   | John    | ... | ✓ Success | Nov 6, 2:30 PM | Retry
123457   | Jane    | ... | ✓ Success | Nov 6, 2:25 PM | Retry
123458   | Bob     | ... | ✗ Failed | Nov 6, 2:20 PM | Retry
```

---

## 🔧 Configuration Steps

### Step 1: Enter Organization ID
1. Click **Settings** tab
2. In "Organization ID" field, enter: **21**
3. (Or your organization's ID)

### Step 2: Enter Branch ID
1. In "Branch ID" field, enter: **113**
2. (Or your branch's ID)

### Step 3: Enable Integration
1. Check the box: **☑ Enable Integration**
2. Make sure this is checked: **☑ Auto-Sync on Enquiry Creation**

### Step 4: Save Settings
1. Click **[Save Settings]** button
2. You should see a success message

---

## ✨ Test It

### Create a Test Enquiry
1. Go to the chatbot form on your website
2. Fill in:
   - Student Name
   - Email
   - Phone
   - School
   - Grade
3. Submit the enquiry

### Check the Logs
1. Go to: **EduBot Pro → MyClassBoard Settings → Logs** tab
2. Look for your test enquiry
3. Status should show: **✓ Success**

---

## 🆘 Troubleshooting

### Menu doesn't appear?
**Solution:**
1. Clear browser cache (Ctrl+Shift+Delete)
2. Reload the page
3. If still missing, deactivate/reactivate plugin again

### Settings page is blank?
**Solution:**
1. Check browser console (F12)
2. Look for JavaScript errors
3. Try a different browser

### Can't save settings?
**Solution:**
1. Verify WordPress user has "manage_options" capability
2. Check file permissions on wp-content/uploads/
3. Check WordPress debug log

### Sync not working?
**Solution:**
1. Verify Organization ID is correct (21)
2. Verify Branch ID is correct (113)
3. Check MyClassBoard API is accessible
4. Check WordPress error logs
5. See: MYCLASSBOARD_INTEGRATION_ANALYSIS.md

---

## 📞 Need More Help?

**For Quick Setup:**
→ See: MYCLASSBOARD_QUICK_ACTIVATION.md

**For Complete Checklist:**
→ See: MYCLASSBOARD_DEPLOYMENT_CHECKLIST.md

**For Technical Details:**
→ See: MYCLASSBOARD_INTEGRATION_ANALYSIS.md

---

## ✅ You're Ready!

Once you see the "MyClassBoard Settings" menu, everything is working correctly.

**Next:** Configure the settings as shown above, then test by creating an enquiry.

