# EdTech360 — Comprehensive Educational Management System

EdTech360 CEMS V2.0 is a responsive educational management system built with Laravel and MySQL. The interface is designed for administrators, teachers, class teachers and students across desktop, laptop, tablet and smartphone layouts.

## Phase 1 — UI Shell & Responsive Layout

This branch establishes the visual and technical UI foundation:

- Laravel 13 application skeleton
- Bootstrap 5 + custom EdTech360 design system
- Alpine.js available for lightweight interactions
- Official EdTech360 application branding
- Responsive sidebar, top bar and mobile bottom navigation
- Collapsible desktop sidebar and off-canvas tablet/mobile navigation
- Light/dark theme support with persisted browser preference
- Role-aware navigation preview for Administrator, Teacher, Class Teacher and Student
- Responsive dashboard reference
- Students table on desktop with record-card presentation on mobile
- Classes and Subjects card layouts
- Teacher quick student registration form with only essential fields
- Mandatory student profile-completion UI reference
- Responsive login page

> Phase 1 pages use sample UI data only. Authentication, database-backed modules, permissions and business workflows are implemented in later phases.

## UI Preview Routes

| Route | Purpose |
| --- | --- |
| `/dashboard` | Main responsive dashboard |
| `/students` | Student listing/table reference |
| `/students/register` | Teacher quick-registration reference |
| `/classes` | Class card layout |
| `/subjects` | Subject card layout |
| `/profile/complete` | Mandatory student profile completion |
| `/login` | Responsive login screen |

Append `?role=administrator`, `?role=teacher`, `?role=class-teacher`, or `?role=student` to preview role-aware navigation during Phase 1.

Example: `/dashboard?role=teacher`.

## Local Setup

Requirements: PHP 8.3+, Composer, Node.js/npm, and MySQL 8+.

```bash
git clone https://github.com/Agien99/edtech360.git
cd edtech360
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `edtech360`, then update `.env` if your local credentials differ.

```bash
php artisan migrate
npm install
npm run dev
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Frontend Structure

```text
resources/
├── css/app.css
├── js/app.js
└── views/
    ├── layouts/
    ├── components/
    ├── dashboard/
    ├── students/
    ├── classes/
    ├── subjects/
    ├── profile/
    └── auth/
```

Navigation definitions are centralized in `config/navigation.php`.

## Core UX Rules

- Teachers should be able to register many students quickly, so initial registration requests only essential data.
- Students with incomplete required information must complete their profile after login before proceeding into the full system.
- Tables are used when users need to scan and compare many records; cards are used when browsing entities such as classes and subjects.
- Responsive behaviour is adaptive rather than simply shrinking desktop layouts.

## Stack

- Laravel 13
- MySQL
- Blade
- Bootstrap 5
- Bootstrap Icons
- Alpine.js
- Vite
