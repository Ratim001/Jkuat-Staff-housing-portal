# Local installation

Use an isolated development database and fictional records.

## 1. Install dependencies

Install PHP 8.2+ with mysqli, MariaDB, Composer, and Apache (for example through XAMPP). Run from the web server's document directory:

```bash
git clone https://github.com/Ratim001/Jkuat-Staff-housing-portal.git jkuat-housing-portal
cd jkuat-housing-portal
composer install
```

The clone directory is the application root. Do not create a nested second copy.

## 2. Configure the environment

Copy `.env.example` to `.env` and configure `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, and `APP_URL`. For XAMPP, the URL can be `http://localhost/jkuat-housing-portal/`. Keep this file untracked.

## 3. Create an empty database and import structure

In phpMyAdmin, create a new database named `staff_housing`, select it, and import `database/schema.sql`.

Alternatively, in a shell that supports input redirection:

```bash
mysql -h localhost -u root -p -e "CREATE DATABASE staff_housing CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
mysql -h localhost -u root -p staff_housing < database/schema.sql
```

Use your own database user and configured database name. These commands are for a NEW database. Do not import the initial schema over an existing installation.

The schema contains no staff accounts, applicant data, bills, or tenant records.

## 4. Apply migrations

```bash
php migrations/run_migrations.php --status
php migrations/run_migrations.php
php migrations/run_migrations.php --status
```

The runner records completed files in `schema_migrations`. Keep applied migration files unchanged. Some historical migrations use MariaDB-specific syntax; do not assume the included MySQL Docker configuration has equivalent behavior.

## 5. Create a local administrator

```bash
php migrations/seed_admin.php
```

This creates a fictional `admin` account with a newly generated password, displayed once in your local terminal. It does not reset an existing administrator. Store the generated password privately.

Open `http://localhost/jkuat-housing-portal/php/login.php` and log in with that generated account. Populate demonstration houses and applicants with fictional values.

## 6. Verify workflows

Follow [HANDOVER.md](HANDOVER.md). A successful schema import is only the beginning; test the application with each role.

## Recovering previous records

A fresh schema and migrations cannot restore old records after reinstalling XAMPP. Restore an authorized private database backup and uploaded-file backup instead; see [DEPLOYMENT.md](DEPLOYMENT.md). Never commit those backups.

## Troubleshooting

- Connection failures: confirm MariaDB is running and `.env` matches your database.
- Missing PHP dependencies: run `composer install` and check enabled extensions.
- Failed migration: capture the failing filename and error privately; inspect the database before retrying. Do not use `--force` as a default remedy.
- Email failures: verify SMTP configuration using a test mailbox.
