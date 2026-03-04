# Student Attendance System

A multi-role school attendance management platform built with Laravel 12 for institutions that need centralized attendance operations, role-based access control, subscription-aware feature management, and optional device-based attendance capture.

This project was developed as a portfolio-ready full-stack application and demonstrates practical backend architecture, admin workflows, reporting, onboarding flows, and domain modeling for a real-world school management use case.

## Project Overview

The system supports the full attendance lifecycle for a school:

- public landing page with subscription-based school onboarding
- super admin controls for managing schools, plans, subscriptions, and devices
- school admin workflows for classes, sections, teachers, students, calendar events, and attendance oversight
- teacher tools for daily attendance marking and student change requests
- student and parent portals for visibility into attendance records and notifications
- device attendance API for hardware-assisted attendance syncing

The application uses a service-oriented structure so controllers stay thin while business logic is grouped into focused service classes.

## Key Features

### 1. Multi-role access control

The platform includes distinct dashboards and protected routes for:

- Super Admin
- School Admin
- Teacher
- Student
- Parent
- Staff

Role and permission checks are enforced at route and service level, including permission-specific demo routes for reports and attendance actions.

### 2. School and subscription management

- multi-school architecture with school-level ownership boundaries
- subscription plans: `basic`, `pro`, `enterprise`
- plan-based feature gating through `PlanFeatureService`
- school activation/deactivation controls
- subscription updates and capacity management
- Stripe checkout and webhook integration for school onboarding
- fallback test registration flow when Stripe keys are not configured

### 3. Attendance operations

- teacher attendance entry by class assignment
- support for present, absent, leave, and remarks
- configurable correction window behavior by plan
- admin attendance reporting with filters
- CSV attendance export for enterprise plan
- attendance audit logging for enterprise plan
- parent and student-facing attendance visibility

### 4. Device attendance integration

- token-protected device API
- idempotent device attendance event ingestion
- heartbeat endpoint for device health reporting
- realtime device last-seen and last-event tracking
- optional student-device identifier mapping

### 5. Academic administration

- class and section management
- class-section mapping
- teacher creation and attendance access control
- class teacher assignment
- student registration with image upload support
- holiday and calendar event management
- CSV import for students and teachers

### 6. Controlled student data changes

Teachers can request student create, update, and delete operations, while admins retain approval authority. This adds a useful review workflow instead of allowing unrestricted direct edits.

### 7. One-time credential generation

The system can generate one-time passwords for class teachers and students, with forced password change support after first login.

## Tech Stack

- Backend: PHP 8.2, Laravel 12
- Frontend: Blade, Vite, Tailwind CSS 4, Axios
- Database: SQLite by default for local setup
- Authentication: Laravel session-based auth with role-aware login flow
- Payments: Stripe checkout and webhook integration
- Tooling: PHPUnit, Laravel Pint, Laravel Pail, Laravel Tinker

## Architecture Notes

Some of the main application modules are organized as:

- `app/Services/Auth` for login and dashboard routing logic
- `app/Services/Admin` for school admin operations and reports
- `app/Services/Teacher` for attendance marking and student request workflows
- `app/Services/Attendance` for device sync, notifications, and audit logging
- `app/Services/PublicSite` for subscription checkout and onboarding
- `app/Services/Subscription` for plan-based feature controls

This separation keeps the application easier to maintain and makes the business rules more explicit than placing everything inside controllers.

## Demo Data

The seeders create sample schools, users, teachers, classes, sections, and students for local testing.

### Default login credentials

All seeded users use the same password:

- Password: `password`

Available demo accounts:

- Super Admin: `superadmin@attendance.test`
- School Admin: `admin@alpha.test`
- Teacher: `teacher@alpha.test`
- Student: `student@alpha.test`
- Parent: `parent@alpha.test`
- Staff: `staff@beta.test`

## Local Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js + npm

### Installation

```bash
git clone <your-repo-url>
cd student-attendance-system
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

You can also use the Composer helper script already defined in the project:

```bash
composer run setup
```

### Development mode

To run the Laravel server, queue listener, log stream, and Vite dev server together:

```bash
composer run dev
```

## Testing

Run the automated tests with:

```bash
composer test
```

The project currently includes feature-level coverage for the attendance workflow and basic Laravel starter tests.

## Screenshots
Not available now

## Author
- Name: `Mohan Bashyal`
- Email: `bashyalmohan77@gmail.com`
- GitHub: `https://github.com/mohan-bashyal`
- LinkedIn: `https://www.linkedin.com/in/mohan-bashyal/`
  
## Why This Project Matters

This project highlights practical software engineering skills relevant for internships and junior backend or full-stack roles:

- Laravel application structure beyond CRUD-only pages
- domain-driven thinking around schools, roles, attendance, and subscriptions
- RBAC and ownership enforcement
- service-layer business logic design
- report generation and CSV export
- external integration handling with Stripe and device APIs
- seeded demo environments for reviewer-friendly evaluation

## Possible Future Enhancements

- automated notifications via SMS or email providers
- richer analytics dashboards with charts and comparisons
- API-first mobile app support
- biometric or QR-based device attendance
- stronger automated test coverage for admin and subscription flows

## License

This project is intended for learning, portfolio, and demonstration purposes.
