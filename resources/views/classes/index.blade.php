@extends('layouts.app')
@section('title', 'Class Management')
@section('content')
<x-ui.page-header title="Class Management" description="Classes available to your account." />

@if (session('success'))
    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Please correct the following:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@can('classes.create')
<div class="ed-card p-4 mb-4">
    <h2 class="h5 mb-3">Register New Class</h2>
    <form method="POST" action="{{ route('classes.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="academic_session_id">Academic Session *</label>
                <select class="form-select" name="academic_session_id" id="academic_session_id" required>
                    <option value="">Select academic session</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name }} ({{ ucfirst($session->status) }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="batch_id">Active Batch *</label>
                <select class="form-select" name="batch_id" id="batch_id" required>
                    <option value="">Select batch</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->id }}" @selected(old('batch_id') == $batch->id)>{{ $batch->code }} — {{ $batch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="code">Class Code *</label>
                <input class="form-control" type="text" id="code" name="code" value="{{ old('code') }}" maxlength="30" placeholder="6B1" required>
            </div>
            <div class="col-12 col-md-8">
                <label class="form-label" for="name">Class Name *</label>
                <input class="form-control" type="text" id="name" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Form 6 B1" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" name="description" id="description" rows="2" maxlength="2000">{{ old('description') }}</textarea>
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Register Class</button>
    </form>
</div>
@endcan

<div class="ed-card p-4">
    <h2 class="h5 mb-3">Accessible Classes</h2>
    <div class="row g-3">
        @forelse ($classes as $schoolClass)
            <div class="col-12 col-md-6 col-xl-4">
                <article class="ed-entity-card h-100">
                    <h3 class="h5">{{ $schoolClass->name }}</h3>
                    <p class="mb-1">Code: <strong>{{ $schoolClass->code }}</strong></p>
                    <p class="mb-1">Session: {{ $schoolClass->academicSession?->name ?? '—' }}</p>
                    <p class="mb-1">Batch: {{ $schoolClass->batch?->code ?? '—' }}</p>
                    <p>Status: <span class="badge {{ $schoolClass->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($schoolClass->status) }}</span></p>

                    @can('classes.update')
                        @if ($schoolClass->status === 'active')
                            <details class="mt-3">
                                <summary class="btn btn-sm btn-outline-primary">Edit Class</summary>
                                <form method="POST" action="{{ route('classes.update', $schoolClass) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <label class="form-label">Academic Session</label>
                                    <select class="form-select mb-2" name="academic_session_id" required>
                                        @foreach ($sessions as $session)
                                            <option value="{{ $session->id }}" @selected($schoolClass->academic_session_id == $session->id)>{{ $session->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="form-label">Batch</label>
                                    <select class="form-select mb-2" name="batch_id" required>
                                        @foreach ($batches as $batch)
                                            <option value="{{ $batch->id }}" @selected($schoolClass->batch_id == $batch->id)>{{ $batch->code }}</option>
                                        @endforeach
                                    </select>
                                    <label class="form-label">Class Code</label>
                                    <input class="form-control mb-2" name="code" value="{{ $schoolClass->code }}" maxlength="30" required>
                                    <label class="form-label">Class Name</label>
                                    <input class="form-control mb-2" name="name" value="{{ $schoolClass->name }}" maxlength="100" required>
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control mb-2" name="description" rows="2" maxlength="2000">{{ $schoolClass->description }}</textarea>
                                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                                </form>
                            </details>
                            <form method="POST" action="{{ route('classes.deactivate', $schoolClass) }}" class="mt-2" onsubmit="return confirm('Deactivate this class? Reactivation is not implemented.');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Deactivate Class</button>
                            </form>
                        @endif
                    @endcan
                </article>
            </div>
        @empty
            <div class="col-12"><p class="text-muted mb-0">No accessible classes.</p></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $classes->links() }}</div>
</div>
@endsection
