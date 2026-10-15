@php
    $user = auth()->user();
    $navigationGroups = [
        'academic' => [
            'label' => 'Academic Management', 'icon' => 'bi-mortarboard',
            'items' => [
                ['label'=>'Academic Sessions','icon'=>'bi-calendar3','route'=>'academic-sessions.index','active'=>'academic-sessions.*','permission'=>'academic_sessions.view'],
                ['label'=>'Batches / Intakes','icon'=>'bi-collection','route'=>'batches.index','active'=>'batches.*','permission'=>'batches.view'],
                ['label'=>'Classes','icon'=>'bi-easel2','route'=>'classes.index','active'=>'classes.*','permission'=>'classes.view'],
                ['label'=>'Subjects','icon'=>'bi-book','route'=>'subjects.index','active'=>'subjects.*','permission'=>'subjects.view'],
            ],
        ],
        'students' => [
            'label' => 'Student Management', 'icon' => 'bi-people',
            'items' => [
                ['label'=>'Student Directory','icon'=>'bi-person-lines-fill','route'=>'students.index','active'=>'students.*','permission'=>'students.view'],
                ['label'=>'Student Registration','icon'=>'bi-person-plus','route'=>'student-registrations.index','active'=>'student-registrations.*','permission'=>'students.create'],
                ['label'=>'Student Enrollment','icon'=>'bi-person-check','route'=>'student-enrollments.index','active'=>'student-enrollments.*','permission'=>'students.enroll'],
                ['label'=>'Class Change Windows','icon'=>'bi-arrow-left-right','route'=>'class-change-windows.index','active'=>'class-change-windows.*','permission'=>'academic_sessions.manage'],
            ],
        ],
        'teachers' => [
            'label' => 'Teacher Management', 'icon' => 'bi-person-workspace',
            'items' => [
                ['label'=>'Teacher Assignments','icon'=>'bi-person-gear','route'=>'teacher-assignments.index','active'=>'teacher-assignments.*','permission'=>'teachers.assign'],
            ],
        ],
    ];
@endphp
<nav class="ed-sidebar-nav" aria-label="Main navigation">
    @can('dashboard.view')
        @if (Route::has('dashboard'))
        <a class="ed-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-grid-1x2-fill" aria-hidden="true"></i><span>Dashboard</span>
        </a>
        @endif
    @endcan

    @foreach ($navigationGroups as $key => $group)
        @php
            $visibleItems = collect($group['items'])->filter(fn ($item) => $user?->can($item['permission']) && Route::has($item['route']));
            $isActive = $visibleItems->contains(fn ($item) => request()->routeIs($item['active']));
        @endphp
        @if ($visibleItems->isNotEmpty())
            <details class="ed-nav-group" data-nav-group="{{ $key }}" @if($isActive) open @endif>
                <summary class="ed-nav-group-heading {{ $isActive ? 'ed-nav-group-current' : '' }}" title="{{ $group['label'] }}">
                    <i class="bi {{ $group['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $group['label'] }}</span>
                    <i class="bi bi-chevron-down ed-nav-chevron" aria-hidden="true"></i>
                </summary>
                <div class="ed-nav-submenu">
                    @foreach ($visibleItems as $item)
                    <a class="ed-nav-link ed-nav-subitem {{ request()->routeIs($item['active']) ? 'active' : '' }}"
                       href="{{ route($item['route']) }}" @if(request()->routeIs($item['active'])) aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span>
                    </a>
                    @endforeach
                </div>
            </details>
        @endif
    @endforeach
</nav>
