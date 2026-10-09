
@php
    $user = auth()->user();

    $displayName = $user?->name ?? 'User';

    $initials = collect(preg_split('/\s+/', trim($displayName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $roleLabels = [
        'super_admin' => 'Super Admin',
        'school_admin' => 'School Admin',
        'curriculum_admin' => 'Curriculum Admin',
        'cocurricular_admin' => 'Co-Curriculum Admin',
        'class_teacher' => 'Class Teacher',
        'assistant_class_teacher' => 'Assistant Class Teacher',
        'subject_teacher' => 'Subject Teacher',
        'subject_group_coordinator' => 'Subject Group Coordinator',
        'student' => 'Student',
    ];

    $primaryRole = $user?->getRoleNames()->first();

    $displayRole = $roleLabels[$primaryRole] ?? 'User';
@endphp

<header class="ed-topbar">
    <div class="d-flex align-items-center gap-2">
        <button
            class="btn ed-icon-btn d-lg-none"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileSidebar"
            aria-label="Open navigation"
        >
            <i class="bi bi-list fs-5"></i>
        </button>

        <div class="ed-search d-none d-md-flex">
            <i class="bi bi-search"></i>
            <input type="search" placeholder="Search anything...">
            <kbd class="d-none d-xl-inline">/</kbd>
        </div>
    </div>

    <div class="d-flex align-items-center gap-1 gap-sm-2">
        <button class="btn ed-icon-btn position-relative" type="button">
            <i class="bi bi-bell"></i>
            <span class="ed-notification-dot">3</span>
        </button>

        <button
            class="btn ed-icon-btn"
            type="button"
            data-theme-toggle
            aria-label="Toggle theme"
        >
            <i class="bi bi-moon-stars"></i>
        </button>

        <div class="dropdown">
            <button
                class="btn ed-profile-trigger dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
            >
                <span class="ed-avatar">
                    {{ $initials }}
                </span>

                <span class="ed-profile-copy d-none d-md-block">
                    <strong>{{ $displayName }}</strong>
                    <small>{{ $displayRole }}</small>
                </span>
            </button>

            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li>
                    <span class="dropdown-item-text small text-muted">
                        {{ $user?->email }}
                    </span>
                </li>

                <li>
                    <hr class="dropdown-divider">
                </li>

                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="dropdown-item text-danger"
                        >
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Sign Out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>