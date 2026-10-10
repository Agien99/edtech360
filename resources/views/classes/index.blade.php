@extends('layouts.app')
@section('title', 'Class Management')
@section('content')
<x-ui.page-header title="Class Management" description="Classes available to your account."/>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">
@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
</ul></div>@endif
@can('classes.create')
<div class="ed-card p-4 mb-4">
<h2 class="h5 mb-3">Register New Class</h2>
<form method="POST" action="{{ route('classes.store') }}">@csrf
<div class="row g-3">
<div class="col-12 col-md-6"><label class="form-label">Academic Session / Batch *</label>
<select class="form-select" name="academic_session_id" required>
<option value="">Select academic session</option>
@foreach($sessions as $session)
@if($session->batch?->status === 'active')
<option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name }} — {{ $session->batch->code }}</option>
@endif
@endforeach
</select><div class="form-text">Batch is chosen automatically from the session.</div></div>
<div class="col-12 col-md-3"><label class="form-label">Class Code *</label>
<input class="form-control" name="code" maxlength="30" value="{{ old('code') }}" required></div>
<div class="col-12 col-md-3"><label class="form-label">Class Name *</label>
<input class="form-control" name="name" maxlength="100" value="{{ old('name') }}" required></div>
<div class="col-12"><label class="form-label">Description</label>
<textarea class="form-control" name="description" maxlength="2000">{{ old('description') }}</textarea></div>
</div><button class="btn btn-primary mt-3" type="submit">Register Class</button>
</form></div>
@endcan
<div class="ed-card p-4">
<h2 class="h5 mb-3">Accessible Classes</h2><div class="row g-3">
@forelse($classes as $schoolClass)
<div class="col-12 col-md-6 col-xl-4"><article class="ed-entity-card h-100">
<h3 class="h5">{{ $schoolClass->name }}</h3>
<p class="mb-1">Code: <strong>{{ $schoolClass->code }}</strong></p>
<p class="mb-1">Session: {{ $schoolClass->academicSession?->name ?? '—' }}</p>
<p class="mb-1">Batch: {{ $schoolClass->batch?->code ?? '—' }}</p>
<p>Status: {{ ucfirst($schoolClass->status) }}</p>
@can('classes.update')
@if($schoolClass->status === 'active')
<details class="mt-3"><summary class="btn btn-sm btn-outline-primary">Edit Class</summary>
<form method="POST" action="{{ route('classes.update',$schoolClass) }}" class="mt-3">
@csrf @method('PATCH')
<label class="form-label">Academic Session / Batch</label>
<select class="form-select mb-2" name="academic_session_id" required>
@foreach($sessions as $session)
@if($session->batch?->status === 'active')
<option value="{{ $session->id }}" @selected($schoolClass->academic_session_id == $session->id)>{{ $session->name }} — {{ $session->batch->code }}</option>
@endif
@endforeach
</select>
<label class="form-label">Class Code</label>
<input class="form-control mb-2" name="code" value="{{ $schoolClass->code }}" required>
<label class="form-label">Class Name</label>
<input class="form-control mb-2" name="name" value="{{ $schoolClass->name }}" required>
<label class="form-label">Description</label>
<textarea class="form-control mb-2" name="description">{{ $schoolClass->description }}</textarea>
<button class="btn btn-sm btn-primary">Save Changes</button></form></details>
<form method="POST" action="{{ route('classes.deactivate',$schoolClass) }}" class="mt-2" onsubmit="return confirm('Deactivate this class?');">
@csrf @method('PATCH')
<button class="btn btn-sm btn-outline-danger">Deactivate Class</button></form>
@endif
@endcan
</article></div>
@empty <div class="col-12 text-muted">No accessible classes.</div>@endforelse
</div>{{ $classes->links() }}</div>
@endsection
