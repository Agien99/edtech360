@php
    $role = $role ?? 'administrator';
    $sections = config("navigation.roles.{$role}", config('navigation.roles.administrator'));
@endphp
<aside class="app-sidebar" data-sidebar>
    <div class="sidebar-brand">
        <a href="{{ route('dashboard', ['role' => $role]) }}" aria-label="EdTech360 dashboard">
            <img class="brand-logo-full" src="{{ asset('assets/branding/edtech360-logo-horizontal-dark.svg') }}" alt="EdTech360">
            <img class="brand-logo-mark" src="{{ asset('assets/branding/edtech360-mark.svg') }}" alt="">
        </a>
        <button class="sidebar-close d-lg-none" type="button" data-sidebar-toggle aria-label="Close navigation"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="sidebar-scroll">
        @foreach ($sections as $section => $items)
            @if ($section !== 'Main')<div class="sidebar-section-label">{{ $section }}</div>@endif
            <nav class="sidebar-nav" aria-label="{{ $section }}">
                @foreach ($items as $item)
                    @php
                        $hasRoute = isset($item['route']);
                        $active = $hasRoute && request()->routeIs($item['route']);
                        $url = $hasRoute ? route($item['route'], ['role' => $role]) : '#';
                    @endphp
                    <a href="{{ $url }}" class="sidebar-link {{ $active ? 'active' : '' }} {{ $hasRoute ? '' : 'is-disabled' }}" @if(!$hasRoute) aria-disabled="true" @endif title="{{ $item['label'] }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span class="sidebar-link-label">{{ $item['label'] }}</span>
                        @if(!$hasRoute)<span class="sidebar-soon">Soon</span>@endif
                    </a>
                @endforeach
            </nav>
        @endforeach
    </div>

    <button class="sidebar-collapse d-none d-lg-flex" type="button" data-sidebar-collapse><i class="bi bi-chevron-double-left"></i><span>Collapse</span></button>
</aside>
