# JKUAT Housing Portal

A comprehensive web-based housing allocation system for managing staff housing applications, ballots, billing, and tenant management at JKUAT (Jomo Kenyatta University of Agriculture and Technology).

## Project Overview

The Housing Portal provides:

- **Applicant Management**: Online housing applications with eligibility checks
- **Ballot System**: Fair randomized house allocation through ballot draws
- **Raffle System**: Alternative allocation mechanism with category-based slot management
- **Financial Management**: Bills tracking, disputes, and payment status
- **Service Requests**: Maintenance and repairs request system
- **Tenant Management**: Housing tenure tracking and notifications
- **Admin Dashboard**: Comprehensive management tools for staff and ICT departments
- **Notifications**: Email and in-app notifications for applicants and staff

## Technology Stack

- **Backend**: PHP 8.2
- **Database**: MySQL 10.4 (MariaDB)
- **Frontend**: HTML5, CSS3, JavaScript (vanilla)
- **Web Server**: Apache (via XAMPP)
- **Docker Support**: Optional containerization available

## Quick Start

### Prerequisites
- XAMPP (or Apache + PHP 8.2 + MySQL)
- Composer (for PHP dependencies)
- Browser (Chrome, Firefox, Safari, Edge)

### Installation

1. **Setup Instructions**: Follow [SETUP.md](SETUP.md)
2. **Deployment Notes**: See [DEPLOYMENT.md](DEPLOYMENT.md)
3. **Environment Variables**: Copy `.env.example` to `.env` and configure

### Running the Application

```bash
# Start XAMPP (Apache + MySQL)
# Navigate to:
http://localhost/jkuat-housing-portal/php/login.php
```

## User Roles

The system supports multiple user types:

### 1. **Applicants**
- View housing availability
- Submit housing applications
- Participate in ballot draws
- Track application status
- Manage bills and payments
- Submit service requests
- View notices and notifications

### 2. **Tenants**
- View current housing assignment
- Check monthly bills
- Report maintenance issues via service requests
- Receive notifications about forfeit and allocations
- View rental notices

### 3. **Staff (Admin)**
- Manage applicant profiles and eligibility
- Control ballot draws and raffle systems
- Generate allocation reports
- Manage housing inventory
- Process forfeit requests
- Post notices to applicants

### 4. **ICT Department**
- View system reports
- Manage bills and billing status
- Monitor service requests
- Access system analytics
- Manage staff assignments

## Key Features

### Ballot System
- Fair randomized allocation of houses
- Support for multiple categories (1BR, 2BR, 3BR, 4BR)
- Ballot status tracking (open/closed)
- Exclusion rules for won/allocated applicants
- Raffle slot management

### Forfeit Request Workflow
1. Applicant submits forfeit reason and documents
2. Admin reviews request
3. Admin approves/rejects with notifications
4. Application status updated upon approval
5. Applicant notified of decision

### Financial Management
- Monthly bill generation
- Payment status tracking
- Bill dispute submission
- Dispute resolution workflow
- Service charge billing

### Service Requests
- Maintenance request submission
- Billable vs non-billable classification
- Status tracking (pending, approved, completed)
- Photo attachment support
- Priority handling

### Notifications
- Email notifications for key events
- In-app notification dashboard
- Notification deletion management
- Admin notifications for pending actions

## Directory Structure

```
jkuat-housing-portal/
├── php/                          # Main application files
│   ├── login.php                # Authentication entry point
│   ├── applicants.php           # Applicant dashboard
│   ├── applicant_profile.php    # Profile edit/creation
│   ├── ballot.php               # Ballot participation
│   ├── bills.php                # Bill management
│   ├── service_requests.php     # Service request submission
│   ├── notifications.php        # Notification dashboard
│   ├── csdashboard.php          # Staff dashboard
│   ├── ictdashboard.php         # ICT dashboard
│   ├── manage_applicants.php    # Admin applicant management
│   ├── manage_raffle_draws.php  # Admin raffle management
│   └── ... (44+ pages)
├── includes/                     # Shared PHP utilities
│   ├── db.php                   # Database connection
│   ├── auth.php                 # Authentication & authorization
│   ├── email.php                # Email sending
│   ├── validation.php           # Input validation
│   ├── helpers.php              # Helper functions
│   └── init.php                 # Application initialization
├── migrations/                   # Database schema changes
│   ├── run_migrations.php       # Migration execution script
│   ├── 2026-02-11_*             # Feature migrations
│   └── README.md                # Migration documentation
├── css/                          # Stylesheets
│   ├── global.css               # Shared styles
│   ├── login.css                # Login page styling
│   ├── tenants.css              # Tenant page styling
│   └── ... (dashboard styles)
├── js/                           # JavaScript files
│   ├── global-ui.js             # UI utilities
│   └── pagination-length.js     # Data table pagination
├── images/uploads/              # User uploads (photos, documents)
├── logs/                         # Application logs
├── staff_housing (1).sql        # Initial database schema
├── .env                          # Environment configuration (GITIGNORED)
├── .env.example                  # Configuration template
├── SETUP.md                      # Setup and installation guide
├── DEPLOYMENT.md                 # Deployment instructions
├── docker-compose.yml           # Docker configuration
├── Dockerfile                    # Docker image definition
└── composer.json                # PHP dependencies

```

## Database Migrations

The project uses a structured migration system for version control:

```bash
# Run all pending migrations
php migrations/run_migrations.php

# Check status without applying
php migrations/run_migrations.php --status
```

### Key Migrations
- `2026-02-11_add_applicant_profile_fields.sql` - Profile data structure
- `2026-02-13_add_ballot_control.sql` - Ballot management tables
- `2026-02-16_add_is_disabled_and_manual_allocations.sql` - Disability allocation
- `2026-02-20_add_raffle_system.sql` - Raffle draw system
- `2026-03-02_add_post_forfeit_requests.sql` - Forfeit workflow
- `2026-03-11_add_disability_details.sql` - Disability details tracking

See [migrations/README.md](migrations/README.md) for complete migration documentation.

## Configuration

### Environment Variables (.env)

```env
# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=staff_housing
DB_USER=root
DB_PASS=

# Application
APP_URL=http://localhost/jkuat-housing-portal/
APP_ENV=development

# Email (Optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
```

## Security Considerations

1. **Database Credentials**: Store in `.env` (never commit to version control)
2. **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
3. **Email Verification**: New applicants must verify email
4. **Session Management**: PHP sessions with timeout
5. **Input Validation**: Server-side validation on all forms
6. **CSRF Protection**: Built into form submissions
7. **Access Control**: Role-based access to different pages

## Development Workflow

### Making Changes

1. **Code Changes**: Modify PHP, CSS, or JavaScript files
2. **Database Changes**: Create migration files in `migrations/`
3. **Run Migrations**: `php migrations/run_migrations.php`
4. **Test**: Verify changes in browser
5. **Commit**: Document changes in git

### Creating Migrations

```bash
# Create new migration file
# Naming: YYYY-MM-DD_description.sql or .php

# Example:
# migrations/2026-03-26_add_new_feature.sql
```

## Testing

To verify installation:

1. Navigate to: `http://localhost/jkuat-housing-portal/php/login.php`
2. Log in with test credentials (see SETUP.md)
3. Navigate through all major features
4. Check logs for any errors: `logs/` directory

## Troubleshooting

### Database Not Loading After XAMPP Reinstall
- **Solution**: Re-run Steps 2-5 in [SETUP.md](SETUP.md)
- The initial SQL schema + migrations restore everything

### Pages Show Blank or Error
- **Check**: XAMPP Apache and MySQL are running
- **Check**: Database credentials in `.env`
- **Check**: Application files in correct directory
- **Check**: Error logs in `logs/` folder

### Email Not Sending
- **Verify**: SMTP credentials in `.env`
- **Generate**: Gmail App Password (not regular password)
- **Check**: SMTP_SECURE is 'tls' (port 587)

### Login Issues
- **Check**: Database has applicant records
- **Verify**: Password hash is valid (bcrypt)
- **Check**: Session/cookie settings in PHP

## Support & Documentation

- **Setup Issues**: See [SETUP.md](SETUP.md)
- **Deployment**: See [DEPLOYMENT.md](DEPLOYMENT.md)
- **Migrations**: See [migrations/README.md](migrations/README.md)
- **Logs**: Check `logs/` directory for application errors

## Version Information

- **PHP Version**: 8.2+
- **MySQL Version**: 5.7+ or MariaDB 10.4+
- **Browser Support**: Modern browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- **Last Updated**: March 26, 2026

## License

JKUAT Housing Portal - All Rights Reserved

## Contributors

- **System Design & Development**: Mohamed Isaak Boru
- **Testing & Validation**: JKUAT Staff

---

**For assistance with setup or deployment, refer to SETUP.md and DEPLOYMENT.md**
