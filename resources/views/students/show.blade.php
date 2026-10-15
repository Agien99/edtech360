@extends('layouts.app')
@section('title','Student Record')
@section('content')
<x-ui.page-header title="Student Record" description="{{ $student->full_name }}" />
<a href="{{route('students.index')}}" class="btn btn-outline-secondary btn-sm mb-3">Back to Directory</a>
@if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
<section class="ed-card mb-3"><h2 class="h5">Student Details</h2>
@if($canAdmin)
<form method="POST" action="{{route('students.admin.update',$student)}}">@csrf @method('PATCH')
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Full Name</label><input class="form-control" name="full_name" required maxlength="150" value="{{old('full_name',$student->full_name)}}"></div>
<div class="col-md-6"><label class="form-label">IC Number</label><input class="form-control" name="ic_number" required value="{{old('ic_number',$student->ic_number)}}"></div>
<div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required value="{{old('email',$student->user?->email)}}"></div>
<div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{old('phone',$student->phone)}}"></div>
<div class="col-md-6"><label class="form-label">Student Number (optional)</label><input class="form-control" name="student_number" value="{{old('student_number',$student->student_number)}}"></div>
</div><button class="btn btn-primary mt-3">Save Changes</button></form>
@else
<dl><dt>Student Number</dt><dd>{{$student->student_number ?? 'Not assigned'}}</dd><dt>Status</dt><dd>{{$student->status}}</dd></dl>
@endif
</section>
@if($canAdmin)
<section class="ed-card mb-3"><h2 class="h5">Account Status</h2><p class="small text-muted">Deactivation blocks login but keeps history and enrollment records.</p>
<form method="POST" action="{{route('students.admin.status',$student)}}">@csrf @method('PATCH')<div class="d-flex flex-wrap gap-2 align-items-center"><select class="form-select" name="status" style="max-width:220px"><option value="active" @selected($student->status==='active')>Active</option><option value="inactive" @selected($student->status==='inactive')>Inactive</option></select><button class="btn btn-outline-primary">Update Status</button></div></form></section>
@endif
<section class="ed-card mb-3"><h2 class="h5">Intake History</h2><div class="table-responsive"><table class="table"><thead><tr><th>Academic Session</th><th>Batch</th><th>Joined</th><th>Status</th></tr></thead><tbody>
@forelse($student->batchMemberships as $m)<tr><td>{{$m->batch?->academicSession?->name}}</td><td>{{$m->batch?->code}}</td><td>{{$m->joined_at?->format('d M Y')}}</td><td>{{$m->status}}</td></tr>@empty<tr><td colspan="4">No intake history</td></tr>@endforelse
</tbody></table></div></section>
<section class="ed-card"><h2 class="h5">Class Enrollment History</h2><div class="table-responsive"><table class="table"><thead><tr><th>Session</th><th>Semester</th><th>Class</th><th>Enrolled</th><th>Ended</th><th>Status</th></tr></thead><tbody>
@forelse($student->classEnrollments->sortByDesc('enrolled_at') as $e)<tr><td>{{$e->academicSession?->name}}</td><td>{{$e->semester?->name}}</td><td>{{$e->schoolClass?->code}}</td><td>{{$e->enrolled_at?->format('d M Y')}}</td><td>{{$e->ended_at?->format('d M Y') ?? '—'}}</td><td>{{$e->status}}</td></tr>@empty<tr><td colspan="6">No class history yet</td></tr>@endforelse
</tbody></table></div></section>
@endsection
