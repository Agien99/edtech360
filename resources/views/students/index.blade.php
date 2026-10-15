@extends('layouts.app')
@section('title','Student Directory')
@section('content')
<x-ui.page-header title="Student Directory" description="Students visible under your assigned responsibilities." />
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
<div class="ed-card mb-3">
<form method="GET" action="{{route('students.index')}}" class="row g-2 align-items-end">
<div class="col-md-6"><label class="form-label" for="q">Search name, IC, email or student number</label><input class="form-control" id="q" name="q" value="{{request('q')}}" maxlength="150"></div>
<div class="col-md-4"><label class="form-label" for="intake">Academic Session / Intake</label><select class="form-select" name="intake" id="intake"><option value="">All visible intakes</option>@foreach($intakes as $intake)<option value="{{$intake->id}}" @selected(request('intake') == $intake->id)>{{$intake->name}}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div>
</form></div>
<section class="ed-card"><div class="table-responsive"><table class="table ed-table align-middle mb-0"><thead><tr><th>Student</th><th>Student Number</th><th>Intake</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($students as $student)
<tr><td><strong>{{$student->full_name}}</strong><div class="small text-muted">{{$student->user?->email}}</div></td><td>{{$student->student_number ?? 'Not assigned'}}</td><td>{{$student->batchMemberships->map(fn($m) => $m->batch?->academicSession?->name)->filter()->join(', ') ?: '—'}}</td><td>{{ucfirst($student->status)}}</td><td><a class="btn btn-sm btn-outline-primary" href="{{route('students.show',$student)}}">View</a></td></tr>
@empty<tr><td colspan="5" class="text-muted text-center py-4">No matching accessible students.</td></tr>@endforelse
</tbody></table></div><div class="p-3">{{$students->links()}}</div></section>
@endsection
