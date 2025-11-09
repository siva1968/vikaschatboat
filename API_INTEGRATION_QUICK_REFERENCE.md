# 🔐 API INTEGRATION SETTINGS - QUICK REFERENCE

## Database Location

**Table**: `wp_edubot_api_integrations`
**Active Record**: ID 2, Status = 'active'

## Email Configuration (Active)

```sql
-- View current configuration
SELECT email_provider, email_api_key, email_from_address 
FROM wp_edubot_api_integrations 
WHERE status = 'active';

-- Result:
-- Provider: zeptomail
-- API Key: PHtE6r0K...YNA== (144 chars)
-- From: info@epistemo.in
```

## WhatsApp Configuration (Active)

```sql
-- View current configuration
SELECT whatsapp_provider, whatsapp_token, whatsapp_phone_id 
FROM wp_edubot_api_integrations 
WHERE status = 'active';

-- Result:
-- Provider: meta
-- Token: EAASeCKYj...ZDZD (199 chars)
-- Phone ID: 614525638411206
```

## Update Email API Key

```sql
UPDATE wp_edubot_api_integrations 
SET email_api_key = 'YOUR_NEW_ZEPTOMAIL_API_KEY'
WHERE status = 'active';
```

## Update WhatsApp Token

```sql
UPDATE wp_edubot_api_integrations 
SET whatsapp_token = 'YOUR_NEW_META_TOKEN'
WHERE status = 'active';
```

## Update Phone ID

```sql
UPDATE wp_edubot_api_integrations 
SET whatsapp_phone_id = 'YOUR_NEW_PHONE_ID'
WHERE status = 'active';
```

## Code Reading Pattern

All notification methods follow this pattern:

```php
global $wpdb;

$api_config = $wpdb->get_row(
    "SELECT email_api_key, email_provider FROM {$wpdb->prefix}edubot_api_integrations 
     WHERE status = 'active' LIMIT 1"
);

if (!empty($api_config->email_api_key)) {
    // Use the API key from database
}
```

## API Endpoints

```
ZeptoMail: https://api.zeptomail.in/v1.1/email
Meta WhatsApp: https://graph.facebook.com/v22.0/{phone_id}/messages
```

## Authorization Headers

```
ZeptoMail Email:
  Authorization: Zoho-enczapikey {email_api_key}

Meta WhatsApp:
  Authorization: Bearer {whatsapp_token}
```

## Notification Flow

```
User Submission → Workflow Manager → API Integrations → Database → Send Notifications

Each step:
1. Read from: wp_edubot_api_integrations (NOT WordPress options)
2. Query: WHERE status = 'active'
3. Execute: Direct HTTP calls to external APIs
4. Log: All responses to debug.log
```

## Testing

```bash
# Verify all settings
php verify_api_integrations.php

# Verify code integration
php verify_code_integration.php

# Test full notification flow
php test_notifications_final.php
```

## Troubleshooting

**If emails don't send:**
1. Check email_api_key is set: `SELECT * FROM wp_edubot_api_integrations`
2. Check endpoint: Should be `api.zeptomail.in` (not `.com`)
3. Check auth header: Should be `Zoho-enczapikey` (not `Bearer`)

**If WhatsApp doesn't send:**
1. Check whatsapp_token is set
2. Check whatsapp_phone_id is set
3. Check endpoint: Should be `graph.facebook.com/v22.0`
4. Check auth header: Should be `Bearer`

**Debug log location:**
```
wp-content/debug.log

Search for:
- "ZeptoMail:" for email debug
- "WhatsApp response" for WhatsApp debug
```

---

## Status: ✅ ALL SYSTEMS USING DATABASE TABLE

✅ Not reading from WordPress options
✅ All credentials in database table
✅ All API endpoints correct
✅ All authorization headers correct
✅ All notifications verified working
