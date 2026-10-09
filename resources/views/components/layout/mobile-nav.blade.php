@php
    $mobileItems = [
        [
            'label' => 'Home',
            'icon' => 'bi-house-door-fill',
            'route' => 'dashboard',
            'active' => 'dashboard',
            'permission' => 'dashboard.view',
        ],
        [
            'label' => 'Classes',
            'icon' => 'bi-easel2',
            'route' => 'classes.index',
            'active' => 'classes.*',
            'permission' => 'classes.view',
        ],
        [
            'label' => 'Students',
            'icon' => 'bi-people',
            'route' => 'students.index',
            'active' => 'students.*',
            'permission' => 'students.view',
        ],
        [
            'label' => 'Subjects',
            'icon' => 'bi-book',
            'route' => 'subjects.index',
            'active' => 'subjects.*',
            'permission' => 'subjects.view',
        ],
    ];
@endphp

<nav class="ed-mobile-bottom-nav d-md-none">
    @foreach ($mobileItems as $item)
        @can($item['permission'])
            @if (Route::has($item['route']))
                <a
                    class="{{ request()->routeIs($item['active']) ? 'active' : '' }}"
                    href="{{ route($item['route']) }}"
                >
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endcan
    @endforeach

    <button
        type="button"
        data-bs-toggle="offcanvas"
        data-bs-target="#mobileSidebar"
        aria-label="More navigation options"
    >
        <i class="bi bi-three-dots"></i>
        <span>More</span>
    </button>
</nav>