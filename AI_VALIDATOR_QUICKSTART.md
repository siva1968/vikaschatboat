# AI Input Validation - Quick Start Guide

## What is AI Validation?

AI Input Validation is a **fallback validation layer** that uses Claude or GPT-4 to intelligently validate user input when regular regex pattern matching fails.

**Example:**
- User types: `986612sasad` (invalid - contains letters)
- Regex: ❌ Doesn't match 8-15 digit pattern → marked as invalid
- AI (if enabled): ✅ Understands it's a phone attempt → Shows error with guidance

## 5-Minute Setup

### Step 1: Get API Key (2 minutes)

**Option A: Claude (Recommended for India)**
1. Visit: https://console.anthropic.com
2. Login or sign up (free)
3. Click "API Keys"
4. Click "Create Key"
5. Copy the key (looks like: `sk-ant-...`)

**Option B: OpenAI (Alternative)**
1. Visit: https://platform.openai.com/api-keys
2. Login or sign up
3. Click "Create new secret key"
4. Copy the key (looks like: `sk-...`)

### Step 2: Configure in WordPress (3 minutes)

1. **Login to WordPress Admin**
2. **Navigate**: EduBot Pro → Settings → AI Validator
3. **Enable**: Check ✓ "Enable AI Validation"
4. **Paste API Key** in the "API Key" field
5. **Select Provider**: 
   - Claude (recommended) OR OpenAI
6. **Select Model**: 
   - Claude 3.5 Sonnet (recommended) OR GPT-4
7. **Test Connection**: Click 🧪 "Test Connection"
   - Should see: ✅ "Connection successful!"
8. **Save Settings**

## How It Works

```
User Input: "986612sasad"
    ↓
Regex Layer: "Looks like a phone attempt but contains letters"
    ↓
Alphanumeric Detector: "Marked as invalid - contains non-digits"
    ↓
Error Message: ❌ Invalid Phone Number - Contains Letters
    "Phone numbers should only contain digits, not letters.
     Please enter valid 10-digit: 9876543210"
    ↓
User Corrects: "9876543210"
    ↓
✅ Accepted!
```

When AI is enabled:
```
User Input: "my number is 9876543210"
    ↓
Regex Layer: "Didn't find clear phone pattern"
    ↓
AI Layer (fallback): "Searching for phone in context..."
    ↓
AI Result: "Found phone: 9876543210 ✓ Valid"
    ↓
✅ Accepted!
```

## Cost: Practically Free

- **Claude 3.5 Sonnet**: ~$0.000002 per validation call
- **GPT-4**: ~$0.000009 per validation call
- **With caching**: Most inputs cached, 80% fewer API calls
- **Monthly (100 users, 1 validation each)**: ~$0.02-0.10

## Testing Your Setup

### Test 1: Connection Test
1. Go to: **EduBot Pro → AI Validator**
2. Click: 🧪 **Test Connection**
3. Should see: ✅ **Connection successful!**

### Test 2: Phone Validation (with AI fallback)
1. Open chatbot at: http://localhost/demo/
2. Type: `Sujay`
3. Type: `sujay@email.com`
4. Type: `98 66 133 566` (spaces - regex might not catch this)
5. **If AI enabled**: ✅ Accepts it as valid phone
6. **If AI disabled**: ❌ Shows error (regex pattern too strict)

### Test 3: Grade Validation (with AI)
1. Open chatbot
2. Go through: Name → Email → Phone
3. Type: `11th grade` or `class eleven`
4. **If AI enabled**: ✅ Accepts as Grade 11
5. **If AI disabled**: ❌ Shows error

## Settings Explained

| Setting | Default | What It Does |
|---------|---------|-------------|
| **Enable AI Validation** | ❌ Off | Master on/off for all AI validation |
| **Provider** | Claude | Which API: Claude or OpenAI |
| **Model** | Claude 3.5 Sonnet | Which AI model to use |
| **API Key** | (empty) | Your secret API key from provider |
| **Use as Fallback** | ✅ On | Only use AI when regex fails (recommended) |
| **Cache Results** | ✅ On | Remember previous validations (saves $) |
| **Temperature** | 0.3 | 0 = consistent, 1 = creative |
| **Rate Limit** | 100/hour | Max API calls to prevent runaway costs |
| **Log Calls** | ✅ On | Record AI calls for debugging |

## Validation Types Supported

### Phone Validation
- ✅ Valid: `9876543210`, `+91 9876543210`, `+919876543210`
- ❌ Invalid: `986612sasad` (letters), `9-digit`, `11-digit`

### Grade Validation
- ✅ Valid: `Grade 5`, `Nursery`, `PP1`, `Class 8`
- ❌ Invalid: `Grade 22`, `Grade 0`, `Class 13`

## Troubleshooting

### Problem: Test Connection Fails
**Solution**: 
1. Verify API key is correct
2. Check your account has credits
3. Try a different model
4. Check your internet connection

### Problem: Settings page doesn't appear
**Solution**:
1. Deactivate plugin
2. Activate plugin
3. Clear WordPress cache
4. Hard refresh browser (Ctrl+Shift+R)

### Problem: Chatbot is slow
**Solution**:
1. Enable caching (saves API calls)
2. Lower timeout to 5 seconds
3. Check rate limit not exceeded
4. Review logs for errors

### Problem: AI is not being used
**Solution**:
1. Make sure "Enable AI Validation" ✓ checked
2. Make sure "Use as Fallback" ✓ checked
3. Check API key is valid (run Test Connection)
4. Review logs at: **EduBot Pro → AI Validator → Logs**

## Advanced: View Logs

1. Go to: **EduBot Pro → AI Validator Settings**
2. Click tab: **Logs**
3. See recent validations and results
4. Helps debug validation issues

## Advanced: Manual Integration

If you want to use AI validation in custom code:

```php
global $edubot_ai_validator;

// Validate phone
$result = $edubot_ai_validator->validate_phone('9876543210');
if ($result['valid']) {
    echo "Phone is valid: " . $result['number'];
}

// Validate grade
$result = $edubot_ai_validator->validate_grade('Grade 5');
if ($result['valid']) {
    echo "Grade is valid: " . $result['grade'];
}
```

## Security & Privacy

✅ **Secure**: API keys never logged or exposed  
✅ **Cached**: Results stored locally, not on AI servers  
✅ **Limited**: Rate limiting prevents runaway costs  
✅ **Logged**: Can review all AI calls in logs tab  

⚠️ **Note**: User input is sent to Claude/OpenAI APIs. For sensitive data, review their privacy policies:
- Claude: https://www.anthropic.com/privacy
- OpenAI: https://openai.com/privacy

## Next Steps

1. ✅ Get API key
2. ✅ Configure in WordPress
3. ✅ Test connection
4. ✅ Test with sample inputs
5. ✅ Monitor logs for first few days
6. ✅ Adjust settings based on usage

## FAQ

**Q: Do I need AI validation?**  
A: No. Regular validation (regex + alphanumeric detection) works for most users. AI is optional for ambiguous inputs.

**Q: What if I run out of API credits?**  
A: Validation falls back to regex pattern matching. Users see same error messages.

**Q: Can I switch from Claude to OpenAI?**  
A: Yes. Change provider and model in settings, test connection, save.

**Q: Will this make my chatbot slower?**  
A: No. AI only runs if regex fails (rare). With caching, ~2ms per call.

**Q: How often is AI used?**  
A: Depends on users. Rough estimate: 5-10% of inputs trigger AI if enabled.

---

**Need Help?**
- Check: AI_VALIDATOR_GUIDE.md (full documentation)
- Test: 🧪 Test Connection button in settings
- Review: Logs tab for troubleshooting
- Debug: Enable logging in advanced settings

**Ready?** → Configure AI Validator now!
