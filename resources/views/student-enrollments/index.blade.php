@extends('layouts.app')
@section('title', 'Student Enrollment & Placement')
@section('content')
<x-ui.page-header title="Student Enrollment & Class Placement" description="Enroll students into Form 6 intakes and semester classes." />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="ed-card p-4 mb-4">
<h2 class="h5 mb-3">Place Student in Class</h2>
<p class="text-muted small">The intake membership is created automatically using the class's academic session. Only semesters matching that class can be selected.</p>
<form method="POST" action="{{ route('student-enrollments.store') }}">@csrf
<div class="row g-3">
<div class="col-12 col-lg-6"><label for="student_profile_id" class="form-label">Student</label><select name="student_profile_id" id="student_profile_id" class="form-select" required>
<option value="">Choose student</option>@foreach($students as $student)<option value="{{ $student->id }}" @selected(old('student_profile_id') == $student->id)>{{ $student->student_number }} — {{ $student->full_name }}</option>@endforeach</select></div>
<div class="col-12 col-lg-6"><label for="class_id" class="form-label">Class / Academic Session</label><select name="class_id" id="class_id" class="form-select" required>
<option value="">Choose class</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-session="{{ $class->academic_session_id }}" @selected(old('class_id') == $class->id)>{{ $class->code }} — {{ $class->academicSession?->name }}</option>@endforeach</select></div>
<div class="col-12 col-lg-6"><label for="semester_id" class="form-label">Matching Semester</label><select name="semester_id" id="semester_id" class="form-select" required><option value="">Choose semester</option>
@foreach($semesters as $semester)<option value="{{ $semester->id }}" data-session="{{ $semester->academic_session_id }}" @selected(old('semester_id') == $semester->id)>{{ $semester->name }} — {{ $semester->academicSession?->name }}</option>@endforeach</select></div>
<div class="col-12 col-lg-6"><label for="enrolled_at" class="form-label">Enrollment Date</label><input type="date" name="enrolled_at" id="enrolled_at" class="form-control" value="{{ old('enrolled_at') }}" required></div>
</div><button class="btn btn-primary mt-3" type="submit">Enroll Student</button></form>
</div>
<div class="ed-card p-4 mb-4"><h2 class="h5 mb-3">Class Enrollment History</h2>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Student</th><th>Session</th><th>Semester</th><th>Class</th><th>Enrolled</th><th>Status</th></tr></thead><tbody>
@forelse($enrollments as $entry)<tr><td>{{ $entry->student?->full_name }}</td><td>{{ $entry->academicSession?->name }}</td><td>{{ $entry->semester?->name }}</td><td>{{ $entry->schoolClass?->code }}</td><td>{{ $entry->enrolled_at?->format('d M Y') }}</td><td>{{ ucfirst($entry->status) }}</td></tr>
@empty<tr><td colspan="6" class="text-muted text-center">No enrollments yet.</td></tr>@endforelse
</tbody></table></div>{{ $enrollments->links() }}</div>
<div class="ed-card p-4"><h2 class="h5 mb-3">Intake Membership History</h2>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Student</th><th>Intake</th><th>Session</th><th>Joined</th><th>Status</th></tr></thead><tbody>
@forelse($memberships as $membership)<tr><td>{{ $membership->student?->full_name }}</td><td>{{ $membership->batch?->code }}</td><td>{{ $membership->batch?->academicSession?->name }}</td><td>{{ $membership->joined_at?->format('d M Y') }}</td><td>{{ ucfirst($membership->status) }}</td></tr>
@empty<tr><td colspan="5" class="text-muted text-center">No memberships yet.</td></tr>@endforelse
</tbody></table></div>{{ $memberships->links() }}</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
 const classSelect = document.getElementById('class_id');
 const semesterSelect = document.getElementById('semester_id');
 const options = Array.from(semesterSelect.options).slice(1);
 function sync() {
   const selectedSession = classSelect.selectedOptions[0]?.dataset.session || '';
   for (const option of options) {
     option.hidden = option.dataset.session !== selectedSession;
     option.disabled = option.hidden;
   }
   if (semesterSelect.selectedOptions[0]?.disabled) semesterSelect.value = '';
 }
 classSelect.addEventListener('change', () => { semesterSelect.value = ''; sync(); });
 sync();
});
</script>
@endsection
