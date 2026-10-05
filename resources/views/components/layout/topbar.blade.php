@php
    $role = $role ?? 'administrator';
    $roleName = match($role) {
        'teacher' => 'Teacher',
        'class-teacher' => 'Class Teacher',
        'student' => 'Student',
        default => 'Administrator',
    };
@endphp
<header class="app-topbar">
    <div class="topbar-left">
        <button class="topbar-menu" type="button" data-sidebar-toggle aria-label="Open navigation"><i class="bi bi-list"></i></button>
        <div class="global-search d-none d-md-flex"><i class="bi bi-search"></i><input type="search" placeholder="Search anything..." aria-label="Search"><kbd>⌘ K</kbd></div>
    </div>

    <div class="topbar-actions">
        <button class="icon-button d-md-none" type="button" aria-label="Search"><i class="bi bi-search"></i></button>
        <button class="icon-button notification-button" type="button" aria-label="Notifications"><i class="bi bi-bell"></i><span class="notification-dot">3</span></button>
        <button class="icon-button" type="button" data-theme-toggle aria-label="Toggle theme"><i class="bi bi-moon-stars" data-theme-icon></i></button>
        <div class="dropdown">
            <button class="profile-button dropdown-toggle" data-bs-toggle="dropdown" type="button" aria-expanded="false">
                <span class="avatar">EA</span>
                <span class="profile-copy d-none d-md-block"><strong>Eurgien</strong><small>{{ $roleName }}</small></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><h6 class="dropdown-header">UI role preview</h6></li>
                @foreach(['administrator' => 'Administrator', 'teacher' => 'Teacher', 'class-teacher' => 'Class Teacher', 'student' => 'Student'] as $value => $label)
                    <li><a class="dropdown-item {{ $role === $value ? 'active' : '' }}" href="{{ route('dashboard', ['role' => $value]) }}">{{ $label }}</a></li>
                @endforeach
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('profile.complete') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                <li><a class="dropdown-item" href="{{ route('login') }}"><i class="bi bi-box-arrow-right me-2"></i>Sign out preview</a></li>
            </ul>
        </div>
    </div>
</header>
