# EdTech360

**EdTech360 : Comprehensive Educational Management System**

EdTech360 is a responsive, role-aware educational management system being developed for Form 6 school administration. It aims to bring academic setup, class and student management, teaching assignments, curriculum, co-curricular activities, and reporting into one platform.

> **Development status:** Active development. The UI foundation and Phase 2A authentication, database, and authorization foundation are implemented. Many school-management workflows are still under development; this is **not** a production-ready release.

## Development Progress

| Phase | Scope | Status |
| --- | --- | --- |
| Phase 1 | UI Shell & Responsive Layout | Completed (initial UI foundation) |
| Phase 2A.1–2A.4 | Database schema and migrations | Completed |
| Phase 2A.5 | Eloquent models and relationships | Completed |
| Phase 2A.6 | Authentication, roles, policies, scoped access and security integration (Batches A–D) | Implemented; current automated suite passing |
| Phase 2B | Core Academic Management (sessions, semesters, batches, classes, teachers, students and subjects) | Planned / next |

**Latest locally reported test baseline (9 October 2026):** `49 tests, 108 assertions`, with zero failures, using PHPUnit 12.5.38. Results may change as the project develops; run the tests on your own machine to verify your checkout.

## Implemented Features

### Responsive UI foundation

- Bootstrap 5.3.8 and Bootstrap Icons 1.13.1
- Laravel Blade layouts and reusable UI components
- Responsive desktop/sidebar, tablet/mobile navigation and mobile bottom navigation
- Light/dark theme foundation and EdTech360 branding
- Login, dashboard, students, classes and subjects screens
- Student registration and profile-completion UI foundations

Some screens remain previews or placeholders; not every visible button represents a completed workflow.

### Database and security foundation

- MySQL schema, migrations and Eloquent models for academic and school operations
- Authentication with login, logout, session handling and inactive-account checks
- Role-based permissions with **Spatie Laravel Permission**
- Multiple roles per user, with access constrained by active teaching or class assignments
- Separation between technical **Super Admin** and operational **School Admin**
- Class-scoped teacher access and student enrollment authorization
- Semester-aware subject-teacher access
- Assistant Class Teacher permissions and administrator-controlled delegation
- Student self-service profile access and restricted phone-number updates
- Student profile updates and class-transfer services, with validation and audit records
- Historical enrollment access rules distinct from current student-profile access
- Scoped students/classes listing foundations, audit integration and enrollment consistency safeguards

> **Security note:** Successful automated tests do not guarantee production security. Additional concurrency, database-integrity, deployment and end-to-end checks remain necessary.

## User Roles

The system currently defines these role names:

| Role | Intended responsibility |
| --- | --- |
| `super_admin` | Technical/system-level administration; exclusive central audit-log access by default |
| `school_admin` | Day-to-day school administration (often Penolong Kanan) |
| `curriculum_admin` | Curriculum coordination |
| `cocurricular_admin` | Co-curricular coordination |
| `class_teacher` | Assigned class management |
| `assistant_class_teacher` | Assigned class assistance, with restricted/delegated actions |
| `subject_teacher` | Assigned subjects, classes and semesters |
| `subject_group_coordinator` | Subject-group coordination |
| `student` | Student-facing functionality and permitted self-service |

Roles can overlap. Having a permission alone does not necessarily grant access to every class or student; record-level policies and assignment scope still apply.

## Technology Stack

- PHP 8.3+
- Laravel 13
- MySQL
- Laravel Blade
- Bootstrap 5.3.8 / Bootstrap Icons 1.13.1
- Vite 8
- Node.js / npm
- Spatie Laravel Permission
- PHPUnit 12
- Composer and Git

For Windows development, **Laragon** can provide PHP, MySQL and a local web server.

## Requirements

Install and configure:

- PHP 8.3+ with the extensions required by Laravel and MySQL
- Composer
- Node.js and npm (compatible with the project's `package-lock.json`)
- MySQL
- Git

The local application database and testing database must be **separate**.

## First-Time Setup (Windows / PowerShell)

### 1. Clone and install dependencies

Open a terminal in the folder where you want to store the project (for example, `C:\laragon\www`):

```powershell
git clone https://github.com/Agien99/edtech360.git
cd edtech360
composer install
npm ci
```

If no `package-lock.json` is present in your checkout, use `npm install` instead of `npm ci`.

### 2. Configure the application

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Update `.env` for your **local** MySQL installation. Example Laragon settings:

```dotenv
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edtech360
DB_USERNAME=root
DB_PASSWORD=
```

Create the `edtech360` MySQL database first using your preferred MySQL client. Use your actual database username/password if they differ from this example.

**Never commit** `.env`, `.env.testing`, application keys, passwords, database dumps or other secrets.

### 3. Prepare the schema, permissions and initial administrator

Make sure MySQL is running, then execute:

```powershell
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan edtech:create-super-admin
```

The final command is an interactive **initial setup** command. It will refuse to create another Super Admin when one already exists. On an existing installation, do not repeat it unnecessarily.

### 4. Build assets and run the application

```powershell
npm run build
php artisan serve
```

Open **http://127.0.0.1:8000** and sign in using the local account you created.

For live frontend development, you may instead use:

```powershell
composer run dev
```

This starts Laravel and Vite together. Alternatively, run `php artisan serve` and `npm run dev` in separate terminals.

## Automated Tests (Separate MySQL Database)

The PHPUnit configuration targets an isolated MySQL database named **`edtech360_testing`**. This database is **not the same as** the development database `edtech360`.

1. Create an empty MySQL database named `edtech360_testing`.
2. Create a local `.env.testing` with appropriate credentials, using `.env` as a starting point and adjusting `APP_ENV=testing`, `DB_CONNECTION=mysql` and `DB_DATABASE=edtech360_testing`.
3. Confirm your test connection points to the testing database before running the suite.

```powershell
php vendor/bin/phpunit
```

**Expected baseline for the current implementation:**

```text
OK (49 tests, 108 assertions)
```

The test suite uses database refresh operations. **Never point testing credentials at a development, shared, or production database.** A database safety test is included, but you should still confirm the connection settings on every new laptop.

## After Pulling Updates From GitHub

When the repository is already installed locally (for example, on a second laptop):

```powershell
cd C:\laragon\www\edtech360
git status
git switch main
git pull origin main
composer install
npm ci
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan optimize:clear
```

Review `git status` and resolve local changes **before** pulling. `npm ci` is needed when JavaScript dependencies or the lockfile change; it can be skipped for code-only updates. Use `npm run build` after frontend changes, or `npm run dev` while working on the UI.

The roles seeder synchronizes the **defined role permissions**. Review permission changes before rerunning it on an environment with custom role configuration; individual teacher delegations should be handled through their dedicated workflow.

> **Important when switching laptops:** `git pull` downloads source code, **not** local MySQL records, `.env`, `.env.testing`, uploaded files or installed dependencies. Each laptop needs its own environment configuration and database. A Super Admin created on one laptop does not automatically exist on the other.

If your other laptop already has a Super Admin and the correct database, you do not need to create that account again.

## Current Routes

### User-facing pages

| Page | Method | Route |
| --- | --- | --- |
| Login | GET | `/login` |
| Dashboard | GET | `/dashboard` |
| Students | GET | `/students` |
| Register student (UI foundation) | GET | `/students/register` |
| Complete student profile (UI foundation) | GET | `/students/complete-profile` |
| Classes | GET | `/classes` |
| Subjects | GET | `/subjects` |

### Implemented student/security endpoints

| Purpose | Method | Route |
| --- | --- | --- |
| Student profile update | PATCH | `/students/{student}/profile` |
| Student class transfer | POST | `/students/{student}/transfer` |
| Own student profile | GET | `/my/student-profile` |
| Own phone-number update | PATCH | `/my/student-profile` |
| Teacher student-editing delegation | PATCH | `/teachers/{teacher}/delegations/student-editing` |

All protected routes require authentication; individual actions are further restricted by permissions, policies and record-level access rules. The list above is not an exhaustive route inventory. To inspect your current checkout:

```powershell
php artisan route:list
```

## Git Workflow (Office and Personal Laptop)

Development currently uses `main` as the primary working branch.

Before starting work on either laptop:

```powershell
git status
git switch main
git pull origin main
```

After making changes, testing and reviewing staged files:

```powershell
git status
git add <paths-to-reviewed-files>
git commit -m "Describe the completed change"
git push origin main
```

Replace `<paths-to-reviewed-files>` with the actual file paths, such as `README.md`. Explicit staging helps avoid accidentally committing local configuration or secrets. Consider feature branches and pull requests as the project grows.

## Next Development Phase

**Phase 2B — Core Academic Management (planned)**

- Academic sessions and semesters
- Student intake batches and classes
- Teacher and student profiles and enrollment workflows
- Subjects and subject groups
- Teacher/class/subject assignments
- Academic setup integration and CRUD testing

Other later modules will include curriculum, assignments, quizzes, attendance, co-curricular management, reports, file management, and administration interfaces.

## Repository

[GitHub — Agien99/edtech360](https://github.com/Agien99/edtech360)

**EdTech360 : Comprehensive Educational Management System**