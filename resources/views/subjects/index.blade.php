@extends('layouts.app')
@section('title', 'Subjects')
@section('content')
<div class="page-heading action-heading"><div><p class="eyebrow">Home / Subjects</p><h1>Subjects</h1><p>Browse subjects, teaching teams, classes and enrolled students.</p></div><button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Subject</button></div>
<div class="entity-toolbar"><div class="filter-search"><i class="bi bi-search"></i><input type="search" placeholder="Search subject name or code..."></div><select class="form-select"><option>All Levels</option></select><select class="form-select"><option>Active</option></select></div>
<div class="entity-grid subject-grid">
@foreach($subjects as $subject)
    <article class="entity-card subject-card">
        <div class="entity-card-head"><span class="entity-icon tone-{{ $subject['tone'] }}"><i class="bi bi-journal-bookmark"></i></span><button class="row-action"><i class="bi bi-three-dots-vertical"></i></button></div>
        <h2>{{ $subject['name'] }}</h2><p>{{ $subject['code'] }}</p>
        <div class="subject-metrics"><span><i class="bi bi-person-workspace"></i>{{ $subject['teachers'] }} Teachers</span><span><i class="bi bi-easel2"></i>{{ $subject['classes'] }} Classes</span><span><i class="bi bi-people"></i>{{ $subject['students'] }} Students</span></div>
        <button class="btn btn-soft-primary w-100">Open Subject <i class="bi bi-arrow-right ms-1"></i></button>
    </article>
@endforeach
</div>
@endsection
