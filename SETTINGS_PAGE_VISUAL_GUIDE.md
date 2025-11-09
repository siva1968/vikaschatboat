# Visual Guide: Settings Page Access

**Status**: ✅ LIVE

---

## The Complete Visual Walkthrough

### Screen 1: WordPress Admin Dashboard

```
┌─────────────────────────────────────────────────────────────┐
│ WordPress Admin                      Logout | Edit Profile  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  LEFT MENU          │  MAIN AREA                            │
│  ──────────────     │  ──────────────────────               │
│  📊 Dashboard       │  Welcome to WordPress!                │
│  📝 Posts           │                                        │
│  📄 Pages           │  At a Glance:                          │
│  💬 Comments        │  5 Posts                              │
│  🎨 Appearance      │  0 Pages                              │
│  🔌 Plugins         │  1 User                               │
│  👥 Users           │                                        │
│  🛠️  Tools           │  Recent Activity...                   │
│  ⚙️  Settings        │  Edit your profile                    │
│                     │  Change password                      │
│                     │                                        │
└─────────────────────────────────────────────────────────────┘

STEP 1: Click "⚙️ Settings" in left menu
```

---

### Screen 2: Settings Menu Expanded

```
┌─────────────────────────────────────────────────────────────┐
│ WordPress Admin                      Logout | Edit Profile  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  LEFT MENU          │  MAIN AREA                            │
│  ──────────────     │  ──────────────────────               │
│  📊 Dashboard       │                                        │
│  📝 Posts           │  Please select an option               │
│  📄 Pages           │  from the menu on the left            │
│  💬 Comments        │                                        │
│  🎨 Appearance      │                                        │
│  🔌 Plugins         │                                        │
│  👥 Users           │                                        │
│  🛠️  Tools           │                                        │
│  ⚙️  Settings ▼     │                                        │
│    └─ General       │                                        │
│    └─ Writing       │                                        │
│    └─ Reading       │                                        │
│    └─ Discussion    │                                        │
│    └─ Media         │                                        │
│    └─ Permalinks    │                                        │
│    └─ EduBot AI Config  ← CLICK HERE                         │
│                     │                                        │
└─────────────────────────────────────────────────────────────┘

STEP 2: Click "EduBot AI Config"
```

---

### Screen 3: AI Settings Page Loaded

```
┌──────────────────────────────────────────────────────────────┐
│ WordPress Admin                        Logout | Edit Profile │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  ⚙️  Settings                                               │
│  EduBot AI Configuration                                    │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Enable AI Validation                      [☐] Unchecked│ │
│  │ Checkbox to turn AI validation on/off                  │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ AI Provider          [Claude ▼]                        │ │
│  │ Choose between Claude (recommended) or OpenAI          │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ AI Model             [claude-3-5-sonnet ▼]            │ │
│  │ Select the AI model to use for validation              │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ API Key              [••••••••]                        │ │
│  │ Get your API key from:                                │ │
│  │ 🔗 console.anthropic.com                              │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ Temperature          [0.3]                            │ │
│  │ 0 = Deterministic, 1 = Random                          │ │
│  │ Recommended: 0.3 (for validation)                      │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ Max Tokens           [500]                            │ │
│  │ Maximum response length. Recommended: 500              │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │ Timeout (seconds)    [10]                             │ │
│  │ API request timeout. Recommended: 10                   │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │              [ Save AI Configuration ]                 │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌─ 📚 Configuration Guide ──────────────────────────────┐  │
│  │ • Enable AI Validation: Check to enable AI             │  │
│  │ • Provider: Choose between Claude or OpenAI           │  │
│  │ • Model: Choose model based on speed vs accuracy      │  │
│  │ • API Key: Get from your provider's dashboard         │  │
│  │ • Temperature: Lower = deterministic, Higher = random │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
│  ┌─ ✅ Current Settings ─────────────────────────────────┐  │
│  │ Provider: claude                                       │  │
│  │ Model: claude-3-5-sonnet                              │  │
│  │ Enabled: No                                           │  │
│  │ Temperature: 0.3                                       │  │
│  │ Max Tokens: 500                                       │  │
│  │ Timeout: 10 seconds                                   │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
└──────────────────────────────────────────────────────────────┘

✅ YOU'RE HERE! Settings page is loaded!
```

---

## Step-by-Step Configuration

### Step 1: Enable AI Validation

```
Before:  [ ] Unchecked
         
After:   [✓] Checked
```

### Step 2: Select Provider

```
[Claude ▼]  ← Default, Recommended
└─ Claude 3.5 Sonnet
└─ Claude 3 Opus
└─ Claude 3 Sonnet
└─ Claude 3 Haiku

[OpenAI ▼]
└─ GPT-4
└─ GPT-4 Turbo
└─ GPT-3.5 Turbo
```

### Step 3: Model Auto-Filters

```
When you select "Claude":
[claude-3-5-sonnet ▼]
├─ claude-3-5-sonnet ✅ (visible)
├─ claude-3-opus ✅ (visible)
├─ claude-3-sonnet ✅ (visible)
├─ claude-3-haiku ✅ (visible)
├─ gpt-4 ❌ (hidden)
└─ gpt-3.5-turbo ❌ (hidden)

When you select "OpenAI":
[gpt-4 ▼]
├─ claude-3-5-sonnet ❌ (hidden)
├─ gpt-4 ✅ (visible)
├─ gpt-4-turbo ✅ (visible)
└─ gpt-3.5-turbo ✅ (visible)
```

### Step 4: Get API Key

```
Claude: Go to https://console.anthropic.com/
        └─ API Keys
        └─ Create new key
        └─ Copy (starts with sk-ant-)

OpenAI: Go to https://platform.openai.com/
        └─ API Keys
        └─ Create new key
        └─ Copy (starts with sk-)
```

### Step 5: Enter API Key

```
API Key:  [••••••••]  ← Paste your key here (shown as dots for security)
```

### Step 6: Adjust Settings (Optional)

```
Temperature:    [0.3]  ← Lower = same output, Higher = random
Max Tokens:     [500]  ← Response length limit
Timeout:        [10]   ← API request timeout in seconds
```

### Step 7: Save Settings

```
Click: [ Save AI Configuration ]
```

### Step 8: See Success Message

```
┌─ ✅ Settings saved successfully! ─┐
│ (Green notification at top)        │
└────────────────────────────────────┘
```

### Step 9: Verify in Current Settings

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

## Access Methods

### Method 1️⃣: Direct URL

```
Paste this in address bar:
http://localhost/demo/wp-admin/options-general.php?page=edubot-ai-config

Then press Enter
```

### Method 2️⃣: Via Menu

```
1. Go to: http://localhost/demo/wp-admin/
2. Left sidebar: Click "Settings"
3. Submenu appears: Click "EduBot AI Config"
```

### Method 3️⃣: Menu Path

```
WordPress Admin
  Dashboard
  Posts
  Pages
  Comments
  Appearance
  Plugins
  Users
  Tools
  Settings ← Click
    General
    Writing
    Reading
    Discussion
    Media
    Permalinks
    EduBot AI Config ← Click
```

---

## What Gets Saved

### In Database
```
WordPress wp_options Table:
┌──────────────────────┬────────────────────────┐
│ option_name          │ option_value           │
├──────────────────────┼────────────────────────┤
│ edubot_ai_validator_ │ {                      │
│ settings             │   "enabled": true,     │
│                      │   "provider": "claude",│
│                      │   "model": "claude-...",│
│                      │   "api_key": "sk-...", │
│                      │   "temperature": 0.3, │
│                      │   "max_tokens": 500,   │
│                      │   "timeout": 10        │
│                      │ }                      │
└──────────────────────┴────────────────────────┘
```

### Used By REST API
```
When you POST to validate:
POST /wp-json/edubot/v1/validate/phone

REST API reads from database:
↓
Gets: edubot_ai_validator_settings
↓
Extracts: provider, api_key, model
↓
Uses: Claude or OpenAI API
↓
Returns: Validation result
```

---

## Flow Diagram

```
┌─ Start ─────────────┐
│                     │
├─ Visit Admin ───────┤
│ http://localhost/   │
│ demo/wp-admin/      │
│                     │
├─ Navigate ──────────┤
│ Settings →          │
│ EduBot AI Config    │
│                     │
├─ Configure ─────────┤
│ Fill form:          │
│ • Enable checkbox   │
│ • Select provider   │
│ • Enter API key     │
│ • Adjust settings   │
│                     │
├─ Save ──────────────┤
│ Click:              │
│ Save AI Config      │
│                     │
├─ Stored ────────────┤
│ Saved to:           │
│ wp_options table    │
│                     │
├─ Used ──────────────┤
│ REST API reads:     │
│ /validate/phone     │
│ /validate/grade     │
│                     │
└─ Done! ─────────────┘
   ✅ Configured!
```

---

## Before and After

### BEFORE Configuration
```
REST API running but:
❌ No API key set
❌ Provider not selected
❌ Model not chosen

Result: Can't validate with AI
        Falls back to regex only
```

### AFTER Configuration
```
REST API running with:
✅ API key configured
✅ Provider selected (Claude)
✅ Model chosen (claude-3-5-sonnet)

Result: Uses AI validation
        Smarter, more accurate
        Regex as fallback
```

---

## Success Indicators

### ✅ You Did It Right If:

- [ ] You see the settings page load
- [ ] All form fields are visible
- [ ] Dropdown filters work (Claude/OpenAI)
- [ ] You can enter API key
- [ ] You can click Save button
- [ ] Green success message appears
- [ ] Settings persist after refresh
- [ ] Validation works in chatbot

---

## Troubleshooting Quick Reference

### Problem: Can't see the menu

```
❌ Not admin? → Log in as admin
❌ Plugin disabled? → Activate EduBot Pro
❌ Cache? → Clear browser cache (Ctrl+Shift+Delete)
❌ File missing? → Copy class-ai-settings-page.php
```

### Problem: Form won't save

```
❌ Missing API key? → Get from provider first
❌ Permission denied? → Must be admin user
❌ Cache? → Clear all cache and reload
❌ Timeout? → Wait 10 seconds, try again
```

### Problem: Dropdown not filtering

```
❌ Browser too old? → Update browser (needs ES6)
❌ JavaScript disabled? → Enable JavaScript
❌ Cache? → Hard refresh (Ctrl+Shift+R)
❌ Console error? → Check browser console (F12)
```

---

## Summary

```
┌─ Access Settings Page ───────────────────┐
│ Direct URL:                              │
│ http://localhost/demo/wp-admin/          │
│ options-general.php?page=edubot-ai-config│
│                                          │
│ OR via Menu:                             │
│ Settings → EduBot AI Config              │
└──────────────────────────────────────────┘

┌─ Configure ──────────────────────────────┐
│ ✅ Enable checkbox                       │
│ ✅ Select provider                       │
│ ✅ Select model                          │
│ ✅ Enter API key                         │
│ ✅ Click Save                            │
└──────────────────────────────────────────┘

┌─ Verify ─────────────────────────────────┐
│ ✅ See success message                   │
│ ✅ Settings persist after refresh        │
│ ✅ Used by REST API automatically        │
│ ✅ Validation works in chatbot           │
└──────────────────────────────────────────┘
```

---

**Status**: ✅ Settings page visually documented and ready!
