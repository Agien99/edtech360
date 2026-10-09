@php
    $user = auth()->user();

    $navigationGroups = [
        'Academic' => [
            [
                'label' => 'Academic Sessions',
                'icon' => 'bi-calendar3',
                'route' => 'academic-sessions.index',
                'active' => 'academic-sessions.*',
                'permission' => 'academic_sessions.view',
            ],
            [
                'label' => 'Batches',
                'icon' => 'bi-collection',
                'route' => 'batches.index',
                'active' => 'batches.*',
                'permission' => 'batches.view',
            ],
            [
                'label' => 'Students',
                'icon' => 'bi-people',
                'route' => 'students.index',
                'active' => 'students.*',
                'permission' => 'students.view',
            ],
            [
                'label' => 'Classes',
                'icon' => 'bi-easel2',
                'route' => 'classes.index',
                'active' => 'classes.*',
                'permission' => 'classes.view',
            ],
            [
                'label' => 'Subjects',
                'icon' => 'bi-book',
                'route' => 'subjects.index',
                'active' => 'subjects.*',
                'permission' => 'subjects.view',
            ],
        ],
    ];
@endphp

<nav class="ed-sidebar-nav">
    @can('dashboard.view')
        <a
            class="ed-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
            href="{{ route('dashboard') }}"
        >
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>
    @endcan

    @foreach ($navigationGroups as $groupName => $items)
        @php
            $visibleItems = collect($items)
                ->filter(fn ($item) =>
                    $user?->can($item['permission'])
                    && Route::has($item['route'])
                );
        @endphp

        @if ($visibleItems->isNotEmpty())
            <div class="ed-nav-section">
                {{ $groupName }}
            </div>

            @foreach ($visibleItems as $item)
                <a
                    class="ed-nav-link {{ request()->routeIs($item['active']) ? 'active' : '' }}"
                    href="{{ route($item['route']) }}"
                >
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endif
    @endforeach
</nav>