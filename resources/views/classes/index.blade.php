@extends('layouts.app')
@section('title', 'Classes')
@section('content')
<div class="page-heading action-heading"><div><p class="eyebrow">Home / Classes</p><h1>Classes</h1><p>Browse classes as visual entities instead of forcing them into a table.</p></div><button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Class</button></div>
<div class="entity-toolbar"><div class="filter-search"><i class="bi bi-search"></i><input type="search" placeholder="Search class name..."></div><select class="form-select"><option>Academic Session: 2026</option></select><select class="form-select"><option>All Semesters</option></select></div>
<div class="entity-grid">
@foreach($classes as $class)
    <article class="entity-card class-card">
        <div class="entity-card-head"><span class="entity-icon tone-blue"><i class="bi bi-easel2"></i></span><button class="row-action"><i class="bi bi-three-dots-vertical"></i></button></div>
        <h2>{{ $class['name'] }}</h2><p>{{ $class['semester'] }}</p>
        <div class="entity-stats"><span><i class="bi bi-people"></i><b>{{ $class['students'] }}</b> Students</span><span><i class="bi bi-journal-bookmark"></i><b>{{ $class['subjects'] }}</b> Subjects</span></div>
        <div class="entity-person"><span class="avatar small">{{ collect(explode(' ', $class['teacher']))->map(fn($n) => mb_substr($n,0,1))->take(2)->join('') }}</span><div><small>Class Teacher</small><strong>{{ $class['teacher'] }}</strong></div></div>
        <button class="btn btn-soft-primary w-100">View Class <i class="bi bi-arrow-right ms-1"></i></button>
    </article>
@endforeach
</div>
@endsection
