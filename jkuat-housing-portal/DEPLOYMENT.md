# JKUAT Housing Portal - Deployment Guide

This guide covers deploying the Housing Portal to production, test servers, or new installations.

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Initial Setup](#initial-setup)
3. [Database Setup](#database-setup)
4. [Configuration](#configuration)
5. [Verification](#verification)
6. [Troubleshooting](#troubleshooting)
7. [Database Recovery](#database-recovery)

---

## Pre-Deployment Checklist

Before deploying, ensure:

- [ ] PHP 8.2+ installed
- [ ] MySQL 5.7+ or MariaDB 10.4+ installed
- [ ] Apache or compatible web server
- [ ] Composer installed (optional, for package management)
- [ ] .env file configured with correct credentials
- [ ] Database backup available (if migrating from existing system)
- [ ] Email credentials ready (if enabling email notifications)

---

## Initial Setup

### Step 1: Copy Project Files

```bash
# Development to Supervisor:
# Copy entire jkuat-housing-portal folder to:
c:\xampp\htdocs\jkuat-housing-portal/

# Or on Linux/Mac:
/var/www/html/jkuat-housing-portal/
```

### Step 2: Set Directory Permissions

```bash
# Linux/Mac only (Windows XAMPP handles this automatically)
chmod -R 755 jkuat-housing-portal/
chmod -R 755 jkuat-housing-portal/logs/
chmod -R 755 jkuat-housing-portal/images/uploads/
chmod 600 jkuat-housing-portal/.env
```

### Step 3: Verify PHP Configuration

Ensure `php.ini` allows file uploads:

```ini
; Find and update in php.ini:
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
```

---

## Database Setup

### Full Setup (New Installation)

This is the **recommended approach** when deploying to a new system:

```bash
cd c:\xampp\htdocs\jkuat-housing-portal

# 1. Create the database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS staff_housing CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 2. Import initial schema
mysql -u root staff_housing < "staff_housing (1).sql"

# 3. Apply all migrations
php migrations/run_migrations.php
```

**Result**: Complete database with all features in one operation.

### Verification

```bash
# Check if tables exist
mysql -u root staff_housing -e "SHOW TABLES;"

# Check if migrations ran
mysql -u root staff_housing -e "SELECT * FROM migrations_log LIMIT 5;"
```

### Migrating from Existing System

If migrating from another JKUAT installation:

```bash
# 1. Export existing database
mysqldump -u old_user -p old_password old_database > backup.sql

# 2. Create new database
mysql -u root -e "CREATE DATABASE staff_housing CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# 3. Import backup
mysql -u root staff_housing < backup.sql

# 4. Run migrations (applies any new changes)
php migrations/run_migrations.php
```

---

## Configuration

### 1. Environment Variables

Create `.env` from `.env.example`:

```bash
cp .env.example .env
# Or manually edit:
```

Update `.env` with your settings:

```env
# DATABASE (CRITICAL)
DB_HOST=127.0.0.1      # localhost or your MySQL host
DB_PORT=3306           # MySQL port
DB_NAME=staff_housing  # Database name
DB_USER=root           # MySQL user
DB_PASS=               # MySQL password (empty if none)

# APPLICATION
APP_URL=http://localhost/jkuat-housing-portal/
APP_ENV=production     # Use 'production' for live

# EMAIL (OPTIONAL - Comment out to disable)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=housing@jkuat.ac.ke
SMTP_PASS=your_app_password
SMTP_SECURE=tls
SMTP_FROM=housing@jkuat.ac.ke
SMTP_FROM_NAME="JKUAT Housing Portal"
```

### 2. Gmail App Password Setup

For email notifications (optional):

1. Go to [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords)
2. Select **Mail** and **Windows Computer**
3. Generate app password
4. Copy password to `.env` as `SMTP_PASS`

**Never use regular Gmail password!**

### 3. Security Hardening

```bash
# Protect sensitive files (Linux/Mac)
chmod 600 .env
chmod 600 .env.example
chmod 600 staff_housing\ \(1\).sql

# On Windows: Use NTFS permissions or .htaccess
```

Create `.htaccess` in root for extra protection:

```apache
<FilesMatch "\.(env|sql|php|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Protect sensitive directories
<Directory "^(migrations|logs|images/uploads)">
    Require all granted
</Directory>
```

---

## Verification

### 1. Is the Application Running?

```
Visit: http://your-domain/jkuat-housing-portal/php/login.php
```

Expected: Login page loads without errors

### 2. Test Admin User

```sql
-- Check if admin exists (run in MySQL)
SELECT applicant_id, name, email, role 
FROM applicants 
WHERE role = 'admin' LIMIT 1;
```

If no admin, create one:

```sql
INSERT INTO applicants 
(applicant_id, pf_no, name, email, contact, password, username, role, is_email_verified)
VALUES 
('ADM001', '9999', 'Admin', 'admin@jkuat.ac.ke', '0700000000', 
 '$2y$10$YourHashedPasswordHere', 'admin', 'admin', 1);
```

### 3. Test Login

1. Login with admin credentials
2. Navigate to Staff Dashboard (csdashboard.php)
3. Check if data loads
4. Verify all menus appear

### 4. Test Email (Optional)

1. Log in as applicant
2. Submit a notice (triggers email to admin)
3. Check if email arrives
4. If not, see [Troubleshooting - Email Issues](#email-not-sending)

---

## Troubleshooting

### Application Shows Blank Page

**Cause**: PHP or database error

**Solution**:
```bash
# Check error log
tail -f logs/*
# or on Windows:
type logs\*.log
```

**Actions**:
- Verify `.env` database credentials
- Ensure MySQL is running
- Check PHP version is 8.2+

### "Connection refused" Error

**Cause**: MySQL not running or wrong credentials

**Solution**:
```bash
# Start MySQL
# Windows: Use XAMPP Control Panel
# Linux: sudo systemctl start mysql

# Verify connection
mysql -h localhost -u root -e "SELECT 1;"
```

### Database Import Fails

**Problem**: "Access denied" error

**Solution**:
```bash
# Check MySQL is running
mysql -u root -e "SHOW DATABASES;"

# Create database first if needed
mysql -u root -e "CREATE DATABASE staff_housing;"

# Then import with database name
mysql -u root staff_housing < staff_housing\ \(1\).sql
```

### Email Not Sending

**Verify SMTP Settings**:
1. Check `.env` has correct SMTP_USER and SMTP_PASS
2. Ensure SMTP_PORT is 587 (not 465 for TLS)
3. SMTP_SECURE should be 'tls' (lowercase)
4. Email must use Gmail App Password (not regular password)

**Test Email**:
```php
<?php
require 'includes/email.php';
$result = sendEmail(
    'test@example.com',
    'Test Subject',
    'Test Message'
);
echo $result ? 'Email sent!' : 'Failed to send email';
?>
```

### Login Fails for All Users

**Cause**: Database corruption or no users

**Solution**:
```bash
# Check if applicants table exists
mysql -u root staff_housing -e "SHOW TABLES LIKE 'applicants';"

# Check if test users exist
mysql -u root staff_housing -e "SELECT applicant_id, name FROM applicants LIMIT 5;"

# If empty, re-import database
mysql -u root staff_housing < staff_housing\ \(1\).sql
```

### Migrations Fail to Run

**Check migration log**:
```bash
mysql -u root staff_housing -e "SELECT * FROM migrations_log ORDER BY created_at DESC LIMIT 10;"
```

**Re-run migrations**:
```bash
php migrations/run_migrations.php
```

**Force specific migration**:
```bash
# Manually run a single migration
mysql -u root staff_housing < migrations/2026-02-11_add_applicant_profile_fields.sql
```

---

## Database Recovery

### After XAMPP Reinstall

The critical recovery process:

```bash
# 1. Create database
mysql -u root -e "CREATE DATABASE staff_housing;"

# 2. Import initial schema
mysql -u root staff_housing < staff_housing\ \(1\).sql

# 3. Apply migrations
php migrations/run_migrations.php

# 4. Verify
mysql -u root staff_housing -e "SELECT COUNT(*) as tables FROM information_schema.tables WHERE table_schema='staff_housing';"
```

**Expected output**: Should show ~30+ tables

### Backup & Restore

**Create backup**:
```bash
mysqldump -u root staff_housing > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Restore from backup**:
```bash
mysql -u root -e "DROP DATABASE IF EXISTS staff_housing; CREATE DATABASE staff_housing;"
mysql -u root staff_housing < backup.sql
```

### Zero-Downtime Migration

For minimal disruption:

1. Stop application temporarily
2. Backup current database: `mysqldump -u root staff_housing > backup.sql`
3. Import new schema: `mysql -u root staff_housing < staff_housing (1).sql`
4. Run migrations: `php migrations/run_migrations.php`
5. Restart application

---

## Production Considerations

### Performance Optimization

1. **Enable database caching**:
   ```sql
   SET GLOBAL max_connections = 200;
   SET GLOBAL query_cache_size = 268435456;
   ```

2. **Optimize PHP session storage**:
   ```ini
   session.save_handler = files
   session.gc_maxlifetime = 1440
   ```

3. **Enable compression**:
   ```apache
   <IfModule mod_deflate.c>
       AddOutputFilterByType DEFLATE text/html text/plain text/css
   </IfModule>
   ```

### Security Hardening

1. **Disable directory listing**:
   ```apache
   <Directory "/var/www/html/jkuat-housing-portal">
       Options -Indexes
   </Directory>
   ```

2. **Set appropriate file permissions**:
   - `.env` should be readable only by web server user
   - Upload directory should be writable by web server
   - Application files should not be writable

3. **Enable HTTPS**:
   - Obtain SSL certificate
   - Redirect all HTTP to HTTPS
   - Update `APP_URL` in `.env` to use `https://`

### Regular Maintenance

```bash
# Weekly: Backup database
mysqldump -u root staff_housing > backup_weekly_$(date +%Y%m%d).sql

# Monthly: Clean old logs
find logs/ -mtime +30 -delete

# Monthly: Clear uploaded temp files
find images/uploads/ -mtime +60 -delete
```

---

## Support Resources

- [Setup Guide](SETUP.md) - Initial installation steps
- [README.md](README.md) - Project overview and features  
- [Migrations Documentation](migrations/README.md) - Database change tracking
- **Error Logs**: Check `logs/` directory for application errors
- **MySQL Logs**: Check XAMPP/MySQL logs for database issues

---

## Deployment Timeline

| Phase | Duration | Task |
|-------|----------|------|
| Pre-Deployment | 1 hour | Review checklist, backup data |
| Installation | 30 min | Copy files, configure .env |
| Database | 15 min | Import schema, run migrations |
| Testing | 30 min | Verify functionality, test emails |
| Go-Live | 15 min | Enable access, monitor errors |
| **Total** | **~2.5 hours** | Complete deployment |

---

## Quick Reference: One-Command Deployment

```bash
# All-in-one deployment (after files copied)
mysql -u root -e "CREATE DATABASE staff_housing;" && \
mysql -u root staff_housing < staff_housing\ \(1\).sql && \
php migrations/run_migrations.php && \
echo "Deployment complete! Visit: http://localhost/jkuat-housing-portal/"
```

---

**For questions about specific features, see README.md or SETUP.md**
