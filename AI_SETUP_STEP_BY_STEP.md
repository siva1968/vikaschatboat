# AI Input Validation Setup - Step by Step

## Current Status

✅ **Deployed**: All AI validator code is deployed to WordPress  
✅ **Alphanumeric Detection**: Active - catches mixed alphanumeric phone attempts  
⏳ **AI Fallback**: Ready for configuration (optional)  

## What's Working Now (No Configuration Needed)

When user types: `986612sasad`

**System Response**:
```
❌ **Invalid Phone Number - Contains Letters**

You entered: 986612sasad

⚠️ Phone numbers should only contain **digits**, not letters.

📱 Please enter a valid 10-digit mobile number:
• **Numbers only:** No letters or special characters
• **Start with:** 6, 7, 8, or 9
• **Format:** 9876543210 or +91 9876543210
• **Length:** Exactly 10 digits

✅ Valid examples: 9876543210, +91 9876543210
❌ Invalid examples: 986612sasad, 98A6B12C

Try again:
```

**No configuration needed** - This is built-in and active.

---

## Optional: Enable AI Fallback Validation

If you want intelligent AI-based validation as a fallback layer:

### Step 1: Choose Your AI Provider

**Option A: Claude (Recommended for India)**
- Better understanding of Indian context
- More cost-effective
- Supports both simple and complex formats

**Option B: OpenAI (Alternative)**
- GPT-4 is very powerful
- Alternative if Claude unavailable
- Works globally

### Step 2: Get API Key

#### For Claude:

1. Go to: https://console.anthropic.com
2. Click "Sign In" or "Sign Up" (create free account)
3. In the dashboard, click "API Keys" in left menu
4. Click "Create Key" button
5. Copy the key (looks like: `sk-ant-abc123xyz...`)
6. **Keep this secret** - don't share or commit to GitHub

#### For OpenAI:

1. Go to: https://platform.openai.com/api-keys
2. Click "Sign In" or "Sign Up" (create free account)
3. In the dashboard, find "API Keys" section
4. Click "Create new secret key"
5. Copy the key (looks like: `sk-abc123xyz...`)
6. **Keep this secret** - don't share or commit to GitHub

### Step 3: Configure in WordPress

1. **Log in to WordPress Admin**
   - URL: http://localhost/wp-admin/
   - Or your production admin URL

2. **Navigate to Settings**
   - Left menu: **EduBot Pro** → **Settings**
   - Look for section: **AI Validator** (new)

3. **Enable AI Validation**
   - Check the box: ✓ "Enable AI Validation"

4. **Select Provider**
   - Dropdown: **Provider**
   - Choose: **Claude** (recommended) OR **OpenAI**

5. **Paste API Key**
   - Field: **API Key**
   - Paste your key (from Step 2)
   - Click Save

6. **Select Model**
   - Dropdown: **Model**
   - For Claude: Choose **Claude 3.5 Sonnet** (recommended, fast & cheap)
   - For OpenAI: Choose **GPT-4** (powerful) or **GPT-4 Turbo** (faster)

7. **Test Connection**
   - Look for button: 🧪 **Test Connection**
   - Click it
   - Should see: ✅ **"Connection successful!"**
   - If error: Check API key is correct and account has credits

8. **Save Settings**
   - Button: **Save Settings**
   - Should see: "Settings saved" message

### Step 4: Verify Settings (Optional)

Go to: **EduBot Pro → AI Validator Settings → Advanced**

Default settings should be fine:
- Temperature: 0.3 ✓ (good for consistent validation)
- Max Tokens: 500 ✓ (sufficient for validation)
- Cache Results: ✓ checked (saves API calls)
- Use as Fallback: ✓ checked (only use when regex fails)

### Step 5: Test AI Validation

Open chatbot and test:

**Test 1: Phone with Context**
```
User: Sujay
User: sujay@email.com
User: my number is 9876543210
User: Grade 5

Expected: ✅ Accepts (AI understands context)
```

**Test 2: Grade in Natural Language**
```
User: Sujay
User: sujay@email.com
User: 9876543210
User: I'm in class five

Expected: ✅ Accepts as Grade 5 (AI understands "five")
```

**Test 3: Alphanumeric (No AI Needed)**
```
User: Sujay
User: sujay@email.com
User: 986612sasad

Expected: ❌ Error "Contains Letters" (Layer 2 catches, no AI needed)
```

### Step 6: Monitor Usage (Optional)

Go to: **EduBot Pro → AI Validator Settings → Logs Tab**

View:
- Recent validation attempts
- What inputs were validated
- Whether they were valid or invalid
- Timestamps

This helps you see how often AI is being used.

---

## Troubleshooting

### Problem: Settings page doesn't appear

**Solution**:
1. Deactivate EduBot Pro plugin
2. Activate EduBot Pro plugin
3. Go to: EduBot Pro → Settings
4. Hard refresh browser: **Ctrl+Shift+R** (Windows) or **Cmd+Shift+R** (Mac)

### Problem: Test Connection fails with "Invalid API key"

**Solution**:
1. Copy API key again from provider website
2. Paste it fresh (no extra spaces)
3. Make sure you're using the correct provider (Claude key in Claude setting, OpenAI key in OpenAI setting)
4. Check your account has credits available
5. Try again

### Problem: Test Connection fails with timeout

**Solution**:
1. Check your internet connection
2. Try changing model to faster one:
   - Claude: Try "Claude 3 Opus"
   - OpenAI: Try "GPT-3.5 Turbo"
3. Increase timeout in Advanced settings (to 15 seconds)
4. Check if AI provider is having issues (https://status.anthropic.com or https://status.openai.com)

### Problem: AI is not being used

**Solution**:
1. Check: ✓ "Enable AI Validation" is checked
2. Check: ✓ "Use as Fallback" is checked
3. Run: 🧪 Test Connection button
4. Review: Logs tab to see if AI was called
5. Note: AI only runs when regex fails (usually rare)

### Problem: Validation is slow

**Solution**:
1. Enable caching (Advanced settings)
2. Lower timeout from 10 to 5 seconds
3. Use faster model:
   - Claude: 3.5 Sonnet is already fast
   - OpenAI: Use "GPT-3.5 Turbo" instead of GPT-4

### Problem: Getting "Rate limit exceeded"

**Solution**:
1. Go to Advanced settings
2. Increase "Rate Limit (per hour)" from 100 to 200
3. Increase "Cache Duration" to cache results longer
4. Or reduce `use_as_fallback` (don't use for everything)

---

## Costs (Free tier!)

### For Personal/Testing
- **Claude Free**: 100K tokens/month free (~$0/month)
- **OpenAI Free**: $5 trial credit (~$0/month for light use)

### For Small School (100 users)
- **Expected usage**: 50-100 validations/month
- **Claude cost**: ~$0.01/month
- **OpenAI cost**: ~$0.05/month

### Cost Control Built-in
- ✅ Rate limiting (default: 100/hour)
- ✅ Result caching (default: 1 hour)
- ✅ Fallback only (doesn't use AI for every input)
- ✅ No charges if disabled

---

## What Data Is Sent?

When AI validation is used:

**Sent to AI API**:
- User input (e.g., "9876543210")
- Validation prompt (what to validate)
- That's it! ✅

**NOT Sent**:
- Student name
- Email
- Full conversation
- Sensitive data

---

## Advanced: API Costs Comparison

| Provider | Model | Input | Output | Per Validation |
|----------|-------|-------|--------|-----------------|
| Claude | 3.5 Sonnet | $3/MTok | $15/MTok | ~$0.00002 |
| Claude | 3 Opus | $15/MTok | $75/MTok | ~$0.0001 |
| OpenAI | GPT-4 | $30/MTok | $60/MTok | ~$0.00009 |
| OpenAI | GPT-4 Turbo | $10/MTok | $30/MTok | ~$0.00003 |
| OpenAI | GPT-3.5 | $0.5/MTok | $1.5/MTok | ~$0.0000005 |

**Bottom Line**: Cheapest is GPT-3.5 Turbo, best quality is Claude 3.5 Sonnet, best value is Claude.

---

## Security & Privacy

✅ **Your data is safe:**
- API keys stored in WordPress database (encrypted by WordPress)
- User input only sent for validation (not stored)
- Caching stores results locally (not on AI servers)
- No sharing of data between users
- GDPR compliant (minimal data processing)

⚠️ **Things to know:**
- User input sent to Claude/OpenAI APIs
- Review their privacy policies:
  - Claude: https://www.anthropic.com/privacy
  - OpenAI: https://openai.com/privacy
- No sensitive data (only phone/grade) sent

---

## Settings Reference

### General Tab (Main Settings)

| Setting | Default | What to Do |
|---------|---------|-----------|
| Enable AI Validation | ❌ Off | ✅ Check to enable |
| Provider | Claude | Keep as Claude (recommended) |
| Model | Claude 3.5 Sonnet | Keep (best balance) |
| API Key | (empty) | Paste your API key |
| Use AI as Fallback | ✅ On | Keep on (only use when needed) |
| Cache Results | ✅ On | Keep on (saves money) |

### Advanced Tab (Tuning)

| Setting | Default | Range | What It Does |
|---------|---------|-------|------------|
| Temperature | 0.3 | 0-1 | Lower = consistent, Higher = creative |
| Max Tokens | 500 | 100-2000 | Max response length (500 is fine) |
| Timeout | 10 | 5-30 | How long to wait (10 is fine) |
| Cache Duration | 1 hour | 1-24 | How long to remember (1 hour is good) |
| Rate Limit | 100 | 10-1000 | Max calls/hour (100 is safe) |
| Log AI Calls | ✅ On | - | Enable for debugging (safe to log) |

---

## How to Check Everything is Working

### Test 1: Settings Page Loads
- [ ] Go to: EduBot Pro → Settings
- [ ] See: "AI Input Validation Settings" heading
- [ ] See: Enable checkbox, provider dropdown, API key field

### Test 2: Connection Works
- [ ] Paste API key
- [ ] Click 🧪 "Test Connection"
- [ ] See: ✅ "Connection successful!"

### Test 3: Phone Validation Works (Alphanumeric)
- [ ] Open chatbot
- [ ] Type: `Sujay` → `sujay@email.com` → `986612sasad`
- [ ] See: ❌ Error "Contains Letters"
- [ ] Type: `9876543210`
- [ ] See: ✅ Proceeds to Grade

### Test 4: Logs Show Activity
- [ ] Go to: EduBot Pro → Settings → Logs tab
- [ ] See: Recent validations listed
- [ ] See: Timestamps and results

---

## One-Page Setup Summary

```
┌─────────────────────────────────────────────────────────────┐
│ QUICK SETUP - 5 MINUTES                                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│ 1. Get API Key (choose one):                                │
│    Claude: https://console.anthropic.com → API Keys        │
│    OpenAI: https://platform.openai.com/api-keys            │
│                                                              │
│ 2. In WordPress:                                            │
│    EduBot Pro → Settings → AI Validator                    │
│                                                              │
│ 3. Enable & Configure:                                      │
│    ✓ Enable AI Validation                                  │
│    Provider: Claude                                         │
│    Model: Claude 3.5 Sonnet                                │
│    API Key: Paste key                                       │
│                                                              │
│ 4. Test Connection:                                         │
│    Click 🧪 Test Connection                                │
│    Should see ✅ Success                                    │
│                                                              │
│ 5. Save:                                                    │
│    Click Save Settings                                      │
│                                                              │
│ Done! ✅ AI validation ready                               │
│                                                              │
│ Cost: ~$0.01-0.10/month                                    │
│ Time: 5 minutes                                             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Still Have Questions?

📖 **Read**: AI_VALIDATOR_QUICKSTART.md (overview)  
📘 **Reference**: AI_VALIDATOR_GUIDE.md (detailed)  
📝 **Technical**: ALPHANUMERIC_PHONE_DETECTION_FIX.md (how it works)  
🆘 **Support**: Check logs in Logs tab, enable logging in Advanced

---

## Ready to Go!

✅ Everything is deployed and working  
✅ Alphanumeric detection is active (no config needed)  
✅ AI fallback ready (optional, 5-minute setup)  

**Next Step**: Follow the 5-minute setup above OR just use the alphanumeric detection that's already working!
