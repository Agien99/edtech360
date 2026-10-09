@extends('layouts.app')
@section('title', 'Classes')
@section('content')
<x-ui.page-header title="Classes" description="Classes assigned to your account." />
<div class="row g-3">
    @forelse ($classes as $schoolClass)
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ed-entity-card">
                <h2>{{ $schoolClass->name }}</h2>
                <p>Code: {{ $schoolClass->code }}</p>
                <p>Status: {{ ucfirst($schoolClass->status) }}</p>
            </article>
        </div>
    @empty
        <div class="col-12"><div class="ed-card p-4 text-muted">No accessible classes.</div></div>
    @endforelse
</div>
<div class="mt-3">{{ $classes->links() }}</div>
@endsection