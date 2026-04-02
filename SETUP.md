# JKUAT Housing Portal - Setup Guide

## Quick Start

This guide explains how to set up the Housing Portal after installing XAMPP or reinstalling the system.

---

## Step 1: Install XAMPP

1. Download XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Install and start Apache, MySQL, and PHP
3. Default database credentials:
   - **Host**: `127.0.0.1` or `localhost`
   - **Port**: `3306` (MySQL)
   - **User**: `root`
   - **Password**: (empty by default)

---

## Step 2: Create Environment Configuration

1. Copy `jkuat-housing-portal/.env.example` to `jkuat-housing-portal/.env`
2. Update the `.env` file with your configuration:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=staff_housing
DB_USER=root
DB_PASS=
APP_URL=http://localhost/jkuat-housing-portal
```

---

## Step 3: Import Initial Database Schema

This is the **CRITICAL STEP** that restores your database after XAMPP reinstallation.

### Option A: Using phpMyAdmin (Easiest)

1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Click **Import** tab
4. Choose file: `jkuat-housing-portal/staff_housing (1).sql`
5. Click **Import**
6. You should see: "Import has been successfully finished, X queries executed."

### Option B: Using MySQL Command Line

```bash
# Navigate to project directory
cd c:\xampp\htdocs\jkuat-housing-portal

# Import the database
mysql -h localhost -u root staff_housing < "staff_housing (1).sql"
```

**Note**: If you get "Unknown database", first create it:
```bash
mysql -h localhost -u root -e "CREATE DATABASE staff_housing"
mysql -h localhost -u root staff_housing < "staff_housing (1).sql"
```

### Option C: Using PHP Script

```bash
cd c:\xampp\htdocs\jkuat-housing-portal
php -r "
    \$conn = new mysqli('localhost', 'root', '', 'staff_housing');
    if (\$conn->connect_error) die('Connection failed: ' . \$conn->connect_error);
    \$sql = file_get_contents('staff_housing (1).sql');
    if (\$conn->multi_query(\$sql)) echo 'Database imported successfully';
    else echo 'Error: ' . \$conn->error;
"
```

---

## Step 4: Run Database Migrations

After importing the initial schema, apply all project-specific changes:

```bash
cd c:\xampp\htdocs\jkuat-housing-portal
php migrations/run_migrations.php
```

**What this does:**
- Applies all pending migration files
- Skips migrations that have already been run
- Logs execution in database for tracking
- Safe to run multiple times (won't duplicate changes)

### Check Migration Status (Optional)

```bash
php migrations/run_migrations.php --status
```

This shows which migrations are pending without applying them.

---

## Step 5: Verify Setup

1. Navigate to: `http://localhost/jkuat-housing-portal/php/login.php`
2. Try logging in with these test credentials:

   | Username | PF Number | Status |
   |----------|-----------|--------|
   | Maxwell  | 3040      | Tenant |
   | Jack     | 3035      | Tenant |
   | Ratim    | 5000      | Tenant |

3. If you can access your dashboard, the setup is complete ✓

---

## Important: Database Persistence After XAMPP Reinstall

### The Problem
When you reinstall XAMPP, the MySQL databases are removed. Your project files and code changes remain, but the database goes back to empty.

### The Solution
This setup process restores the database completely:

1. **Initial import** (`staff_housing (1).sql`) restores the base schema and tables
2. **Migrations** (`migrations/run_migrations.php`) apply all feature updates and changes

Together, these ensure your database is in the exact same state as your development system.

### Recovery Process After XAMPP Reinstall

Simply repeat **Steps 2-5** above. The entire database state will be restored exactly.

---

## Troubleshooting

### "Unknown database 'staff_housing'"
**Solution:** Create the database first:
```bash
mysql -h localhost -u root -e "CREATE DATABASE staff_housing"
```

### "Access denied for user 'root'@'localhost'"
**Solution:** XAMPP MySQL might have a password. Check `.env` file and update `DB_PASS`:
```env
DB_PASS=your_mysql_password
```

### Migrations not running / stuck
**Solution:** Check log file:
```bash
cat logs/migration.log
```

Or re-run with verbose output:
```bash
php migrations/run_migrations.php
```

### Pages not loading after setup
**Possible causes:**
- Apache not running (start in XAMPP Control Panel)
- MySQL not running (start in XAMPP Control Panel)
- Incorrect database credentials in `.env` file
- Application files not in `c:\xampp\htdocs\jkuat-housing-portal`

---

## Project Structure

```
jkuat-housing-portal/
├── php/                          # Main application files
│   ├── applicants.php           # Applicant dashboard
│   ├── applicant_profile.php    # Profile management
│   ├── login.php                # Login page
│   ├── ballot.php               # Ballot system
│   ├── bills.php                # Bill management
│   └── ... (other pages)
├── migrations/                   # Database migrations
│   ├── run_migrations.php       # Main migration runner
│   ├── 2026-02-11_*.sql        # Feature migrations
│   └── README.md                # Migration documentation
├── includes/                     # PHP utilities
│   ├── db.php                   # Database connection
│   ├── auth.php                 # Authentication logic
│   ├── email.php                # Email utilities
│   └── validation.php           # Form validation
├── css/                          # Stylesheets
├── js/                           # JavaScript files
├── staff_housing (1).sql        # Initial database schema
├── .env                          # Configuration (GITIGNORED)
├── .env.example                  # Configuration template
└── README.md                     # Project documentation
```

---

## Next Steps

After setup is complete:

1. **For Development**: See [README.md](README.md) for feature documentation
2. **For Database Queries**: See [migrations/README.md](migrations/README.md) for migration info
3. **For Deployment**: See [DEPLOYMENT.md](DEPLOYMENT.md) for production setup

---

## Support

- **Check logs**: `jkuat-housing-portal/logs/` directory
- **Database help**: Review migration files in `jkuat-housing-portal/migrations/`
- **PHP errors**: Check browser console and XAMPP error logs

