# AI Input Validation - Complete Implementation Guide

## Overview

The EduBot Pro AI Validator provides intelligent input validation as a fallback layer when regular regex/pattern matching fails. This is particularly useful for:

- **Phone numbers with typos or unusual formats** - AI can understand intent
- **Grade inputs in natural language** - "Class 5" or "5th grade" variations
- **Ambiguous user inputs** - Mixed alphanumeric, non-standard spellings
- **Contextual validation** - Understanding Indian education system context

## Architecture

```
┌─────────────────────────────────────────┐
│   User Input                            │
│   "986612sasad"                        │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│   Layer 1: Regex Pattern Matching        │
│   - parse_personal_info()               │
│   - extract_grade_from_message()        │
│   - FAST, NO API CALLS                  │
└────────────┬────────────────────────────┘
             │ (Success? Use it)
             │ (Fail? Continue)
             ▼
┌─────────────────────────────────────────┐
│   Layer 2: Alphanumeric Detection       │
│   - Catch mixed alphanumeric inputs     │
│   - Mark as phone_invalid flag          │
└────────────┬────────────────────────────┘
             │ (Detected? Show error)
             │ (Not detected? Continue)
             ▼
┌─────────────────────────────────────────┐
│   Layer 3: AI Validation (Optional)      │
│   - Use if enabled and fallback mode    │
│   - Call Claude/GPT API                 │
│   - INTELLIGENT PARSING                 │
└────────────┬────────────────────────────┘
             │ (Valid? Accept it)
             │ (Invalid? Show error)
             ▼
┌─────────────────────────────────────────┐
│   Error Message to User                 │
│   Clear guidance for correction          │
└─────────────────────────────────────────┘
```

## Setup Instructions

### 1. Get API Key

**For Claude (Recommended):**
1. Visit: https://console.anthropic.com
2. Sign up or log in
3. Navigate to "API Keys"
4. Click "Create Key"
5. Copy the key (starts with `sk-ant-`)

**For OpenAI:**
1. Visit: https://platform.openai.com/api-keys
2. Sign up or log in
3. Click "Create new secret key"
4. Copy the key (starts with `sk-`)

### 2. Configure in WordPress

1. Go to **WordPress Admin → EduBot Pro → AI Validator Settings**
2. Enable: **✓ Enable AI Validation**
3. Select Provider:
   - Claude (Recommended for India context)
   - OpenAI
4. Paste API Key in the "API Key" field
5. Select Model:
   - **Claude 3.5 Sonnet** (Recommended - best balance)
   - Claude 3 Opus (Most powerful)
   - GPT-4 (OpenAI alternative)
   - GPT-4 Turbo (Faster)
6. Set **✓ Use AI as Fallback** (uses AI only when regex fails)
7. Click **🧪 Test Connection**
8. Save Settings

### 3. Advanced Configuration

**Temperature** (0.0 - 1.0)
- **0.3** (Default): Deterministic, consistent validation ✅ RECOMMENDED
- **0.7**: Slightly creative
- **1.0**: Maximum variation

**Max Tokens**: 500 (usually sufficient for validation)

**Cache Results**: ✓ Enabled (saves API calls and cost)
- Cache Duration: 1 hour (can adjust)

**Rate Limiting**: 100 requests/hour (prevent runaway costs)

**Log AI Calls**: ✓ Enabled (for debugging)

## Cost Estimation

### Claude 3.5 Sonnet
- Input: $0.003 per 1M tokens
- Output: $0.015 per 1M tokens
- ~100 tokens per validation
- **Cost: ~$0.000002 per call** (practically free)

### GPT-4
- Input: $0.03 per 1M tokens
- Output: $0.06 per 1M tokens
- ~100 tokens per validation
- **Cost: ~$0.000009 per call** (~10x Claude)

## Usage in Code

### Automatic (Fallback Mode)

When enabled with fallback mode, AI validation is **automatic**:

```php
// In parse_personal_info() if regex fails:
$ai_result = edubot_ai_validate_phone($message, $parsed);

if ($ai_result && isset($ai_result['valid'])) {
    if ($ai_result['valid']) {
        $info['phone'] = $ai_result['number'];  // Use AI result
    } else {
        $info['phone'] = $message;
        $info['phone_invalid'] = true;  // Show error
    }
}
```

### Manual Validation

For custom validation logic:

```php
global $edubot_ai_validator;

$result = $edubot_ai_validator->validate_phone('9876543210');
// Returns: ['valid' => true, 'number' => '+919876543210', 'reason' => 'Valid 10-digit...']

$result = $edubot_ai_validator->validate_grade('Grade 5, CBSE');
// Returns: ['valid' => true, 'grade' => 'Grade 5', 'grade_number' => 5, 'reason' => '...']
```

## AI Prompts and Response Format

### Phone Validation Prompt

```
You are a phone number validator for an Indian school admission chatbot.

User Input: 986612sasad

Task: Extract and validate the phone number from the input.

Valid Format: Indian 10-digit mobile numbers starting with 6-9: 
9876543210, +91-9876543210, +91 9876543210

Invalid examples: 986612sasad (contains letters), 9-digit numbers, 11+ digit numbers

Respond ONLY in this JSON format (no markdown, no extra text):
{
  "valid": true or false,
  "number": "extracted 10-digit number or null",
  "digits_found": number,
  "reason": "brief reason why valid/invalid"
}
```

### Grade Validation Prompt

```
You are a grade validator for an Indian school admission chatbot.

User Input: Grade 22, CBSE

Task: Extract and validate the grade/class from the input.

Valid Grades: Nursery, PP1, PP2, Grade 1-12

Examples:
- Valid: "Grade 5, CBSE", "Nursery", "Class 8"
- Invalid: "Grade 22", "Class 0", "Grade 13"

Respond ONLY in this JSON format (no markdown, no extra text):
{
  "valid": true or false,
  "grade": "extracted grade or null",
  "grade_number": 1-12 or null,
  "reason": "brief reason"
}
```

## Troubleshooting

### Connection Test Fails

**Problem**: "Claude API error: Invalid API key"

**Solution**:
1. Verify API key is correct (copy from console)
2. Check API key has not expired
3. Ensure API key has proper permissions
4. Check your account has available credits

### Rate Limit Exceeded

**Problem**: "Rate limit exceeded"

**Solution**:
1. Reduce `use_as_fallback` - don't use for everything
2. Increase `cache_ttl` - cache results longer
3. Increase `rate_limit` setting - allow more calls
4. Check Plan: Free tier = 100 requests/month

### Slow Responses

**Problem**: Chatbot pauses waiting for AI

**Solution**:
1. Lower `temperature` (default 0.3 is optimal)
2. Lower `max_tokens` (500 is usually sufficient)
3. Set timeout to 5-10 seconds
4. Add async validation queue (advanced)

### No AI Results Showing

**Problem**: AI validator not being called

**Check**:
1. Is "Enable AI Validation" ✓ checked?
2. Is "Use AI as Fallback" ✓ checked?
3. Is API key configured and valid?
4. Check plugin logs: `tail -f /var/log/php-error.log`

## Database Tables

### AI Validator Logs Table

```sql
CREATE TABLE wp_edubot_ai_validator_log (
    id BIGINT(20) PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50),           -- 'phone_validation', 'grade_validation'
    input TEXT,                 -- Original user input
    result LONGTEXT,            -- JSON result from AI
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_type (type),
    KEY idx_created (created_at)
);
```

View logs in: **EduBot Pro → AI Validator → Logs**

## Settings Reference

| Setting | Default | Range | Description |
|---------|---------|-------|-------------|
| Enable AI Validation | false | - | Master on/off |
| Provider | claude | claude, openai | Which API to use |
| Model | claude-3-5-sonnet | - | AI model |
| API Key | - | - | Secret API key |
| Temperature | 0.3 | 0.0-1.0 | Response consistency |
| Max Tokens | 500 | 100-2000 | Max response length |
| Timeout | 10 | 5-30 | Seconds to wait for API |
| Use as Fallback | true | - | Only use when regex fails |
| Cache Results | true | - | Cache validation results |
| Cache TTL | 3600 | - | Cache duration (seconds) |
| Rate Limit | 100 | 10-1000 | Requests per hour |
| Log AI Calls | true | - | Log all AI calls |

## Advanced Integration

### Custom AI Validation

```php
// Validate any field with AI
$validator = new EduBot_AI_Validator();

$phone_result = $validator->validate_phone($user_input);
if ($phone_result['valid']) {
    // Phone is valid
    $phone = $phone_result['number'];
}

$grade_result = $validator->validate_grade($user_input);
if ($grade_result['valid']) {
    // Grade is valid
    $grade = $grade_result['grade'];
}
```

### Conditional Validation

```php
// Use AI only in specific cases
if (strlen($user_input) > 50 || strpos($user_input, ' ') !== false) {
    // Complex input - try AI
    $ai_result = edubot_ai_validate_phone($user_input, $parsed);
} else {
    // Simple input - use regex
    $parsed = $this->parse_personal_info($user_input);
}
```

### Error Handling

```php
// AI can fail gracefully
$result = $edubot_ai_validator->validate_phone('some input');

if (isset($result['error'])) {
    // API call failed - fall back to regex
    error_log('AI validation failed: ' . $result['error']);
    $parsed = $this->parse_personal_info($user_input);
} else if (isset($result['valid'])) {
    // Use AI result
    // ...
}
```

## Testing

### Manual Test

1. Go to **EduBot Pro → AI Validator Settings**
2. Click **🧪 Test Connection**
3. Should show: "✅ Connection successful!"

### Test Cases

```
Phone Validation:
- Input: "986612sasad" → Invalid (contains letters)
- Input: "9866133566" → Valid
- Input: "98661" → Invalid (too short)
- Input: "+91 9876543210" → Valid

Grade Validation:
- Input: "Grade 22" → Invalid (no such grade)
- Input: "Grade 5, CBSE" → Valid
- Input: "Nursery" → Valid
- Input: "Class 13" → Invalid (max is 12)
```

### Check Logs

Go to: **EduBot Pro → AI Validator → Logs**

View recent AI validation calls and their results.

## Future Enhancements

Planned features:
- [ ] Batch validation (multiple inputs at once)
- [ ] Custom AI model training
- [ ] Webhook integration for async validation
- [ ] Cost analytics dashboard
- [ ] A/B testing regex vs AI accuracy
- [ ] Multi-language support

## Security Notes

1. **API Key**: Never commit to version control
2. **User Data**: AI API requests include user input
3. **Cache**: Validation results cached locally (not stored on AI servers)
4. **Rate Limiting**: Prevents runaway API costs
5. **Logging**: Enable only if needed for debugging

## Support

For issues or feature requests:
- Check logs: **EduBot Pro → AI Validator → Logs**
- Test connection: **EduBot Pro → AI Validator Settings → Test Connection**
- Review input/output: Enable logging in advanced settings
