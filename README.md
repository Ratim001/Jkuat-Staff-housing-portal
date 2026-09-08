# JKUAT Staff Housing Portal

A PHP web application for staff housing applications, allocation ballots, tenant records, bills, and maintenance requests.

## Project context

This repository documents Mohamed Isaak Boru's work on staff housing workflows during his JKUAT software development / information systems support internship. It is presented as a development and portfolio project. Repository contents do not establish an official deployment or institutional endorsement. Existing source attributions are retained.

## Main workflows

| Area | Workflow |
| --- | --- |
| Applications | Applicant registration, profile completion, and housing applications |
| Allocation | Ballot participation, raffle slots, and administrative allocation |
| Tenancy | Tenant records, notices, and forfeiture requests |
| Billing | Bills, disputes, and payment status tracking |
| Maintenance | Service requests and billable-service decisions |
| Notifications | In-app notices and email templates |

## Stack

PHP 8.2+, MariaDB, HTML, CSS, JavaScript, and Composer. Apache/XAMPP is the documented local setup; Docker configuration is also included. Some migrations use MariaDB-specific syntax, so MySQL compatibility should be tested separately.

## Run locally

Follow [SETUP.md](SETUP.md) to install dependencies, configure the environment, import the empty schema, run migrations, and generate a local administrator account.

The repository root is the application directory. Clone it into a folder named `jkuat-housing-portal`; there is no second application copy inside it.

## Repository guide

| Path | Purpose |
| --- | --- |
| `php/` | Application pages and request handlers |
| `includes/` | Database, authentication, email, and shared utilities |
| `database/schema.sql` | Initial database structure, without exported records |
| `migrations/` | Incremental schema changes and the admin bootstrap script |
| `templates/emails/` | Notification templates |
| `css/`, `js/`, `images/` | Frontend assets |
| `.env.example` | Configuration template |

## Validation and limitations

The project currently relies on manual application testing. Removing duplicate files and improving documentation does not establish production readiness.

For a fresh installation, validate login, role restrictions, profile completion, application submission, allocation, bill disputes, service requests, and email delivery with fictional records. Detailed checks are in [HANDOVER.md](HANDOVER.md).

- Public database exports and example staff accounts are no longer part of the working tree after this cleanup is applied.
- Fresh setup creates structure; it does not restore previous business records.
- Database schema import, migration execution, and administrator bootstrap must be verified in a PHP/MariaDB environment before deployment.
- Historical Git commits can still contain earlier exports; working-tree cleanup alone does not erase history.
- Use fictional records and redact personal information when preparing screenshots or demonstrations.

## Documentation

- [Installation](SETUP.md)
- [Deployment and backup recovery](DEPLOYMENT.md)
- [Handover and acceptance checks](HANDOVER.md)
- [Migration usage](migrations/README.md)

## License and attribution

JKUAT Housing Portal — All Rights Reserved.

Existing third-party and institutional notices in the source remain applicable. This cleanup does not change ownership or licensing.
