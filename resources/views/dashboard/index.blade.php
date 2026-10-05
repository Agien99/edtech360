@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="page-heading dashboard-heading">
    <div>
        <p class="eyebrow">{{ $roleLabel }} workspace</p>
        <h1>Good Morning, Eurgien <span aria-hidden="true">👋</span></h1>
        <p>Here’s what’s happening in your school today.</p>
    </div>
    <div class="heading-date d-none d-sm-block"><span>{{ now()->format('l') }}</span><strong>{{ now()->format('j M Y') }}</strong></div>
</div>

@if($role === 'student' && request()->boolean('incomplete'))
    <div class="profile-alert">
        <div><i class="bi bi-exclamation-triangle-fill"></i><div><strong>Complete your profile</strong><span>Required information is missing. Complete it before continuing.</span></div></div>
        <a href="{{ route('profile.complete') }}" class="btn btn-primary">Complete Profile</a>
    </div>
@endif

<div class="stats-grid">
    @foreach($stats as $stat)
        <x-ui.stat-card :label="$stat['label']" :value="$stat['value']" :meta="$stat['meta']" :icon="$stat['icon']" :tone="$stat['tone']" />
    @endforeach
</div>

<div class="dashboard-grid mt-4">
    <section class="panel attendance-panel">
        <div class="panel-heading"><div><h2>{{ $role === 'student' ? 'My Attendance' : 'Student Attendance' }}</h2><span>This Week</span></div><div class="metric-positive"><strong>94.8%</strong><small>Overall Attendance</small></div></div>
        <div class="bar-chart" aria-label="Attendance chart">
            @foreach([78, 86, 81, 73, 84] as $i => $height)
                <div class="bar-column"><div class="bar-track"><span style="height: {{ $height }}%"></span></div><small>{{ ['Mon','Tue','Wed','Thu','Fri'][$i] }}</small></div>
            @endforeach
        </div>
    </section>

    <section class="panel classes-panel">
        <div class="panel-heading"><div><h2>Today’s Classes</h2><span>{{ now()->format('D, j M') }}</span></div><a href="#">View all</a></div>
        <div class="schedule-list">
            @foreach([
                ['Mathematics','Form 6A','10:00 AM – 11:00 AM','Ongoing','green'],
                ['Chemistry','Form 6B','11:00 AM – 12:00 PM','In 1 hour','blue'],
                ['Physics','Form 6C','2:00 PM – 3:00 PM','In 4 hours','orange'],
            ] as $item)
                <div class="schedule-item"><div class="schedule-icon tone-{{ $item[4] }}"><i class="bi bi-journal-bookmark"></i></div><div class="schedule-copy"><strong>{{ $item[0] }}</strong><span>{{ $item[1] }} · {{ $item[2] }}</span></div><span class="status-pill tone-{{ $item[4] }}">{{ $item[3] }}</span></div>
            @endforeach
        </div>
    </section>

    <section class="panel activity-panel">
        <div class="panel-heading"><div><h2>Recent Activities</h2><span>Latest school updates</span></div><a href="#">View all</a></div>
        <div class="activity-list">
            @foreach([
                ['bi-person-plus','New student registered','Siti Nur Aisyah','5 minutes ago','purple'],
                ['bi-journal-check','Assignment submitted','Form 6A · Mathematics','20 minutes ago','blue'],
                ['bi-check2-square','Attendance updated','Form 6B','1 hour ago','cyan'],
                ['bi-calendar-event','Timetable updated','Semester 1 · Week 3','2 hours ago','green'],
            ] as $activity)
                <div class="activity-item"><span class="activity-icon tone-{{ $activity[4] }}"><i class="bi {{ $activity[0] }}"></i></span><div><strong>{{ $activity[1] }}</strong><span>{{ $activity[2] }}</span></div><small>{{ $activity[3] }}</small></div>
            @endforeach
        </div>
    </section>

    <section class="panel progress-panel">
        <div class="panel-heading"><div><h2>Academic Session Progress</h2><span>Form 6 · 2026</span></div></div>
        <div class="progress-layout">
            <div class="progress-ring" style="--progress: 35"><div><strong>35%</strong><span>Completed</span></div></div>
            <div class="progress-meta"><strong>Semester 1</strong><span>1 Jan 2026 – 30 Apr 2026</span><ul><li><i class="dot blue"></i>Total Weeks <b>16</b></li><li><i class="dot green"></i>Completed <b>6</b></li><li><i class="dot orange"></i>Remaining <b>10</b></li></ul></div>
        </div>
    </section>
</div>
@endsection
