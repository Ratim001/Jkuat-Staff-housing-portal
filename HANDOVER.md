# Handover and acceptance checks

## Scope

The application covers staff housing applications, allocation ballots and raffles, tenants, bills, maintenance requests, and notifications. See [README.md](README.md) for the project context and source attribution.

## Installation handover

Provide the code, [SETUP.md](SETUP.md), the empty schema, migrations, and environment-variable names. Transfer any authorized credentials or private backups separately through an appropriate private channel.

## Acceptance checks

Use fictional accounts and record the tested commit, PHP/MariaDB versions, date, and results.

- [ ] Import the empty schema and run pending migrations successfully.
- [ ] Confirm migration status after a second run.
- [ ] Create a local admin with the bootstrap script and log in.
- [ ] Confirm rerunning the bootstrap does not change an existing password.
- [ ] Verify applicant registration, profile completion, and email verification.
- [ ] Check each role's allowed and forbidden actions.
- [ ] Submit a housing application and exercise ballot/raffle allocation.
- [ ] Check duplicate participation and allocation handling.
- [ ] Test bill disputes, service requests, and notifications.
- [ ] Verify document-upload access and invalid-file handling.
- [ ] Restore a private staging backup and confirm expected records.

## Portfolio demonstration

Capture screenshots only from fictional demonstration records. Useful views include the staff dashboard, application status, allocation workflow, and service requests. Record what you implemented and what remains incomplete; avoid claims of institutional deployment unless verified.

## Remaining validation

The cleanup has not verified a full PHP/MariaDB installation or production behavior. The public repository no longer needs a populated database export for setup, but older Git history remains a separate review item.
