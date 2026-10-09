@extends('layouts.app')

@section('title', 'Semester Management')

@section('content')
<x-ui.page-header
    title="Semester Management"
    description="Configure semesters for {{ $academicSession->name }}."
/>

<a href="{{ route('academic-sessions.index') }}"
   class="btn btn-outline-secondary mb-4">
    <i class="bi bi-arrow-left"></i>
    Back to Academic Sessions
</a>

@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please correct the following:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="ed-card p-4 mb-4">
    <div class="d-flex justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="h5">{{ $academicSession->name }}</h2>
            <p class="text-muted mb-0">
                {{ $academicSession->start_date->format('d M Y') }}
                —
                {{ $academicSession->end_date->format('d M Y') }}
            </p>
        </div>

        <div>
            <span class="badge text-bg-secondary">
                {{ ucfirst($academicSession->status) }}
            </span>
            @if ($academicSession->is_current)
                <span class="badge text-bg-success">
                    Current
                </span>
            @endif
        </div>
    </div>
</div>


{{-- Semester Progression --}}
@can('academic_sessions.manage')
    @if (
        $academicSession->status === 'active'
        && $academicSession->is_current
        && $semesters->contains('status', 'active')
    )
        <div class="ed-card p-4 mb-4">
            <h2 class="h5">Semester Progression</h2>

            <p class="text-muted">
                This action closes the current semester
                and activates the next semester, if one exists.
                Review academic records before proceeding.
            </p>

            <form
                method="POST"
                action="{{ route('academic-sessions.advance-semester', $academicSession) }}"
            >
                @csrf

                <button
                    type="submit"
                    class="btn btn-warning"
                    onclick="return confirm('Advance to the next semester? This action cannot be undone.')"
                >
                    <i class="bi bi-arrow-right-circle"></i>
                    Advance Semester
                </button>
            </form>
        </div>
    @endif
@endcan

@can('academic_sessions.manage')
    @if ($academicSession->status === 'planned')
        <div class="ed-card p-4 mb-4">
            <h2 class="h5 mb-3">Add Semester</h2>

            <form method="POST"
                action="{{ route('academic-sessions.semesters.store', $academicSession) }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="number">
                            Semester
                        </label>
                        <select class="form-select"
                            name="number" id="number" required>
                            <option value="">Select semester</option>
                            @for ($number = 1; $number <= 3; $number++)
                                <option value="{{ $number }}"
                                    @selected(old('number') == $number)>
                                    Semester {{ $number }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="start_date">
                            Start Date
                        </label>
                        <input type="date"
                            class="form-control"
                            name="start_date"
                            id="start_date"
                            min="{{ $academicSession->start_date->toDateString() }}"
                            max="{{ $academicSession->end_date->toDateString() }}"
                            value="{{ old('start_date') }}"
                            required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label" for="end_date">
                            End Date
                        </label>
                        <input type="date"
                            class="form-control"
                            name="end_date"
                            id="end_date"
                            min="{{ $academicSession->start_date->toDateString() }}"
                            max="{{ $academicSession->end_date->toDateString() }}"
                            value="{{ old('end_date') }}"
                            required>
                    </div>
                </div>

                <button type="submit"
                    class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i>
                    Add Semester
                </button>
            </form>
        </div>
    @endif
@endcan

<div class="ed-card p-4">
    <h2 class="h5 mb-3">Configured Semesters</h2>

    @forelse ($semesters as $semester)
        <div class="border rounded-3 p-3 mb-3">
            <div class="d-flex justify-content-between gap-3">
                <div>
                    <h3 class="h6 mb-1">
                        {{ $semester->name }}
                    </h3>
                    <p class="text-muted small mb-1">
                        {{ $semester->start_date->format('d M Y') }}
                        —
                        {{ $semester->end_date->format('d M Y') }}
                    </p>
                </div>

                <span class="badge text-bg-secondary align-self-start">
                    {{ ucfirst($semester->status) }}
                </span>
            </div>

            @can('academic_sessions.manage')
                @if (
                    $academicSession->status === 'planned'
                    && $semester->status === 'planned'
                    && ! $semester->studentClassEnrollments()->exists()
                    && ! $semester->teachingAssignments()->exists()
                    && ! $semester->timetables()->exists()
                )
                    <details class="mt-3">
                        <summary class="btn btn-sm btn-outline-primary">
                            Edit Semester
                        </summary>

                        <form method="POST"
                            class="mt-3"
                            action="{{ route('academic-sessions.semesters.update', [$academicSession, $semester]) }}">
                            @csrf
                            @method('PATCH')

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">
                                        Semester
                                    </label>
                                    <select name="number"
                                        class="form-select" required>
                                        @for ($n = 1; $n <= 3; $n++)
                                            <option value="{{ $n }}"
                                                @selected($semester->number === $n)>
                                                Semester {{ $n }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">
                                        Start Date
                                    </label>
                                    <input type="date"
                                        name="start_date"
                                        class="form-control"
                                        value="{{ $semester->start_date->toDateString() }}"
                                        min="{{ $academicSession->start_date->toDateString() }}"
                                        max="{{ $academicSession->end_date->toDateString() }}"
                                        required>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">
                                        End Date
                                    </label>
                                    <input type="date"
                                        name="end_date"
                                        class="form-control"
                                        value="{{ $semester->end_date->toDateString() }}"
                                        min="{{ $academicSession->start_date->toDateString() }}"
                                        max="{{ $academicSession->end_date->toDateString() }}"
                                        required>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn btn-primary mt-3">
                                Save Changes
                            </button>
                        </form>
                    </details>
                @endif
            @endcan
        </div>
    @empty
        <p class="text-muted mb-0">
            No semesters have been configured yet.
        </p>
    @endforelse
</div>
@endsection