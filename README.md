# EdTech360

**EdTech360 — Comprehensive Educational Management System**

EdTech360 is a modern, responsive educational management platform designed for administrators, teachers, class teachers, and students.

## Current Development Status

### Phase 1 — UI Shell & Responsive Layout

Phase 1 establishes the visual and frontend foundation of the system:

- Laravel 13 application scaffold
- Bootstrap 5.3.8
- Bootstrap Icons 1.13.1
- Official EdTech360 branding
- Responsive desktop and laptop layout
- Collapsible desktop sidebar
- Tablet and mobile off-canvas navigation
- Smartphone bottom navigation
- Light/dark theme foundation
- Reusable Blade layout and UI components
- Dashboard UI
- Responsive student listing
- Class card layout
- Subject card layout
- Quick teacher-side student registration
- Mandatory student profile completion screen
- Responsive login screen

> **Note:** Phase 1 currently uses mock data and UI-only actions. Authentication, authorization, database models, and full module workflows will be implemented in later phases.

## Technology Stack

- PHP 8.3+
- Laravel 13
- MySQL
- Blade
- Bootstrap 5
- Bootstrap Icons
- Vite
- Node.js / npm

## Requirements

Before setting up the project locally, make sure the following are installed:

- PHP 8.3 or newer
- Composer
- Node.js and npm
- MySQL
- Git

For Windows development, Laragon can be used to provide PHP and MySQL.

---

## What To Do After Cloning / Pulling From GitHub

### First-Time Setup

Clone the repository and enter the project directory:

```powershell
git clone https://github.com/Agien99/edtech360.git
cd edtech360
```

Install the PHP dependencies:

```powershell
composer install
```

Create your local environment file:

```powershell
Copy-Item .env.example .env
```

Generate the Laravel application key:

```powershell
php artisan key:generate
```

Install the frontend dependencies:

```powershell
npm install
```

Build the frontend assets:

```powershell
npm run build
```

Start Laravel:

```powershell
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

### Development Mode

During development, you can run Laravel and Vite together with:

```powershell
composer run dev
```

Alternatively, use two terminals:

**Terminal 1**

```powershell
php artisan serve
```

**Terminal 2**

```powershell
npm run dev
```

---

## After Pulling New Updates

When you already have the project locally:

```powershell
git checkout main
git pull origin main
```

If `composer.json` changed, run:

```powershell
composer install
```

If `package.json` changed, run:

```powershell
npm install
```

After frontend changes, run either:

```powershell
npm run dev
```

for development, or:

```powershell
npm run build
```

for a production build.

If Laravel reports cached configuration or routes after an update, clear them with:

```powershell
php artisan optimize:clear
```

Later, when database migrations are introduced, also run:

```powershell
php artisan migrate
```

---

## Current Phase 1 Preview Routes

| Page | Route |
| --- | --- |
| Login | `/login` |
| Dashboard | `/dashboard` |
| Students | `/students` |
| Register Student | `/students/register` |
| Complete Student Profile | `/students/complete-profile` |
| Classes | `/classes` |
| Subjects | `/subjects` |

## Responsive Design

EdTech360 is designed for:

- Desktop PC
- Laptop
- Tablet
- Smartphone

The interface adapts its navigation, cards, tables, forms, and content layout according to screen size.

## Development Workflow

Development is performed primarily on the `main` branch.

The normal workflow is:

```powershell
git checkout main
git pull origin main
```

Make and test changes locally, then:

```powershell
git add .
git commit -m "your commit message"
git push origin main
```

## Project

**EdTech360**  
Comprehensive Educational Management System
