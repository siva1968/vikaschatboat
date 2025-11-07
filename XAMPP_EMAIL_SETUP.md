# XAMPP Email Setup Guide

## Problem
Notifications are configured but emails are not sending because XAMPP doesn't have a built-in mail server.

## Current Status
✅ Notification settings configured:
- Email Provider: WordPress
- Admin Email: prasadmasina@gmail.com
- Admin Phone: +917702800800
- Email Enabled: Yes
- Admin Notifications: Yes

❌ Email delivery failing:
- XAMPP has no mail server
- WordPress `wp_mail()` can't send emails

## Solutions

### Option 1: WP Mail SMTP Plugin (Easiest)

**Install WP Mail SMTP Plugin:**

1. **Download:** https://wordpress.org/plugins/wp-mail-smtp/
2. **Install:**
   - Go to: WordPress Admin → Plugins → Add New
   - Search: "WP Mail SMTP"
   - Install and Activate

3. **Configure with Gmail:**
   - Go to: Settings → WP Mail SMTP
   - From Email: prasadmasina@gmail.com
   - From Name: Epistemo Vikas Leadership School
   - Mailer: Gmail (or Other SMTP)

4. **Gmail SMTP Settings:**
   - SMTP Host: smtp.gmail.com
   - SMTP Port: 587
   - Encryption: TLS
   - Username: prasadmasina@gmail.com
   - Password: [App Password - see below]

5. **Create Gmail App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Select: Mail
   - Device: WordPress
   - Copy the generated password
   - Paste in WP Mail SMTP settings

6. **Test:**
   - Click "Send Test Email"
   - Check inbox

### Option 2: Configure XAMPP sendmail

**1. Edit `php.ini`:**

File: `D:\xampp\php\php.ini`

Find and update:
```ini
[mail function]
SMTP=smtp.gmail.com
smtp_port=587
sendmail_from=prasadmasina@gmail.com
sendmail_path="\"D:\xampp\sendmail\sendmail.exe\" -t"
```

**2. Edit `sendmail.ini`:**

File: `D:\xampp\sendmail\sendmail.ini`

Update:
```ini
[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
auth_username=prasadmasina@gmail.com
auth_password=YOUR_APP_PASSWORD_HERE
force_sender=prasadmasina@gmail.com
```

**3. Restart Apache:**
```bash
# Stop and start Apache in XAMPP Control Panel
```

**4. Test:**
```bash
php configure_notifications.php
```

### Option 3: Use ZeptoMail (Professional)

**Setup ZeptoMail:**

1. **Sign up:** https://www.zeptomail.com/
2. **Get API Token**
3. **Configure in EduBot:**
   - Go to: WordPress Admin → EduBot Pro → API Settings
   - Email Provider: ZeptoMail
   - ZeptoMail Token: [Your token]
   - Save

4. **Re-run configuration:**
```bash
php configure_notifications.php
```

### Option 4: Use Mailtrap (Testing Only)

For development testing:

1. **Sign up:** https://mailtrap.io/
2. **Get SMTP credentials**
3. **Configure in WP Mail SMTP:**
   - Host: smtp.mailtrap.io
   - Port: 2525
   - Username: [from mailtrap]
   - Password: [from mailtrap]

All test emails will be caught by Mailtrap (won't reach real inboxes).

## Quick Test After Setup

Run this to test:
```bash
php configure_notifications.php
```

Should show:
```
✅ Test email sent successfully!
Check inbox: prasadmasina@gmail.com
```

## Verify Notifications Working

**Method 1: Check Current Settings**
```bash
php check_notifications.php
```

**Method 2: Submit Test Enquiry**
1. Go to chatbot
2. Start admission enquiry
3. Complete the form
4. Check email inbox

**Method 3: Manual Test**
```php
<?php
require_once('D:/xampp/htdocs/demo/wp-load.php');
wp_mail('prasadmasina@gmail.com', 'Test', 'Testing notifications');
?>
```

## Troubleshooting

### Issue: "Failed to send test email"
**Solutions:**
- Install WP Mail SMTP plugin
- Configure Gmail SMTP settings
- Use App Password (not regular password)
- Check firewall/antivirus blocking port 587

### Issue: Gmail blocking login
**Solutions:**
- Use App Password instead of regular password
- Enable 2-Step Verification first
- Generate App Password: https://myaccount.google.com/apppasswords

### Issue: Emails going to spam
**Solutions:**
- Use ZeptoMail or professional SMTP
- Add SPF/DKIM records to domain
- Use "From" email matching domain

### Issue: Port 25/587 blocked
**Solutions:**
- Use port 465 (SSL) instead
- Check firewall settings
- Try alternative SMTP port 2525

## Recommended Solution

**For Development (XAMPP):**
- Use **WP Mail SMTP Plugin** with Gmail
- Quick setup, reliable, free

**For Production:**
- Use **ZeptoMail** or **SendGrid**
- Professional, reliable, analytics
- Better deliverability

## Next Steps

1. **Choose a solution** from above
2. **Configure SMTP** settings
3. **Test email** delivery
4. **Submit test enquiry** through chatbot
5. **Verify notification** received

## WhatsApp Notifications

Once email is working, configure WhatsApp:

1. **Sign up:** https://www.interakt.shop/
2. **Get API key**
3. **Configure in EduBot:**
   - WhatsApp Provider: Interakt
   - API Key: [Your key]
   - Enable WhatsApp: Yes
4. **Test WhatsApp** delivery

---

**Current Status:**
- ✅ Notification system configured
- ✅ Admin email set
- ❌ Email delivery not working (need SMTP)
- ❌ WhatsApp not configured (need API key)

**Next Action:** Install WP Mail SMTP plugin and configure Gmail SMTP
