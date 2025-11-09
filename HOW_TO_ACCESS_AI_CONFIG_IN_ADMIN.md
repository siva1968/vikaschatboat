# How to Access AI Configuration in WordPress Admin

**Date**: November 6, 2025  
**Status**: ✅ Live & Ready  
**Access Location**: WordPress Settings Menu

---

## Quick Steps

### Step 1: Go to WordPress Admin
```
URL: http://localhost/demo/wp-admin/
```

### Step 2: Click Settings Menu
Look for: **Settings** → **EduBot AI Config**

### Step 3: Configure Settings
- ✅ Enable AI Validation (checkbox)
- ✅ Select Provider (Claude or OpenAI)
- ✅ Select Model (claude-3-5-sonnet recommended)
- ✅ Enter API Key
- ✅ Adjust Temperature, Tokens, Timeout

### Step 4: Click Save
All settings saved to database!

---

## Visual Guide

```
WordPress Admin Dashboard
├─ Settings (left menu)
│  ├─ General
│  ├─ Writing
│  ├─ Reading
│  ├─ Discussion
│  ├─ Media
│  ├─ Permalinks
│  ├─ EduBot AI Config ← ✅ CLICK HERE
│  └─ Privacy
```

---

## Settings Page Features

### Enable/Disable AI
```
☑ Enable AI Validation
Checkbox to turn AI validation on/off
```

### Provider Selection
```
AI Provider: [Claude ▼]
- Claude (Anthropic) ← Default
- OpenAI
```

### Model Selection
```
AI Model: [claude-3-5-sonnet ▼]

Claude Models:
- claude-3-5-sonnet (Recommended)
- claude-3-opus (Most Powerful)
- claude-3-sonnet (Balanced)
- claude-3-haiku (Fastest)

OpenAI Models:
- gpt-4 (Most Powerful)
- gpt-4-turbo (Balanced)
- gpt-3.5-turbo (Fastest & Cheapest)
```

### API Key
```
API Key: [••••••••]
Get from: https://console.anthropic.com/ (Claude)
          https://platform.openai.com/ (OpenAI)
```

### Temperature Setting
```
Temperature: [0.3]
0 = Same output every time
1 = Creative random output
Recommended: 0.3
```

### Max Tokens
```
Max Tokens: [500]
Maximum response length
Recommended: 500
```

### Timeout
```
Timeout: [10 seconds]
API request timeout
Recommended: 10 seconds
```

### Current Settings Display
```
✅ Current Settings
Provider: claude
Model: claude-3-5-sonnet
Enabled: Yes
Temperature: 0.3
Max Tokens: 500
Timeout: 10 seconds
```

---

## Complete Flow

### Full Path to Settings Page

1. **Open Admin**
   ```
   http://localhost/demo/wp-admin/
   ```

2. **Left Menu → Settings**
   ```
   In WordPress left sidebar, look for "Settings"
   ```

3. **Click EduBot AI Config**
   ```
   Under Settings menu, click "EduBot AI Config"
   ```

4. **You should see:**
   - ✅ Enable AI Validation checkbox
   - ✅ Provider dropdown (Claude/OpenAI)
   - ✅ Model dropdown (automatically filters by provider)
   - ✅ API Key password field
   - ✅ Temperature number input
   - ✅ Max Tokens number input
   - ✅ Timeout number input
   - ✅ Save Configuration button
   - ✅ Current Settings display box

---

## Setup Instructions

### For Claude API

1. **Get Claude API Key**
   - Visit: https://console.anthropic.com/
   - Click "API Keys"
   - Create new API key
   - Copy key (starts with `sk-ant-`)

2. **In WordPress Settings**
   - Select Provider: `Claude`
   - Select Model: `claude-3-5-sonnet`
   - Paste API Key
   - Click "Save AI Configuration"

### For OpenAI API

1. **Get OpenAI API Key**
   - Visit: https://platform.openai.com/api-keys
   - Create new API key
   - Copy key (starts with `sk-`)

2. **In WordPress Settings**
   - Select Provider: `OpenAI`
   - Select Model: `gpt-4` or `gpt-3.5-turbo`
   - Paste API Key
   - Click "Save AI Configuration"

---

## Features of Settings Page

✅ **Automatic Provider Filtering**
- When you change provider, model list updates automatically

✅ **Current Settings Display**
- See what's currently saved in real-time

✅ **Security**
- API key stored as password field (hidden)
- Nonce verification for form submission
- WordPress capability check (admin only)

✅ **Validation**
- Temperature limited to 0-1
- Tokens limited to 100-4000
- Timeout limited to 1-60 seconds

✅ **Documentation**
- Help text for each field
- Links to get API keys from provider
- Recommended values shown

✅ **Success Message**
- Green notification when settings saved

---

## Default Values

If no settings are configured, defaults are:

```php
'enabled'     => false                 // AI disabled by default
'provider'    => 'claude'              // Default to Claude
'api_key'     => ''                    // Empty until configured
'model'       => 'claude-3-5-sonnet'   // Recommended model
'temperature' => 0.3                   // Low randomness
'max_tokens'  => 500                   // Standard response length
'timeout'     => 10                    // 10 second timeout
```

---

## Troubleshooting

### Settings Page Not Showing?

1. **Check Plugin Activated**
   - Go to: Plugins menu
   - Look for: "EduBot Pro"
   - Make sure it says "Deactivate" (not "Activate")

2. **Check Permissions**
   - Make sure you're logged in as admin
   - Non-admin users won't see the menu

3. **Clear Cache**
   - If using caching plugin, clear cache
   - Hard refresh browser (Ctrl+Shift+R)

4. **Check File Deployed**
   - Should exist: `D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\includes\class-ai-settings-page.php`
   - If missing, copy it from source folder

### Settings Not Saving?

1. **Check Admin Nonce**
   - Make sure form submission includes nonce
   - Should see success message

2. **Check Permissions**
   - Form requires `manage_options` capability
   - Only admins can save

3. **Check Debug Log**
   - Look at: `D:\xampp\htdocs\demo\wp-content\debug.log`
   - Check for errors

### Settings Showing But Dropdown Not Filtering?

1. **Check JavaScript**
   - Browser must support ES6
   - Check browser console for errors
   - Try refreshing page

2. **Try Different Provider**
   - Select Claude, then OpenAI
   - Models should switch automatically

---

## Files Deployed

```
✅ includes/class-ai-settings-page.php
   - Location: D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\includes\
   - Size: ~8KB
   - Status: Active

✅ edubot-pro.php (updated)
   - Location: D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\
   - Change: Added require for settings page
   - Status: Active
```

---

## What Gets Saved

When you click "Save AI Configuration", this gets stored:

```php
wp_options table
├─ option_name: edubot_ai_validator_settings
└─ option_value: array(
    'enabled'     => true/false
    'provider'    => 'claude' or 'openai'
    'api_key'     => 'your-api-key'
    'model'       => 'model-name'
    'temperature' => 0.3
    'max_tokens'  => 500
    'timeout'     => 10
)
```

**Accessible via REST API:**
```
POST /wp-json/edubot/v1/validate/phone
Uses: edubot_ai_validator_settings from wp_options
```

---

## Quick Troubleshooting Checklist

- [ ] Plugin activated?
- [ ] Logged in as admin?
- [ ] Cleared cache?
- [ ] Settings menu visible?
- [ ] EduBot AI Config menu item shows?
- [ ] Can select provider?
- [ ] Can select model?
- [ ] Can enter API key?
- [ ] Save button works?
- [ ] Success message shows?
- [ ] Settings persist after reload?

---

## Screenshots

### Main Settings Page
```
┌─────────────────────────────────┐
│ EduBot AI Validator Config      │
├─────────────────────────────────┤
│                                 │
│ ☑ Enable AI Validation          │
│                                 │
│ AI Provider:    [Claude ▼]      │
│                                 │
│ AI Model:       [claude-3-5...▼]│
│                                 │
│ API Key:        [••••••••]      │
│                                 │
│ Temperature:    [0.3]           │
│                                 │
│ Max Tokens:     [500]           │
│                                 │
│ Timeout:        [10]            │
│                                 │
│         [ Save AI Configuration ]│
│                                 │
├─────────────────────────────────┤
│ ✅ Current Settings             │
│ Provider: claude                │
│ Model: claude-3-5-sonnet        │
│ Enabled: Yes                    │
└─────────────────────────────────┘
```

---

## Summary

✅ **Settings Page Deployed**
✅ **Admin Menu Integrated**
✅ **Database Settings Saved**
✅ **REST API Ready to Use**

### To Configure AI:
1. Go to Admin
2. Settings → EduBot AI Config
3. Enter API Key
4. Select Model
5. Click Save
6. Done! ✅

---

**Status**: ✅ Configuration Page Live & Ready to Use
