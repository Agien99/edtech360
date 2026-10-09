@extends('layouts.app')

@section('title', 'Academic Sessions')

@section('content')
<x-ui.page-header
    title="Academic Sessions"
    description="Manage academic years and their semesters."
/>

@if (session('success'))
    <div class="alert alert-success" role="alert">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Please correct the following:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@can('academic_sessions.manage')
    <div class="ed-card p-4 mb-4">
        <h2 class="h5 mb-3">Create Academic Session</h2>

        <form
            method="POST"
            action="{{ route('academic-sessions.store') }}"
        >
            @csrf

            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label for="name" class="form-label">
                        Session Name
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="30"
                        value="{{ old('name') }}"
                        placeholder="e.g. 2026/2027"
                        class="form-control @error('name') is-invalid @enderror"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="start_date" class="form-label">
                        Start Date
                    </label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="{{ old('start_date') }}"
                        class="form-control @error('start_date') is-invalid @enderror"
                        required
                    >
                    @error('start_date')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="col-12 col-md-4">
                    <label for="end_date" class="form-label">
                        End Date
                    </label>
                    <input
                        type="date"
                        id="end_date"
                        name="end_date"
                        value="{{ old('end_date') }}"
                        class="form-control @error('end_date') is-invalid @enderror"
                        required
                    >
                    @error('end_date')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i>
                    Create Session
                </button>
            </div>
        </form>
    </div>
@endcan

<div class="ed-card p-4">
    <h2 class="h5 mb-3">Academic Session Records</h2>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Session</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                    <th>Semesters</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($sessions as $academicSession)
                    <tr>
                        <td>
                            <strong>
                                {{ $academicSession->name }}
                            </strong>

                            @if ($academicSession->is_current)
                                <span class="badge text-bg-success ms-1">
                                    Current
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $academicSession->start_date->format('d M Y') }}
                        </td>

                        <td>
                            {{ $academicSession->end_date->format('d M Y') }}
                        </td>

                        <td>
                            <span class="badge text-bg-secondary">
                                {{ ucfirst($academicSession->status) }}
                            </span>
                        </td>

                        <td>
                            @forelse ($academicSession->semesters as $semester)
                                <div class="small mb-1">
                                    {{ $semester->name }}
                                    ({{ ucfirst($semester->status) }})
                                </div>
                            @empty
                                <span class="text-muted small">
                                    Not configured
                                </span>
                            @endforelse
                        </td>
                        
                        <td>
                            <div class="d-flex flex-column gap-2">

                                {{-- Manage Semesters --}}
                                <a
                                    href="{{ route('academic-sessions.semesters.index', $academicSession) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    <i class="bi bi-calendar3"></i>
                                    Manage Semesters
                                </a>

                                {{-- Academic Session Lifecycle --}}
                                @can('academic_sessions.manage')

                                    {{-- Activate a planned session --}}
                                    @if ($academicSession->status === 'planned')
                                        <form
                                            method="POST"
                                            action="{{ route('academic-sessions.activate', $academicSession) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success w-100"
                                                onclick="return confirm('Activate this academic session?')"
                                            >
                                                <i class="bi bi-play-circle"></i>
                                                Activate Session
                                            </button>
                                        </form>

                                    {{-- Close the current active session --}}
                                    @elseif (
                                        $academicSession->status === 'active'
                                        && $academicSession->is_current
                                    )
                                        <form
                                            method="POST"
                                            action="{{ route('academic-sessions.close', $academicSession) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger w-100"
                                                onclick="return confirm('Close this academic session? This action cannot be undone.')"
                                            >
                                                <i class="bi bi-lock"></i>
                                                Close Session
                                            </button>
                                        </form>
                                    @endif

                                @endcan

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No academic sessions have been created.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $sessions->links() }}
    </div>
</div>
@endsection