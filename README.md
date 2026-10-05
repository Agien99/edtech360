# EdTech360

**EdTech360 — Comprehensive Educational Management System**

Modern responsive educational management platform.

## Phase 1 — UI Shell & Responsive Layout

This branch establishes the UI foundation:

- Laravel 13 scaffold
- Bootstrap 5.3.8 + Bootstrap Icons 1.13.1
- EdTech360 vector branding
- Desktop/laptop collapsible sidebar
- Tablet/mobile off-canvas navigation
- Smartphone bottom navigation
- Light/dark theme
- Reusable Blade components
- Dashboard sample
- Student table + mobile card fallback
- Class and subject card views
- Quick teacher-side student registration
- Mandatory student profile completion screen
- Responsive login screen

The pages currently use mock data and UI-only actions. Authentication, permissions, database entities and module workflows will be implemented in later phases.

## Setup

    composer install
    cp .env.example .env
    php artisan key:generate
    npm install
    npm run build
    php artisan serve

Routes: /login, /dashboard, /students, /students/register, /students/complete-profile, /classes, /subjects.
