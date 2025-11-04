# EduBot Pro - Operational Runbook

**Version:** 1.4.1  
**Last Updated:** November 5, 2025  
**Audience:** DevOps, System Administrators, Operations Team

---

## 📅 Daily Operations

### Morning Startup (05:00 AM)

**Duration:** 10 minutes  
**Owner:** Operations Team

**Checklist:**

```bash
#!/bin/bash
# Daily startup verification script

echo "=== DAILY STARTUP CHECK ==="
echo "Time: $(date)"

# 1. System Health
echo ""
echo "1. Checking system resources..."
cpu_usage=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
memory_usage=$(free | grep Mem | awk '{print ($3/$2) * 100.0}')
disk_usage=$(df -h / | awk 'NR==2 {print $5}')

echo "   CPU Usage: ${cpu_usage}% (Alert: > 80%)"
echo "   Memory Usage: ${memory_usage}% (Alert: > 85%)"
echo "   Disk Usage: ${disk_usage} (Alert: > 90%)"

if (( $(echo "$cpu_usage > 80" | bc -l) )); then
    echo "   ⚠️  WARNING: High CPU usage"
fi

# 2. Services Status
echo ""
echo "2. Checking critical services..."
systemctl is-active --quiet nginx && echo "   ✅ Nginx: RUNNING" || echo "   ❌ Nginx: STOPPED"
systemctl is-active --quiet mysql && echo "   ✅ MySQL: RUNNING" || echo "   ❌ MySQL: STOPPED"
systemctl is-active --quiet php-fpm && echo "   ✅ PHP-FPM: RUNNING" || echo "   ❌ PHP-FPM: STOPPED"

# 3. Error Logs
echo ""
echo "3. Checking error logs..."
error_count=$(grep -c ERROR /var/www/html/wp-content/debug.log 2>/dev/null || echo "0")
echo "   Errors in last 12 hours: ${error_count}"

if [ $error_count -gt 10 ]; then
    echo "   ⚠️  WARNING: High error count"
    echo "   Recent errors:"
    tail -5 /var/www/html/wp-content/debug.log | grep ERROR
fi

# 4. Database Status
echo ""
echo "4. Checking database..."
mysql -u root -p"$DB_PASS" -e "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'wordpress_db' AND table_name LIKE 'wp_edubot%';" 2>/dev/null || echo "   ❌ Database check failed"
echo "   ✅ Database: ACCESSIBLE"

# 5. SSL Certificate
echo ""
echo "5. Checking SSL certificate..."
cert_expire=$(openssl x509 -enddate -noout -in /etc/ssl/certs/yourdomain.com.crt 2>/dev/null | cut -d= -f2)
cert_days=$(($(date -d "$cert_expire" +%s) - $(date +%s))) / 86400)
echo "   Certificate expires: ${cert_expire}"
echo "   Days remaining: ${cert_days}"

if [ $cert_days -lt 30 ]; then
    echo "   ⚠️  WARNING: Certificate expires soon"
fi

# 6. Cron Jobs
echo ""
echo "6. Checking cron jobs..."
wp cron event list --fields=hook,next_run --format=table

echo ""
echo "=== STARTUP CHECK COMPLETE ==="
echo "Status: All systems operational"
```

**Manual Verification:**

```
☐ Web server responding: curl -I https://yourdomain.com
☐ WordPress admin accessible: https://yourdomain.com/wp-admin
☐ Dashboard loading: https://yourdomain.com/wp-admin/admin.php?page=edubot-dashboard
☐ No critical errors in logs
☐ Database backup completed overnight
☐ All API connections green
```

---

### Mid-Day Check (12:00 PM)

**Duration:** 5 minutes  
**Owner:** Operations Team

```bash
# Quick health check
#!/bin/bash

echo "=== MID-DAY HEALTH CHECK ==="

# 1. Response time test
response_time=$(curl -o /dev/null -s -w '%{time_total}' https://yourdomain.com/wp-admin/admin.php?page=edubot-dashboard)
echo "Dashboard response time: ${response_time}s (Target: < 2s)"

if (( $(echo "$response_time > 2" | bc -l) )); then
    echo "⚠️  WARNING: Slow response time"
fi

# 2. API status
curl -s https://yourdomain.com/wp-admin/admin.php?page=edubot-api-settings | grep -q "Connection: OK" && echo "✅ APIs: Connected" || echo "⚠️  APIs: Check status"

# 3. Recent errors
errors=$(grep -c "ERROR" /var/www/html/wp-content/debug.log)
echo "Errors since last check: ${errors}"

# 4. Database size
size=$(du -sh /var/lib/mysql/wordpress_db | awk '{print $1}')
echo "Database size: ${size}"

echo "=== CHECK COMPLETE ==="
```

---

### End-of-Day Review (18:00)

**Duration:** 15 minutes  
**Owner:** Operations Team

**Procedure:**

1. **Performance Summary**
   ```bash
   # Export daily metrics
   wp eval '
   $dashboard = new EduBot_Admin_Dashboard();
   $kpis = $dashboard->get_kpis("week");
   echo "KPIs Summary: ";
   print_r($kpis);
   '
   ```

2. **Error Log Summary**
   ```bash
   # Count errors by type
   grep ERROR /var/www/html/wp-content/debug.log | \
   awk -F: '{print $2}' | sort | uniq -c | sort -rn
   ```

3. **Backup Verification**
   ```bash
   # Check backup completed
   backup_file=$(ls -lt /backups/ | head -1 | awk '{print $NF}')
   backup_size=$(du -sh /backups/$backup_file | awk '{print $1}')
   echo "Latest backup: $backup_file ($backup_size)"
   ```

4. **Tomorrow's Tasks**
   ```
   ☐ Review any pending maintenance
   ☐ Check if API token renewals needed
   ☐ Review upcoming scheduled tasks
   ☐ Prepare for any planned maintenance
   ```

---

## 📊 Weekly Operations

### Monday Morning Review (09:00 AM)

**Duration:** 30 minutes  
**Owner:** DevOps Lead + Operations Team

**Procedure:**

1. **Performance Analysis**

   ```bash
   #!/bin/bash
   # Generate weekly performance report
   
   echo "=== WEEKLY PERFORMANCE REPORT ==="
   echo "Week of: $(date +%Y-%m-%d)"
   
   # 1. Average response times
   echo ""
   echo "1. Performance Metrics:"
   grep "response_time" /var/log/application.log | \
   awk '{sum += $NF; count++} END {print "   Average Response: " sum/count "ms"}'
   
   # 2. Database statistics
   echo ""
   echo "2. Database Statistics:"
   mysql -u root -p"$DB_PASS" -e "
   SELECT table_name, 
          ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
   FROM information_schema.TABLES 
   WHERE table_schema = 'wordpress_db' 
   AND table_name LIKE 'wp_edubot%'
   ORDER BY data_length DESC;"
   
   # 3. Error summary
   echo ""
   echo "3. Error Summary:"
   grep "$(date -d '7 days ago' +%Y-%m-%d)" /var/www/html/wp-content/debug.log | \
   grep -i error | wc -l | xargs echo "   Total Errors:"
   
   # 4. Uptime
   echo ""
   echo "4. Uptime:"
   uptime -p
   
   echo ""
   echo "=== END REPORT ==="
   ```

2. **Review Issues from Past Week**
   - Check support tickets
   - Review error logs for patterns
   - Identify any recurring issues
   - Create action items

3. **Planning Next Week**
   - Identify any maintenance needed
   - Schedule non-critical updates
   - Review capacity trends
   - Plan optimizations

---

### Wednesday Performance Analysis (14:00)

**Duration:** 20 minutes  
**Owner:** Performance Engineer

**Analyze:**

```bash
# Query slow queries
mysql -u root -p"$DB_PASS" -e "
SELECT query_time, lock_time, rows_examined, rows_sent, sql_text
FROM mysql.slow_log
WHERE start_time > DATE_SUB(NOW(), INTERVAL 3 DAY)
ORDER BY query_time DESC
LIMIT 20;"

# Check API performance
grep "api_response_time" /var/log/application.log | \
awk '{sum += $NF; count++} END {print "Average API Response: " sum/count "ms"}'
```

**Actions:**

- [ ] Identify slow queries
- [ ] Add missing indexes if needed
- [ ] Review caching effectiveness
- [ ] Document findings
- [ ] Create optimization tickets

---

### Friday Security Audit (16:00)

**Duration:** 25 minutes  
**Owner:** Security Officer

**Checklist:**

```bash
#!/bin/bash
# Weekly security audit

echo "=== SECURITY AUDIT ==="

# 1. File integrity
echo "1. Checking file integrity..."
find /var/www/html/wp-content/plugins/edubot-pro -type f -name "*.php" | \
while read file; do
    md5=$(md5sum "$file" | awk '{print $1}')
    echo "$file:$md5" >> /tmp/current_hashes.txt
done

# 2. Permission check
echo "2. Checking file permissions..."
find /var/www/html/wp-content/plugins/edubot-pro -type f -perm /077 && \
echo "⚠️  WARNING: Unsafe permissions found"

# 3. Credential check
echo "3. Checking for exposed credentials..."
grep -r "edubot_.*_token\|api_key\|secret" /var/www/html/wp-content/plugins/edubot-pro | \
grep -v "get_option\|update_option" && echo "⚠️  WARNING: Hardcoded credentials"

# 4. SSL certificate
echo "4. Checking SSL status..."
ssl_days=$(($(date -d "$(openssl x509 -enddate -noout -in /etc/ssl/certs/yourdomain.com.crt | cut -d= -f2)" +%s) - $(date +%s)) / 86400)
echo "   Certificate valid for: ${ssl_days} more days"

if [ $ssl_days -lt 30 ]; then
    echo "   ⚠️  Certificate renewal needed"
fi

# 5. Failed login attempts
echo "5. Checking for suspicious activity..."
grep "failed login" /var/log/auth.log | tail -10 | \
awk '{print $NF}' | sort | uniq -c | sort -rn | head -5

echo ""
echo "=== AUDIT COMPLETE ==="
```

---

### Sunday Backup Verification (22:00)

**Duration:** 20 minutes  
**Owner:** Database Administrator

**Procedure:**

```bash
#!/bin/bash
# Test backup restoration

echo "=== BACKUP VERIFICATION ==="

# 1. List backups
echo "1. Available backups:"
ls -lh /backups/edubot/$(date +%Y-%m-%d)/

# 2. Verify backup integrity
echo "2. Verifying backup integrity..."
backup_file=$(ls -t /backups/edubot/*/db_backup_*.sql | head -1)
mysql -u root -p"$DB_PASS" wordpress_db_test < "$backup_file" && \
echo "   ✅ Backup valid and restorable" || \
echo "   ❌ Backup restore failed"

# 3. Check backup size
echo "3. Backup size:"
du -sh /backups/edubot/*/

# 4. Verify encryption
echo "4. Checking backup encryption..."
file "$backup_file" | grep -q "gzip" && \
echo "   ✅ Backup compressed" || \
echo "   ⚠️  Backup not compressed"

# 5. Check off-site backup
echo "5. Checking off-site replication..."
# Check if backup replicated to S3/Cloud storage
aws s3 ls s3://yourbucket/backups/ | tail -1

echo ""
echo "=== VERIFICATION COMPLETE ==="
```

---

## 🔧 Monthly Maintenance

### Database Optimization (1st of Month)

**Duration:** 30 minutes  
**Owner:** Database Administrator

```bash
#!/bin/bash
# Monthly database maintenance

echo "=== MONTHLY DATABASE MAINTENANCE ==="

# 1. Analyze tables
echo "1. Analyzing table statistics..."
for table in wp_edubot_attribution_sessions wp_edubot_conversions wp_edubot_attributions wp_edubot_report_schedules wp_edubot_logs; do
    echo "   Analyzing $table..."
    mysql -u root -p"$DB_PASS" wordpress_db -e "ANALYZE TABLE $table;"
done

# 2. Optimize tables
echo ""
echo "2. Optimizing tables..."
for table in wp_edubot_attribution_sessions wp_edubot_conversions wp_edubot_attributions wp_edubot_report_schedules wp_edubot_logs; do
    echo "   Optimizing $table..."
    mysql -u root -p"$DB_PASS" wordpress_db -e "OPTIMIZE TABLE $table;"
done

# 3. Check for corruption
echo ""
echo "3. Checking for table corruption..."
mysql -u root -p"$DB_PASS" wordpress_db -e "CHECK TABLE wp_edubot_*;"

# 4. Cleanup old logs
echo ""
echo "4. Cleaning up old logs..."
mysql -u root -p"$DB_PASS" wordpress_db -e "
DELETE FROM wp_edubot_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
SELECT ROW_COUNT() as rows_deleted;"

# 5. Generate report
echo ""
echo "5. Post-maintenance table sizes:"
mysql -u root -p"$DB_PASS" -e "
SELECT table_name, 
       ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
FROM information_schema.TABLES 
WHERE table_schema = 'wordpress_db' 
AND table_name LIKE 'wp_edubot%'
ORDER BY data_length DESC;"

echo ""
echo "=== MAINTENANCE COMPLETE ==="
```

---

### Security Patching (7th of Month)

**Duration:** 45 minutes  
**Owner:** Security Officer

```bash
#!/bin/bash
# Monthly security updates

echo "=== MONTHLY SECURITY PATCHING ==="

# 1. Check WordPress updates
echo "1. Checking WordPress updates..."
wp core check-update

# 2. Update WordPress core
echo "2. Updating WordPress core..."
wp core update

# 3. Update plugins
echo "3. Updating plugins..."
wp plugin update --all

# 4. Update themes
echo "4. Updating themes..."
wp theme update --all

# 5. Verify SSL certificate
echo ""
echo "5. Verifying SSL certificate..."
openssl x509 -text -noout -in /etc/ssl/certs/yourdomain.com.crt | grep -E "Not Before|Not After"

# 6. Check system packages
echo ""
echo "6. Checking system packages..."
apt update
apt list --upgradable

# 7. Test everything still works
echo ""
echo "7. Post-patch verification..."
wp plugin list --status=active
wp core version
curl -I https://yourdomain.com

echo ""
echo "=== PATCHING COMPLETE ==="
```

---

### Performance Review (14th of Month)

**Duration:** 1 hour  
**Owner:** DevOps Lead

**Generate Report:**

```bash
#!/bin/bash
# Monthly performance report

echo "=== MONTHLY PERFORMANCE REPORT ==="
echo "Date: $(date +%Y-%m-%d)"

# 1. Performance Trends
echo ""
echo "1. PERFORMANCE TRENDS"
echo "   Dashboard Load Time:"
grep "dashboard_load" /var/log/performance.log | tail -30 | \
awk '{sum += $NF; count++} END {print "   Average: " sum/count "ms"}'

# 2. API Performance
echo ""
echo "2. API PERFORMANCE"
for api in facebook google tiktok linkedin; do
    grep "api_response_$api" /var/log/api.log | tail -100 | \
    awk '{sum += $NF; count++} END {print "   '$api': " sum/count "ms"}'
done

# 3. Database Statistics
echo ""
echo "3. DATABASE STATISTICS"
mysql -u root -p"$DB_PASS" -e "
SELECT 
    (SELECT COUNT(*) FROM wp_edubot_conversions) as total_conversions,
    (SELECT COUNT(*) FROM wp_edubot_attribution_sessions) as total_sessions,
    ROUND((SELECT SUM(data_length + index_length) / 1024 / 1024 FROM information_schema.TABLES WHERE table_schema = 'wordpress_db' AND table_name LIKE 'wp_edubot%'), 2) as total_size_mb;"

# 4. Uptime
echo ""
echo "4. UPTIME"
/usr/bin/systemctl status nginx | grep "Active"

# 5. Recommendations
echo ""
echo "5. RECOMMENDATIONS"
echo "   [ ] Review slow queries"
echo "   [ ] Check disk space growth"
echo "   [ ] Verify backup success"
echo "   [ ] Review error logs"

echo ""
echo "=== REPORT COMPLETE ==="
```

---

### Capacity Planning (28th of Month)

**Duration:** 30 minutes  
**Owner:** Infrastructure Manager

**Review:**

```bash
# Check growth trends
echo "Database size trend (last 12 months):"
df -h /var/lib/mysql/ | tail -1 | awk '{print "Current: " $3}'

echo ""
echo "Disk space trend:"
# Check backup partition
df -h /backups/ | tail -1 | awk '{print "Usage: " $5 " of " $2}'

echo ""
echo "Capacity forecast:"
# Simple calculation: current size * growth rate
current_size=$(du -sb /var/lib/mysql/wordpress_db | awk '{print $1}')
daily_growth=1000000000 # 1GB/day estimated
months_until_full=$((total_disk_space / (daily_growth * 30)))
echo "Estimated months until full: $months_until_full"
```

---

## 🚨 Incident Response

### Critical Issue Response Flow

**Severity Levels:**

```
🔴 CRITICAL (Response: 5 min, Fix: 30 min)
   - System down (503 error)
   - Database unavailable
   - All conversions failing

🟠 HIGH (Response: 15 min, Fix: 2 hours)
   - Dashboard slow (> 5s load time)
   - API failures (some platforms)
   - Data corruption detected

🟡 MEDIUM (Response: 1 hour, Fix: 4 hours)
   - Partial feature failure
   - Performance degradation
   - Minor API issues

🟢 LOW (Response: 1 day, Fix: 1 week)
   - UI formatting issue
   - Minor performance impact
   - Documentation updates
```

### Incident Response Procedure

**Step 1: Triage (Immediately)**

```bash
# 1. Determine severity
curl -o /dev/null -s -w '%{http_code}' https://yourdomain.com
# 200 = OK, 500 = Critical

# 2. Check system status
systemctl status nginx mysql php-fpm

# 3. Check error logs
tail -50 /var/www/html/wp-content/debug.log
```

**Step 2: Mitigation (Within 5 minutes)**

```bash
# If 503 error:
# 1. Enable maintenance mode
touch /var/www/html/wp-content/maintenance.php

# 2. Check disk space
df -h

# 3. Clear cache
wp cache flush
php -r "opcache_reset();"

# 4. Restart services
systemctl restart nginx php-fpm
```

**Step 3: Investigation (Within 15 minutes)**

```bash
# 1. Check all logs
tail -100 /var/log/nginx/error.log
tail -100 /var/log/php-fpm/error.log
tail -100 /var/www/html/wp-content/debug.log

# 2. Check database
mysql -u root -p -e "SELECT * FROM PROCESSLIST WHERE TIME > 300;"

# 3. Check resources
top -u www-data
free -h
```

**Step 4: Resolution (As needed)**

- Restart services
- Rollback recent changes
- Restore from backup
- Escalate if needed

**Step 5: Communication**

- Update status page
- Notify stakeholders
- Document timeline
- Post-mortem meeting

---

## 📋 Maintenance Templates

### Daily Checklist Template

```
Date: _______________
Operator: _______________

Morning (05:00):
☐ System resources OK
☐ Services running
☐ Error logs reviewed
☐ Database accessible
☐ SSL certificate OK
☐ Cron jobs running

Issues Found:
_____________________________________

Actions Taken:
_____________________________________

Evening (18:00):
☐ Performance acceptable
☐ Errors reviewed
☐ Backup completed
☐ All systems normal

Notes:
_____________________________________
```

### Incident Log Template

```
Date/Time: _______________
Severity: ☐ Critical ☐ High ☐ Medium ☐ Low

Issue Description:
_____________________________________

Timeline:
15:30 - Issue detected
15:35 - Investigation started
15:40 - Root cause identified
16:00 - Issue resolved

Root Cause:
_____________________________________

Resolution:
_____________________________________

Prevention:
_____________________________________

Lessons Learned:
_____________________________________
```

---

## 📞 Contact & Escalation

### On-Call Schedule

```
Monday-Friday: 09:00-17:00
- Primary: [Name] - [Phone]
- Backup: [Name] - [Phone]

After Hours (17:00-09:00):
- On-Call: [Name] - [Phone]
- Emergency: [Name] - [Phone]

Weekends & Holidays:
- On-Call: [Name] - [Phone]
```

### Escalation Path

```
Level 1: Operations Team
- Threshold: Any incident
- Response: 15 minutes
- Authority: Mitigation steps

Level 2: DevOps Lead
- Threshold: > 1 hour duration
- Response: 30 minutes
- Authority: Service restart, rollback

Level 3: Infrastructure Manager
- Threshold: > 4 hours duration
- Response: 1 hour
- Authority: Major changes, vendor contact

Level 4: Director of Engineering
- Threshold: > 8 hours duration / Data loss risk
- Response: Immediate
- Authority: All decisions
```

---

## 🔗 Related Documentation

- [Deployment Guide](./DEPLOYMENT_GUIDE.md)
- [Troubleshooting Guide](./TROUBLESHOOTING_GUIDE.md)
- [Configuration Guide](./CONFIGURATION_GUIDE.md)
- [Best Practices](./BEST_PRACTICES.md)

