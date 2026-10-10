@extends('layouts.app')
@section('title', 'Form 6 Batches')
@section('content')
<x-ui.page-header title="Batch / Cohort Management" description="Each academic session automatically owns one Form 6 cohort."/>
<div class="alert alert-info">
    New batches are created automatically through
    <a href="{{ route('academic-sessions.index') }}">Academic Sessions</a>.
    Batch dates are inherited from their academic session; status changes follow the session lifecycle.
</div>
<div class="ed-card p-4">
    <h2 class="h5 mb-3">Registered Batches</h2>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr>
                <th>Batch</th><th>Academic Session</th><th>Intake Year</th>
                <th>Session Start</th><th>Session End</th><th>Status</th>
                <th>Classes</th><th>Students</th>
            </tr></thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td><strong>{{ $batch->code }}</strong><div class="small text-muted">{{ $batch->name }}</div></td>
                        <td>{{ $batch->academicSession?->name ?? 'Unlinked (migration required)' }}</td>
                        <td>{{ $batch->intake_year }}</td>
                        <td>{{ $batch->academicSession?->start_date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $batch->academicSession?->end_date?->format('d M Y') ?? '—' }}</td>
                        <td><span class="badge text-bg-secondary">{{ ucfirst($batch->status) }}</span></td>
                        <td>{{ $batch->school_classes_count }}</td>
                        <td>{{ $batch->student_memberships_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No batches found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $batches->links() }}
</div>
@endsection
