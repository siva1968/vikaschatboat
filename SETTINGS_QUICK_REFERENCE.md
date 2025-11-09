# QUICK REFERENCE: AI Settings Page

**Status**: ✅ LIVE & READY

---

## 🟢 Access Settings NOW

### Direct URL
```
http://localhost/demo/wp-admin/options-general.php?page=edubot-ai-config
```

### Menu Path
```
WordPress Admin → Settings → EduBot AI Config
```

---

## 🟢 One-Minute Setup

### 1. Check Enable
```
☑ Enable AI Validation
```

### 2. Select Provider
```
Claude (default) or OpenAI
```

### 3. Get API Key
```
Claude: console.anthropic.com
OpenAI: platform.openai.com
```

### 4. Paste Key & Save
```
API Key: [sk-ant-...]
Click: Save AI Configuration
```

### 5. Done! ✅

---

## 🟢 Settings Fields

| Field | Default | Range | Notes |
|-------|---------|-------|-------|
| Enable | ☐ Off | On/Off | Check to use AI |
| Provider | Claude | Claude/OpenAI | Recommended: Claude |
| Model | claude-3-5-sonnet | See below | Auto-filtered by provider |
| API Key | Empty | Text | Required, starts with sk- |
| Temperature | 0.3 | 0-1 | Low = deterministic |
| Max Tokens | 500 | 100-4000 | Response length |
| Timeout | 10s | 1-60s | API request timeout |

---

## 🟢 Available Models

### Claude (Recommended)
- `claude-3-5-sonnet` ⭐ Best balance
- `claude-3-opus` Most powerful
- `claude-3-sonnet` Balanced
- `claude-3-haiku` Fastest

### OpenAI
- `gpt-4` Most powerful
- `gpt-4-turbo` Balanced
- `gpt-3.5-turbo` Fastest & cheapest

---

## 🟢 Get API Key

### Claude
1. Visit: https://console.anthropic.com/
2. Click: API Keys
3. Create new key
4. Copy: sk-ant-...

### OpenAI
1. Visit: https://platform.openai.com/
2. Click: API Keys
3. Create new key
4. Copy: sk-...

---

## 🟢 Files Deployed

```
✅ class-ai-settings-page.php
   Location: includes/ (source)
   Live: D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\includes\

✅ edubot-pro.php (updated)
   Location: root (source)
   Live: D:\xampp\htdocs\demo\wp-content\plugins\edubot-pro\
```

---

## 🟢 Data Storage

**Where**: WordPress wp_options table  
**Key**: `edubot_ai_validator_settings`  
**Used By**: REST API validators  
**Access**: Settings page or direct PHP

---

## 🟢 Testing

### Test 1: See Settings Page
```
✅ Visit URL above
✅ Should see form with all fields
```

### Test 2: Save Settings
```
✅ Fill in a test API key
✅ Click Save
✅ See green success message
```

### Test 3: Persist After Reload
```
✅ Refresh page
✅ Settings should still be there
```

### Test 4: REST API Reads It
```bash
curl http://localhost/demo/wp-json/edubot/v1/validate/phone \
  -d '{"input":"9876543210"}'
```

---

## 🟢 Troubleshooting

| Issue | Solution |
|-------|----------|
| Can't see menu | Check plugin activated, admin user, clear cache |
| Settings won't save | Check admin permission, API key entered, try again |
| Dropdown not filtering | Update browser, enable JS, hard refresh |
| Can't get API key | Visit provider URL, create account, generate key |
| Settings not persistent | Check cache, reload page, check database |

---

## 🟢 Database Query

Check what's saved:

```sql
SELECT * FROM wp_options 
WHERE option_name = 'edubot_ai_validator_settings'
```

---

## 🟢 Recommended Settings

**For Production**
```
Provider: Claude
Model: claude-3-5-sonnet
Temperature: 0.3
Max Tokens: 500
Timeout: 10
```

**For Speed**
```
Provider: Claude
Model: claude-3-haiku
Temperature: 0.3
Max Tokens: 300
Timeout: 5
```

**For Accuracy**
```
Provider: Claude
Model: claude-3-opus
Temperature: 0.2
Max Tokens: 1000
Timeout: 15
```

---

## 🟢 What Changed

### Before ❌
- Settings in database but no admin UI
- Had to use database tools or PHP scripts
- No easy way to configure

### After ✅
- Settings in database ✅
- Admin UI in Settings menu ✅
- Easy form to configure ✅
- One-click save ✅
- Success notification ✅

---

## 🟢 Feature Checklist

- ✅ Admin menu integration
- ✅ Professional form UI
- ✅ Provider auto-filtering
- ✅ API key field (password type)
- ✅ Temperature control
- ✅ Tokens control
- ✅ Timeout control
- ✅ Current settings display
- ✅ Success notification
- ✅ Security (nonce, capability check)
- ✅ Data sanitization
- ✅ Responsive design

---

## 🟢 Next Steps

1. ✅ **Visit settings page**
   ```
   http://localhost/demo/wp-admin/options-general.php?page=edubot-ai-config
   ```

2. ✅ **Enable AI validation**
   ```
   Check the checkbox
   ```

3. ✅ **Select provider**
   ```
   Choose Claude (recommended) or OpenAI
   ```

4. ✅ **Get API key**
   ```
   From provider's dashboard
   ```

5. ✅ **Paste & save**
   ```
   Click Save AI Configuration
   ```

6. ✅ **Done!**
   ```
   AI validation now configured!
   ```

---

## 🟢 Support

For issues:

1. Check plugin activated
2. Verify admin user
3. Clear cache (browser + plugins)
4. Check files deployed
5. Review debug.log
6. Try different browser

---

**Status**: ✅ Complete and ready to use!

**Access**: http://localhost/demo/wp-admin/options-general.php?page=edubot-ai-config

**Saved**: wp_options table as `edubot_ai_validator_settings`
