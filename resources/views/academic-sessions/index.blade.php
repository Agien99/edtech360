@extends('layouts.app')
@section('title', 'Academic Sessions')
@section('content')
<x-ui.page-header title="Academic Sessions" description="Each Form 6 intake has its own session, batch and three semesters. Sessions may overlap."/>
@if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
@if($errors->any())
<div class="alert alert-danger"><ul class="mb-0">
@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
</ul></div>
@endif
@can('academic_sessions.manage')
<div class="ed-card p-4 mb-4">
<h2 class="h5 mb-3">Create Academic Session and Batch</h2>
<p class="small text-muted">The Form 6 Batch is automatically generated. Configure its three semesters after saving.</p>
<form method="POST" action="{{ route('academic-sessions.store') }}">
@csrf
<div class="row g-3">
<div class="col-12 col-md-4"><label class="form-label" for="name">Session Name</label>
<input id="name" name="name" class="form-control" maxlength="30" value="{{ old('name') }}" placeholder="2028/2029" required></div>
<div class="col-12 col-md-4"><label class="form-label" for="start_date">Start Date</label>
<input id="start_date" type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required></div>
<div class="col-12 col-md-4"><label class="form-label" for="end_date">End Date</label>
<input id="end_date" type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required></div>
</div>
<button class="btn btn-primary mt-3" type="submit">Create Session + Batch</button>
</form>
</div>
@endcan
<div class="ed-card p-4">
<h2 class="h5 mb-3">Sessions and Their Batches</h2>
<div class="table-responsive"><table class="table table-hover align-middle">
<thead><tr><th>Session</th><th>Batch</th><th>Dates</th><th>Status</th><th>Semesters</th><th>Actions</th></tr></thead>
<tbody>
@forelse($sessions as $academicSession)
<tr>
<td><strong>{{ $academicSession->name }}</strong></td>
<td>{{ $academicSession->batch?->code ?? 'Missing batch' }}</td>
<td>{{ $academicSession->start_date->format('d M Y') }} – {{ $academicSession->end_date->format('d M Y') }}</td>
<td><span class="badge text-bg-secondary">{{ ucfirst($academicSession->status) }}</span></td>
<td>@forelse($academicSession->semesters as $semester)
<div class="small">{{ $semester->name }} ({{ ucfirst($semester->status) }})</div>
@empty <span class="text-muted">Not configured</span>@endforelse</td>
<td><div class="d-flex flex-column gap-2">
<a class="btn btn-sm btn-outline-primary" href="{{ route('academic-sessions.semesters.index',$academicSession) }}">Manage Semesters</a>
@can('academic_sessions.manage')
@if($academicSession->status === 'planned')
<form method="POST" action="{{ route('academic-sessions.activate',$academicSession) }}">@csrf
<button type="submit" class="btn btn-sm btn-success w-100">Activate Session</button></form>
@elseif($academicSession->status === 'active')
<form method="POST" action="{{ route('academic-sessions.close',$academicSession) }}">@csrf
<button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Close this session after all semesters and enrollments are finalized?')">Close Session</button></form>
@endif
@endcan
</div></td>
</tr>
@empty <tr><td colspan="6" class="text-muted text-center py-4">No sessions registered.</td></tr>
@endforelse
</tbody></table></div>
{{ $sessions->links() }}
</div>
@endsection
