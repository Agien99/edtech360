<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <title>@yield('title', 'EdTech360') · Comprehensive Educational Management System</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/branding/favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="edtech-app">
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
    @include('components.layout.sidebar', ['role' => $role ?? 'administrator'])

    <div class="app-shell" data-app-shell>
        @include('components.layout.topbar', ['role' => $role ?? 'administrator'])
        <main class="app-content">@yield('content')</main>
    </div>

    <nav class="mobile-bottom-nav d-md-none" aria-label="Mobile navigation">
        <a href="{{ route('dashboard', ['role' => $role ?? 'administrator']) }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-house-door"></i><span>Home</span></a>
        <a href="{{ route('classes.index', ['role' => $role ?? 'administrator']) }}" class="{{ request()->routeIs('classes.*') ? 'active' : '' }}"><i class="bi bi-easel2"></i><span>Classes</span></a>
        <a href="{{ route('subjects.index', ['role' => $role ?? 'administrator']) }}" class="{{ request()->routeIs('subjects.*') ? 'active' : '' }}"><i class="bi bi-journal-bookmark"></i><span>Subjects</span></a>
        <a href="#" aria-disabled="true"><i class="bi bi-calendar3"></i><span>Timetable</span></a>
        <button type="button" data-sidebar-toggle><i class="bi bi-three-dots"></i><span>More</span></button>
    </nav>
</body>
</html>
