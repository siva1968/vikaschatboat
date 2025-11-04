# EduBot Pro - Deployment & Production Guide

**Version:** 1.4.1  
**Last Updated:** November 5, 2025  
**Status:** Production Ready  
**Deployment Time:** 30-45 minutes

---

## 📋 Pre-Deployment Checklist

### Code & Database Verification

```
Pre-Deployment (Do 48 hours before):
☐ Code freeze - no new commits after this point
☐ All Phase 1-7 code tested locally
☐ All 66+ PHPUnit tests passing (100% pass rate)
☐ Database schema verified (5 tables created)
☐ All API credentials generated (4 platforms)
☐ SSL certificate valid and renewed (if expired soon)
☐ Domain DNS records updated
☐ SSL certificate installed on server
☐ PHP version verified (7.4+ or 8.0+)
☐ MySQL version verified (5.7+ or 8.0+)
☐ WordPress core updated (6.4+)
☐ Backup of production database scheduled
☐ Rollback procedure documented
```

### Team & Access Verification

```
Pre-Deployment (Do 24 hours before):
☐ Staging environment matches production
☐ Team trained on EduBot features
☐ Support team has documentation access
☐ Deployment window scheduled
☐ Communication plan prepared
☐ Rollback contacts identified
☐ Emergency contact list created
☐ Client/stakeholder notified
☐ Success criteria defined
```

### Security & Credentials

```
Pre-Deployment (Do 12 hours before):
☐ API credentials secured in vault/environment
☐ Passwords changed (not default)
☐ SSH keys rotated
☐ Database user permissions reviewed
☐ WordPress security plugins active
☐ Firewall rules configured
☐ Two-factor authentication enabled (admin)
☐ Backup encryption verified
☐ Security headers configured
```

---

## 🚀 Deployment Procedure

### Phase 1: Staging Verification (30 minutes)

**Step 1: Deploy to Staging**

```bash
# 1. Connect to staging server
ssh deploy@staging.yourdomain.com

# 2. Navigate to WordPress directory
cd /var/www/staging/

# 3. Clone/update EduBot Pro plugin
git clone https://github.com/yourusername/edubot-pro.git wp-content/plugins/edubot-pro

# 4. Set correct permissions
chmod -R 755 wp-content/plugins/edubot-pro
chown -R www-data:www-data wp-content/plugins/edubot-pro

# 5. Activate plugin via WP-CLI
wp plugin activate edubot-pro

# 6. Run database migrations
wp edubot migrate

# 7. Verify installation
wp plugin list | grep edubot-pro
wp db query "SELECT * FROM information_schema.tables WHERE table_schema = 'staging_db' AND table_name LIKE 'wp_edubot%'"
```

**Step 2: Run Full Test Suite**

```bash
# Run all PHPUnit tests
cd wp-content/plugins/edubot-pro
./vendor/bin/phpunit

# Expected: All 66+ tests passing

# Check code coverage
./vendor/bin/phpunit --coverage-html coverage/

# Expected: 90%+ coverage maintained
```

**Step 3: Verify All Features**

```
Manual Testing Checklist:
☐ Dashboard loads without errors
☐ KPIs calculating correctly
☐ Sample test session created and tracked
☐ Sample test conversion created
☐ Attribution models all working (5 models)
☐ Test report generated and sent
☐ API connections to all 4 platforms testing successfully
☐ Admin widgets displaying data
☐ No PHP errors in debug log
☐ Page load time acceptable (< 3 seconds)
```

**Step 4: Load Test (if high-traffic site)**

```bash
# Simulate 100 concurrent users
ab -n 1000 -c 100 https://staging.yourdomain.com/wp-admin/

# Expected results:
# - 0 errors
# - Average response time < 500ms
# - 95th percentile < 1000ms
```

---

### Phase 2: Pre-Production Backup (15 minutes)

**Step 1: Full Database Backup**

```bash
# Create backup directory
mkdir -p /backups/edubot/$(date +%Y-%m-%d)

# Backup database
mysqldump -u wordpress_user -p wordpress_db > /backups/edubot/$(date +%Y-%m-%d)/db_backup_$(date +%H%M%S).sql

# Backup WordPress files
tar -czf /backups/edubot/$(date +%Y-%m-%d)/files_backup_$(date +%H%M%S).tar.gz /var/www/html/

# Verify backups
ls -lah /backups/edubot/$(date +%Y-%m-%d)/

# Test restore (in separate environment)
mysql wordpress_db_test < /backups/edubot/$(date +%Y-%m-%d)/db_backup_*.sql

# Expected: Restore completes without errors
```

**Step 2: Document Rollback Procedure**

```
Rollback Steps (if needed):
1. Stop web server: sudo systemctl stop nginx
2. Restore database: mysql wordpress_db < backup.sql
3. Restore files: tar -xzf files_backup.tar.gz -C /
4. Deactivate plugin: wp plugin deactivate edubot-pro
5. Clear cache: wp cache flush
6. Start web server: sudo systemctl start nginx
7. Verify: Check dashboard loads without errors

Estimated time: 5-10 minutes
```

---

### Phase 3: Production Deployment (30 minutes)

**Step 1: Deploy to Production**

```bash
# 1. Connect to production server
ssh deploy@yourdomain.com

# 2. Navigate to WordPress directory
cd /var/www/html

# 3. Enable maintenance mode
wp maintenance-mode activate
# Or create maintenance file:
touch wp-content/maintenance.php

# 4. Create pre-deployment backup
mysqldump -u root -p wordpress_db > wp-content/backups/pre_deployment_$(date +%Y%m%d_%H%M%S).sql

# 5. Deploy plugin
git clone https://github.com/yourusername/edubot-pro.git wp-content/plugins/edubot-pro
chmod -R 755 wp-content/plugins/edubot-pro
chown -R www-data:www-data wp-content/plugins/edubot-pro

# 6. Install dependencies (if any)
cd wp-content/plugins/edubot-pro
composer install --no-dev --optimize-autoloader

# 7. Activate plugin
wp plugin activate edubot-pro

# 8. Run database migrations
wp edubot migrate

# 9. Warm up cache
wp cache flush
wp cache warm
```

**Step 2: Verify Production Deployment**

```bash
# Check plugin activated
wp plugin list | grep edubot-pro

# Verify database tables
wp db query "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'wordpress_db' AND table_name LIKE 'wp_edubot%'"
# Expected output: table_count = 5

# Check for PHP errors
tail -50 /var/log/php-fpm/error.log
tail -50 /var/www/html/wp-content/debug.log

# Test admin dashboard
wp eval "echo home_url('/wp-admin/admin.php?page=edubot-dashboard')"

# Disable maintenance mode
rm wp-content/maintenance.php
wp maintenance-mode deactivate
```

**Step 3: Post-Deployment Health Check**

```
Production Verification (First 2 hours):
☐ Admin dashboard loads without 404 or 500 errors
☐ KPI widgets display correctly
☐ Sample session tracking works
☐ Sample conversion tracking works
☐ First report sent successfully (if configured)
☐ API connections testing green
☐ No PHP errors in debug log
☐ Database queries performing well
☐ Page load time < 2 seconds
☐ Mobile dashboard responsive
```

---

## 📊 Monitoring & Verification

### Real-Time Monitoring (First 24 hours)

**Performance Metrics to Watch:**

```
PHP Execution Time:
- Alert if: > 3 seconds
- Action: Check logs for slow queries
- Fix: Enable caching, optimize database

Database Query Time:
- Alert if: > 500ms per query
- Action: Check slow query log
- Fix: Add indexes, enable caching

Memory Usage:
- Alert if: > 256MB
- Action: Check active processes
- Fix: Disable unused features, increase PHP memory

API Response Time:
- Alert if: > 2 seconds to ad platforms
- Action: Check network connectivity
- Fix: Increase timeout, retry logic

Error Rate:
- Alert if: Any 500 errors
- Action: Check error log immediately
- Fix: Rollback if critical
```

**Monitoring Commands:**

```bash
# Watch real-time log for errors
tail -f /var/www/html/wp-content/debug.log

# Check MySQL slow query log
tail -50 /var/log/mysql/slow-query.log

# Monitor system resources
top -u www-data

# Check disk space
df -h

# Monitor network traffic to APIs
tcpdump -i eth0 dst api.facebook.com or dst googleads.google.com

# Check WordPress cron jobs
wp cron event list

# Verify plugin is working
wp eval 'echo EduBot_Logger::get_instance()->log_info("Deployment verification", ["status" => "live"])'
```

---

## 🔧 Performance Tuning (Post-Deployment)

### Database Optimization

```sql
-- Verify indexes exist
SELECT * FROM information_schema.statistics 
WHERE table_schema = 'wordpress_db' 
AND table_name LIKE 'wp_edubot%'
ORDER BY seq_in_index;

-- Optimize all EduBot tables
OPTIMIZE TABLE wp_edubot_attribution_sessions;
OPTIMIZE TABLE wp_edubot_conversions;
OPTIMIZE TABLE wp_edubot_attributions;
OPTIMIZE TABLE wp_edubot_report_schedules;
OPTIMIZE TABLE wp_edubot_logs;

-- Analyze table statistics
ANALYZE TABLE wp_edubot_attribution_sessions;
ANALYZE TABLE wp_edubot_conversions;

-- Check query performance
EXPLAIN SELECT * FROM wp_edubot_conversions 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY created_at DESC LIMIT 100;
-- Expected: Uses indexes, efficient execution
```

### Enable Caching

```php
// In wp-config.php, add:
define('WP_CACHE', true);
update_option('edubot_enable_caching', true);
update_option('edubot_cache_duration', 300); // 5 minutes

// For very high traffic, use Redis:
// wp plugin install redis-cache --activate
define('WP_REDIS_HOST', '127.0.0.1');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_PASSWORD', 'your_password');
```

### Query Logging

```php
// In wp-config.php, for monitoring:
define('SAVEQUERIES', true);
define('EDUBOT_DEBUG', true);

// Check slowest queries
global $wpdb;
usort($wpdb->queries, function($a, $b) {
    return $b[1] - $a[1]; // Sort by execution time
});
foreach (array_slice($wpdb->queries, 0, 10) as $query) {
    echo $query[2] . " - " . $query[1] . " seconds\n";
}
```

---

## 🔐 Security Hardening (Post-Deployment)

### WordPress Security

```bash
# 1. Remove default admin user (if exists)
wp user list | grep admin
wp user delete 1 --reassign=2

# 2. Set secure password policy
wp config set WP_PASS_MIN_LENGTH 16

# 3. Disable file editing
wp config set DISALLOW_FILE_EDIT true

# 4. Disable plugin/theme editing
wp config set DISALLOW_FILE_MODS true

# 5. Update all WordPress core, plugins, themes
wp core update
wp plugin update --all
wp theme update --all

# 6. Remove unnecessary plugins
wp plugin list
# Keep only: EduBot Pro, security plugin, backup plugin

# 7. Set up security headers
# In .htaccess or nginx config:
# X-Frame-Options: DENY
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block
```

### EduBot-Specific Security

```php
// Verify security settings
$security_settings = [
    'hash_algorithm' => get_option('edubot_hash_algorithm'), // Should be 'sha256'
    'nonce_verification' => true,
    'capability_checks' => true,
    'input_sanitization' => true,
    'output_escaping' => true,
    'sql_injection_prevention' => true // Using $wpdb->prepare()
];

// Rotate API credentials immediately after deployment
update_option('edubot_facebook_access_token', 'new_token');
update_option('edubot_google_refresh_token', 'new_token');
update_option('edubot_tiktok_access_token', 'new_token');
update_option('edubot_linkedin_access_token', 'new_token');

// Verify PII hashing
$test_email = 'test@example.com';
$hashed = hash('sha256', $test_email);
// Should produce 40-character hex string
```

---

## 📈 Production Readiness Checklist

### Before Going Live

```
24 Hours Before Launch:
☐ Staging deployment successful
☐ All tests passing
☐ Performance benchmarks met
☐ Team trained and ready
☐ Rollback plan documented
☐ Communication prepared

2 Hours Before Launch:
☐ Production database backed up
☐ Current production state documented
☐ Deployment window confirmed
☐ All systems verified
☐ Status page prepared

During Launch:
☐ Enable maintenance mode
☐ Monitor logs continuously
☐ Verify each deployment step
☐ Test critical features
☐ Disable maintenance mode
☐ Announce deployment complete

After Launch (2 hours):
☐ Monitor error logs
☐ Check performance metrics
☐ Verify API connections
☐ Test all user flows
☐ Monitor conversion tracking
☐ Verify email reports

After Launch (24 hours):
☐ Review all logs for errors
☐ Check performance trends
☐ Verify data accuracy
☐ Monitor API response times
☐ Ensure cron jobs running
☐ Backup completed successfully
```

---

## 🎯 Success Criteria

### Deployment is Successful When:

```
Functionality:
✅ Dashboard loads without errors
✅ All KPIs displaying correctly
✅ Sample conversion tracking working
✅ Email reports sending on schedule
✅ All 4 API platforms connected
✅ Attribution models calculating

Performance:
✅ Dashboard load time < 2 seconds
✅ Average API response time < 500ms
✅ Database queries < 200ms average
✅ No PHP timeout errors
✅ CPU usage normal
✅ Memory usage < 256MB

Reliability:
✅ 0 critical errors in logs
✅ 0 API connection failures
✅ 0 database connection failures
✅ 100% uptime (0 downtime events)
✅ All backups completed
✅ All cron jobs running

Security:
✅ SSL certificate valid
✅ No SQL injection attempts
✅ No unauthorized access attempts
✅ PII being hashed correctly
✅ API credentials secure
✅ All security headers present

Monitoring:
✅ Error monitoring active
✅ Performance monitoring active
✅ Uptime monitoring active
✅ Alert system functional
✅ Logging captured
✅ Backups scheduled
```

---

## 📞 Operational Runbooks

### Daily Operations

**Morning Checklist (First thing):**

```bash
# 1. Check system health
top -b -n 1 | head -20
df -h | grep -E '^/dev'

# 2. Review error logs
tail -100 /var/www/html/wp-content/debug.log | grep -i error

# 3. Check API status
wp eval 'echo "API Status: OK"'

# 4. Verify cron jobs ran
wp cron event list | grep edubot

# 5. Check database size
mysql -e "SELECT table_name, ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB' FROM information_schema.TABLES WHERE table_schema = 'wordpress_db' AND table_name LIKE 'wp_edubot%' ORDER BY data_length DESC;"
```

### Weekly Maintenance

**Monday Routine:**

```bash
# 1. Database maintenance
mysql -u root -p wordpress_db < maintenance.sql

# 2. Log rotation
logrotate -f /etc/logrotate.d/wordpress

# 3. Backup verification
# Test restore in test environment
mysql wordpress_db_test < /backups/latest_backup.sql

# 4. Performance review
# Review slow query log
# Check average response times

# 5. Security scan
# Run WordPress security plugins
wp plugin run-security-check
```

### Monthly Maintenance

**First of Month:**

```bash
# 1. Full system backup
# Already scheduled, verify completion

# 2. WordPress updates
wp core update
wp plugin update --all

# 3. Database cleanup
mysql -e "DELETE FROM wp_edubot_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);"

# 4. Certificate renewal check
# Verify SSL certificate expiry
openssl x509 -enddate -noout -in /etc/ssl/certs/yourdomain.com.crt

# 5. Performance analysis
# Generate monthly performance report
# Review trends, identify issues

# 6. Capacity planning
# Check disk space usage trend
# Verify database growth rate
```

---

## 🚨 Incident Response

### If Errors Occur During Deployment

**Scenario 1: PHP Fatal Error**

```
Symptoms: 500 error, white screen
Steps:
1. Check debug log: tail -50 /var/www/html/wp-content/debug.log
2. Identify error line
3. Fix code or disable plugin
4. Clear PHP opcode cache: php -r "opcache_reset();"
5. Verify: Test dashboard
6. If persists: Rollback to previous version
```

**Scenario 2: Database Connection Error**

```
Symptoms: "Error establishing database connection"
Steps:
1. Verify MySQL running: systemctl status mysql
2. Check credentials: grep DB_NAME wp-config.php
3. Test connection: mysql -u user -p database_name
4. Check file permissions: chmod 755 wp-config.php
5. If still failing: Restore from backup
```

**Scenario 3: API Connection Failures**

```
Symptoms: "API connection failed" in logs
Steps:
1. Test connectivity: curl -I https://api.facebook.com
2. Verify credentials: grep edubot_facebook /var/www/html/wp-content/options.php
3. Check firewall: iptables -L | grep 443
4. Verify SSL cert: openssl s_client -connect api.facebook.com:443
5. If still failing: Disable send_to_platforms setting temporarily
```

### Emergency Rollback Procedure

```bash
# If deployment is catastrophic and must be rolled back:

# 1. Enable maintenance mode
touch /var/www/html/wp-content/maintenance.php

# 2. Deactivate plugin
wp plugin deactivate edubot-pro

# 3. Restore database backup
mysql wordpress_db < /backups/pre_deployment_backup.sql

# 4. Clear caches
wp cache flush
php -r "opcache_reset();"

# 5. Restore previous plugin version
rm -rf /var/www/html/wp-content/plugins/edubot-pro
git clone --branch v1.4.0 https://github.com/yourusername/edubot-pro.git /var/www/html/wp-content/plugins/edubot-pro

# 6. Reactivate plugin
wp plugin activate edubot-pro

# 7. Disable maintenance mode
rm /var/www/html/wp-content/maintenance.php

# 8. Verify
wp plugin list | grep edubot-pro
curl -s https://yourdomain.com/wp-admin/admin.php?page=edubot-dashboard | grep -q "EduBot" && echo "OK" || echo "ERROR"

# Total rollback time: 5-10 minutes
```

---

## 📊 Post-Launch Reporting

### Day 1 Report

```
Subject: EduBot Pro - Day 1 Deployment Report

Deployment Summary:
✅ Deployment Date: [Date]
✅ Deployment Time: [Start] - [End] (Duration: XX minutes)
✅ Status: Successful

System Health:
- Dashboard Load Time: XX ms (Target: < 2s)
- API Response Time: XX ms (Target: < 500ms)
- Database Performance: XX queries/sec
- Error Rate: XX (Target: 0)
- Uptime: 100%

Features Verified:
✅ Dashboard KPIs
✅ Session Tracking
✅ Conversion Tracking
✅ Attribution Calculation
✅ Email Reports
✅ API Connections

Issues Encountered:
- None / [List any issues and resolutions]

Next Steps:
- Monitor performance for 7 days
- Collect feedback from team
- Optimize based on real usage
```

### Week 1 Report

```
Metrics:
- Total Sessions Tracked: XXX
- Total Conversions: XX
- Conversion Rate: X%
- Average Session Duration: X min
- API Success Rate: 99.X%
- Database Size: X MB

Top Performing Channel:
- Channel: [Channel Name]
- Conversions: XX
- Conversion Rate: X%

Issues & Resolutions:
- [Issue 1]: [Resolution]
- [Issue 2]: [Resolution]

Optimizations Implemented:
- [Optimization 1]
- [Optimization 2]

Recommendations:
- [Recommendation 1]
- [Recommendation 2]
```

---

## 🔄 Maintenance Schedule

### Daily Tasks

```
05:00 AM: Automatic backup
06:00 AM: First error log check
12:00 PM: Mid-day health check
06:00 PM: End-of-day review
11:00 PM: Pre-sleep health check
```

### Weekly Tasks

```
Monday 09:00 AM: Full system review
Wednesday 14:00: Performance analysis
Friday 16:00: Security audit
Sunday 22:00: Backup verification
```

### Monthly Tasks

```
1st: Database maintenance & cleanup
7th: Performance trending analysis
14th: Security patches & updates
28th: Capacity planning review
```

---

## 📚 Related Documentation

- [API Reference](./API_REFERENCE.md) - Technical reference
- [Setup Guide](./SETUP_GUIDE.md) - Initial setup
- [Configuration Guide](./CONFIGURATION_GUIDE.md) - Settings reference
- [Troubleshooting Guide](./TROUBLESHOOTING_GUIDE.md) - Common issues
- [Best Practices](./BEST_PRACTICES.md) - Optimization strategies

