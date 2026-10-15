@extends('layouts.app')
@section('title', 'Change My Class')
@section('content')
<x-ui.page-header title="Change My Class" description="Change classes only during the period configured for your intake." />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="ed-card p-4">
@if(!$current)
<div class="alert alert-info">You do not have exactly one current class enrollment. Contact the school administrator.</div>
@else
<p><strong>Intake:</strong> {{ $current->academicSession?->name }} · <strong>Semester:</strong> {{ $current->semester?->name }} · <strong>Current Class:</strong> {{ $current->schoolClass?->code }}</p>
@if($window)<p class="text-muted">Change period: {{ \Carbon\Carbon::parse($window->opens_at)->format('d M Y, g:i A') }} – {{ \Carbon\Carbon::parse($window->closes_at)->format('d M Y, g:i A') }}</p>@endif
@if($open && $classes->isNotEmpty())
<form method="POST" action="{{ route('student.class-change.store') }}">@csrf
<label for="class_id" class="form-label">New Class</label>
<select name="class_id" id="class_id" class="form-select" required><option value="">Choose class</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->code }} — {{ $class->name }}</option>@endforeach</select>
<button type="submit" class="btn btn-primary mt-3">Change Class</button>
</form>
@elseif(!$open)<div class="alert alert-warning">Class changes are currently closed for your intake.</div>
@else<div class="alert alert-info">No other active classes are available in your intake.</div>@endif
@endif</div>
@endsection
