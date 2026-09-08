# Deployment and recovery

This is an operational guide for a development project, not a certification of production readiness.

## Before deployment

1. Verify the fresh installation in [SETUP.md](SETUP.md) against the intended PHP and MariaDB versions.
2. Test authentication, role restrictions, request validation, CSRF handling, uploads, and all critical workflows.
3. Configure a dedicated database user, HTTPS, a private `.env`, and SMTP.
4. Ensure the web server denies public access to `.env`, database SQL, migration scripts, logs, backups, Composer metadata, and other non-public project files.
5. Disable error display in production and send diagnostics to private logs.
6. Use fictional demonstration data; obtain appropriate authorization for institutional code and real records.
7. Verify compatibility before using the included MySQL Docker service: some migrations use MariaDB-specific syntax.

## New installation

Import `database/schema.sql` into a new, empty database, run migrations, and create an administrator using [SETUP.md](SETUP.md). The schema does not include accounts or business records.

## Updating an existing installation

Back up the database and uploads privately. Test the update against a restored staging copy. Deploy the reviewed application files, run pending migrations, and verify core workflows. Do not import the initial schema over an existing database.

Migrations are not automatically reversible. DDL can commit immediately in MySQL/MariaDB; a transaction wrapper alone does not guarantee rollback.

## Backup and recovery

Store database exports and uploaded files outside the repository and outside the publicly served directory. Limit access and test restoration regularly.

To recover after reinstalling XAMPP:

1. Restore the authorized private database backup into the intended database.
2. Restore uploaded files from the matching private backup.
3. Restore private configuration and install dependencies.
4. Check migration status and apply only pending, reviewed migrations.
5. Verify records and application workflows before reopening access.

An initial schema rebuild restores structure only. It cannot recreate historical applicants, bills, allocations, or uploaded documents.

## Earlier public exports

Removing an export from the default branch does not remove it from older commits, clones, forks, or cached copies. If earlier exports contain real personal records or active credentials, coordinate history cleanup and credential resets with the repository and system owners. Never paste records or credentials into public issues.
