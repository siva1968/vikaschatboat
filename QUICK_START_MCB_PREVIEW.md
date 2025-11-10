# 🚀 Quick Start: MCB Preview Button

## What's New?

Added a **"👁️ Preview"** button to your Applications admin page that shows exactly what data will be sent to MCB for each enquiry.

## Access It

1. Go to WordPress Admin
2. Click **EduBot** → **Applications**
3. Find any enquiry row
4. Click the **👁️ Preview** button

## What You'll See

A modal popup showing:

| Section | Shows |
|---------|-------|
| 👤 Student Info | Name, parent, email, phone, DOB |
| 🎓 Academic Info | Class ID, academic year ID |
| ⚙️ MCB Config | Org ID, branch ID, lead source ID |
| 📊 Marketing Data | **✓ Captured** or **Not captured** for each UTM field |
| 📋 JSON Payload | Complete MCB data as JSON |

## Why Are Marketing Parameters "Not captured"?

If you see "Not captured" for utm_source, utm_medium, utm_campaign, gclid, fbclid:

### ✅ It's Probably Because:
- User visited form without UTM parameters in URL
- E.g., `http://localhost/demo/` (missing `?utm_source=google`)

### ✅ To Test & Fix:
1. Visit with UTM params:
   ```
   http://localhost/demo/?utm_source=google&utm_medium=cpc&utm_campaign=admissions_2025
   ```
2. Fill and submit form
3. Go to Applications
4. Click Preview
5. Should now show "✓ Captured" for marketing data

## Troubleshooting

### If Marketing Data Still Shows "Not captured":

Use the diagnostic tool:
```
http://localhost/demo/debug_utm_capture.php
```

This traces the complete flow and shows you exactly where data is being lost.

---

**For Complete Details:** See `IMPLEMENTATION_MCB_PREVIEW_BUTTON.md`
