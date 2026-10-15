@extends('layouts.app')
@section('title', 'Class Change Windows')
@section('content')
<x-ui.page-header title="Class Change Windows" description="Set when students can change class within their own intake." />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@foreach($sessions as $session)
@php($window = $windows->get($session->id))
<div class="ed-card p-4 mb-3">
<h2 class="h5">{{ $session->name }}</h2>
<p class="small text-muted">Session: {{ $session->start_date?->format('d M Y') }} – {{ $session->end_date?->format('d M Y') }}</p>
<form method="POST" action="{{ route('class-change-windows.update', $session) }}" class="row g-3">@csrf @method('PUT')
<div class="col-md-4"><label class="form-label">Opens (Malaysia local server time)</label><input required class="form-control" type="datetime-local" name="opens_at" value="{{ old('opens_at', $window ? \Carbon\Carbon::parse($window->opens_at)->format('Y-m-d\TH:i') : '') }}"></div>
<div class="col-md-4"><label class="form-label">Closes</label><input required class="form-control" type="datetime-local" name="closes_at" value="{{ old('closes_at', $window ? \Carbon\Carbon::parse($window->closes_at)->format('Y-m-d\TH:i') : '') }}"></div>
<div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="is_enabled"><option value="0" @selected(!$window?->is_enabled)>Disabled</option><option value="1" @selected($window?->is_enabled)>Enabled</option></select></div>
<div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Save Window</button></div>
</form></div>
@endforeach
@endsection
