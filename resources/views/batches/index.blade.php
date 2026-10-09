@extends('layouts.app')

@section('title', 'Batch Management')

@section('content')

<x-ui.page-header
    title="Batch / Cohort Management"
    description="Manage Form 6 student intake batches and cohorts."
/>

{{-- Success Message --}}
@if (session('success'))
    <div class="alert alert-success" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        {{ session('success') }}
    </div>
@endif

{{-- Validation Errors --}}
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

{{-- Batch Registration --}}
@can('batches.manage')
    <div class="ed-card p-4 mb-4">
        <h2 class="h5 mb-3">
            <i class="bi bi-plus-circle me-2"></i>
            Register New Batch
        </h2>

        <form method="POST" action="{{ route('batches.store') }}">
            @csrf

            <div class="row g-3">
                {{-- Batch Code --}}
                <div class="col-12 col-md-4">
                    <label for="code" class="form-label">
                        Batch Code <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="code"
                        id="code"
                        class="form-control @error('code') is-invalid @enderror"
                        value="{{ old('code') }}"
                        placeholder="FORM6-2026"
                        maxlength="30"
                        required
                    >
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Batch Name --}}
                <div class="col-12 col-md-5">
                    <label for="name" class="form-label">
                        Batch Name <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        placeholder="Form 6 Intake 2026"
                        maxlength="100"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Intake Year --}}
                <div class="col-12 col-md-3">
                    <label for="intake_year" class="form-label">
                        Intake Year <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        name="intake_year"
                        id="intake_year"
                        class="form-control @error('intake_year') is-invalid @enderror"
                        value="{{ old('intake_year') }}"
                        min="2000"
                        max="2100"
                        placeholder="2026"
                        required
                    >
                    @error('intake_year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Start Date --}}
                <div class="col-12 col-md-6">
                    <label for="start_date" class="form-label">
                        Start Date
                    </label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        class="form-control @error('start_date') is-invalid @enderror"
                        value="{{ old('start_date') }}"
                    >
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Expected End Date --}}
                <div class="col-12 col-md-6">
                    <label for="expected_end_date" class="form-label">
                        Expected End Date
                    </label>
                    <input
                        type="date"
                        name="expected_end_date"
                        id="expected_end_date"
                        class="form-control @error('expected_end_date') is-invalid @enderror"
                        value="{{ old('expected_end_date') }}"
                    >
                    @error('expected_end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="col-12">
                    <label for="description" class="form-label">
                        Description
                    </label>
                    <textarea
                        name="description"
                        id="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="3"
                        maxlength="2000"
                        placeholder="Optional information about this batch..."
                    >{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary ed-btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>
                    Register Batch
                </button>
            </div>
        </form>
    </div>
@endcan

{{-- Batch Listing --}}
<div class="ed-card p-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="h5 mb-0">
            <i class="bi bi-collection me-2"></i>
            Registered Batches
        </h2>

        <span class="badge text-bg-secondary">
            {{ $batches->total() }} Total
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Batch Code</th>
                    <th>Batch Name</th>
                    <th>Intake Year</th>
                    <th>Status</th>
                    <th>Classes</th>
                    <th>Members</th>
                    <th>Expected End</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($batches as $batch)
                    <tr>
                        <td>
                            <strong>{{ $batch->code }}</strong>
                        </td>

                        <td>{{ $batch->name }}</td>

                        <td>{{ $batch->intake_year }}</td>

                        <td>
                            <span class="badge {{ $batch->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                {{ ucfirst($batch->status) }}
                            </span>
                        </td>

                        <td>{{ $batch->school_classes_count }}</td>

                        <td>{{ $batch->student_memberships_count }}</td>

                        <td>
                            {{ $batch->expected_end_date?->format('d M Y') ?? '—' }}
                        </td>

                        <td>
                            @can('batches.manage')
                                @if ($batch->status === 'active')
                                    <details>
                                        <summary class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square me-1"></i>
                                            Edit Batch
                                        </summary>

                                        <div class="mt-3">
                                            <form
                                                method="POST"
                                                action="{{ route('batches.update', $batch) }}"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Batch Code
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="code"
                                                        class="form-control"
                                                        value="{{ $batch->code }}"
                                                        maxlength="30"
                                                        required
                                                    >
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Batch Name
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        class="form-control"
                                                        value="{{ $batch->name }}"
                                                        maxlength="100"
                                                        required
                                                    >
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Intake Year
                                                    </label>
                                                    <input
                                                        type="number"
                                                        name="intake_year"
                                                        class="form-control"
                                                        value="{{ $batch->intake_year }}"
                                                        min="2000"
                                                        max="2100"
                                                        required
                                                    >
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Start Date
                                                    </label>
                                                    <input
                                                        type="date"
                                                        name="start_date"
                                                        class="form-control"
                                                        value="{{ $batch->start_date?->toDateString() }}"
                                                    >
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Expected End Date
                                                    </label>
                                                    <input
                                                        type="date"
                                                        name="expected_end_date"
                                                        class="form-control"
                                                        value="{{ $batch->expected_end_date?->toDateString() }}"
                                                    >
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">
                                                        Description
                                                    </label>
                                                    <textarea
                                                        name="description"
                                                        class="form-control"
                                                        rows="3"
                                                        maxlength="2000"
                                                    >{{ $batch->description }}</textarea>
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="btn btn-primary btn-sm"
                                                >
                                                    <i class="bi bi-check-lg me-1"></i>
                                                    Save Changes
                                                </button>
                                            </form>
                                        </div>
                                    </details>

                                    {{-- Batch Status Management --}}
                                    <div class="d-flex flex-wrap gap-2 mt-2">

                                        {{-- Complete Batch --}}
                                        <form
                                            method="POST"
                                            action="{{ route('batches.change-status', $batch) }}"
                                            onsubmit="return confirm('Complete this batch? This action cannot currently be reversed.');"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden" name="status" value="completed">

                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Complete Batch
                                            </button>
                                        </form>

                                        {{-- Deactivate Batch --}}
                                        <form
                                            method="POST"
                                            action="{{ route('batches.change-status', $batch) }}"
                                            onsubmit="return confirm('Deactivate this batch? This action cannot currently be reversed.');"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden" name="status" value="inactive">

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-slash-circle me-1"></i>
                                                Deactivate Batch
                                            </button>
                                        </form>

                                    </div>
                                @else
                                    <span class="text-muted small">
                                        Editing unavailable
                                    </span>
                                @endif
                            @endcan
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            No student batches have been registered.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $batches->links() }}
    </div>
</div>

@endsection